<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;

class DoctorPanelController extends Controller
{
    /**
     * Standalone Doctor Appointments Workspace.
     * Displays all incoming patient consultations directly to the doctor.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Doctors only see consultations approved and sent by Admin
        $baseQuery = ConsultationRequest::query()->where('admin_approved', true);

        // Find doctor profile for this authenticated user
        $doctorProfile = null;
        if ($user) {
            $doctorProfile = \App\Models\DoctorProfile::where('email', $user->email)
                ->orWhere('phone', $user->phone)
                ->orWhere('name', 'like', '%' . $user->name . '%')
                ->first();
        }

        if ($doctorProfile && !($user && $user->isAdmin())) {
            $hasAssigned = (clone $baseQuery)->where(function ($q) use ($doctorProfile, $user) {
                $q->where('doctor_profile_id', $doctorProfile->id)
                  ->orWhere('doctor_email', $user->email)
                  ->orWhere('assigned_to', $user->name)
                  ->orWhere('assigned_to', $doctorProfile->name);
            })->exists();

            if ($hasAssigned && $request->get('view') !== 'all') {
                $baseQuery->where(function ($q) use ($doctorProfile, $user) {
                    $q->where('doctor_profile_id', $doctorProfile->id)
                      ->orWhere('doctor_email', $user->email)
                      ->orWhere('assigned_to', $user->name)
                      ->orWhere('assigned_to', $doctorProfile->name);
                });
            }
        }

        $query = (clone $baseQuery)->latest('id');

        $tab = $request->get('tab', 'all');

        if ($tab === 'today') {
            $query->whereDate('scheduled_at', today())
                  ->where('video_status', '!=', 'completed');
        } elseif ($tab === 'upcoming') {
            $query->where('video_status', '!=', 'completed');
        } elseif ($tab === 'followup') {
            $query->where(function ($q) {
                $q->where('session_type', 'follow_up')
                  ->orWhere('is_order_followup', true);
            })->where('video_status', '!=', 'completed');
        } elseif ($tab === 'completed') {
            $query->where('video_status', 'completed');
        } else {
            // By default on 'all' active tab, hide completed consultations so active list stays fresh
            $query->where('video_status', '!=', 'completed');
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('reference_no', 'like', "%{$s}%")
                  ->orWhere('concern', 'like', "%{$s}%");
            });
        }

        $appointments = $query->paginate(15)->withQueryString();

        // Counters
        $allCount = (clone $baseQuery)->where('video_status', '!=', 'completed')->count();
        $upcomingCount = (clone $baseQuery)->where('video_status', '!=', 'completed')->count();
        $todayCount = (clone $baseQuery)->whereDate('scheduled_at', today())
            ->where('video_status', '!=', 'completed')
            ->count();
        $followupCount = (clone $baseQuery)->where(function ($q) {
            $q->where('session_type', 'follow_up')
              ->orWhere('is_order_followup', true);
        })->where('video_status', '!=', 'completed')->count();
        $completedCount = (clone $baseQuery)->where('video_status', 'completed')->count();

        return view('doctor.appointments.index', compact(
            'appointments',
            'tab',
            'allCount',
            'todayCount',
            'upcomingCount',
            'followupCount',
            'completedCount',
            'doctorProfile'
        ));
    }

    public function show(ConsultationRequest $consultation)
    {
        return view('doctor.appointments.show', compact('consultation'));
    }

    /**
     * Doctor schedules appointment date/time & meeting link directly.
     */
    public function schedule(Request $request, ConsultationRequest $consultation)
    {
        if ($request->filled('meeting_link')) {
            $link = trim((string) $request->input('meeting_link'));
            if (!preg_match('/^https?:\/\//i', $link)) {
                $link = 'https://' . $link;
            }
            $request->merge(['meeting_link' => $link]);
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'meeting_link' => 'nullable|url|max:500',
        ]);

        $consultation->update([
            'scheduled_at' => $validated['scheduled_at'],
            'zoom_link' => !empty($validated['meeting_link']) ? trim($validated['meeting_link']) : $consultation->zoom_link,
            'video_status' => 'scheduled',
            'admin_approved' => true,
        ]);

        // Notify Customer & Admin
        try {
            $custUser = $consultation->user ?: ($consultation->email ? \App\Models\User::where('email', strtolower(trim($consultation->email)))->first() : null);
            if ($custUser) {
                $custUser->notify(new \App\Notifications\ConsultationStatusUpdatedNotification($consultation, 'scheduled', 'customer'));
            }
            $adminRoleIds = \App\Models\Role::whereIn('name', ['Super Admin', 'Admin'])->pluck('id');
            $admins = \App\Models\User::whereIn('role_id', $adminRoleIds)->get();
            foreach ($admins as $adm) {
                $adm->notify(new \App\Notifications\ConsultationStatusUpdatedNotification($consultation, 'scheduled', 'admin'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Doctor schedule notification error: ' . $e->getMessage());
        }

        return back()->with('success', "Appointment scheduled for {$consultation->name}! The patient will now see the scheduled time and video call button in their account panel.");
    }

    /**
     * Doctor updates / sets meeting link directly.
     */
    public function saveMeetingLink(Request $request, ConsultationRequest $consultation)
    {
        if ($request->filled('meeting_link')) {
            $link = trim((string) $request->input('meeting_link'));
            if (!preg_match('/^https?:\/\//i', $link)) {
                $link = 'https://' . $link;
            }
            $request->merge(['meeting_link' => $link]);
        }

        $validated = $request->validate([
            'meeting_link' => 'required|url|max:500',
        ]);

        $link = trim($validated['meeting_link']);
        $docName = auth()->user()->name ?? 'Doctor';

        $consultation->update([
            'zoom_link' => $link,
            'video_status' => 'scheduled',
            'admin_approved' => true,
        ]);

        // Dispatch notification to Admin users that doctor updated the video meeting link
        try {
            $adminRoleIds = \App\Models\Role::whereIn('name', ['Super Admin', 'Admin'])->pluck('id');
            $admins = \App\Models\User::whereIn('role_id', $adminRoleIds)->get();
            foreach ($admins as $adm) {
                $adm->notify(new \App\Notifications\ConsultationStatusUpdatedNotification($consultation, 'meeting_link_updated', 'admin'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Doctor meeting link notification error: ' . $e->getMessage());
        }

        return back()->with('success', "Meeting link saved and instantly synchronized with Admin Panel! Link: {$link}");
    }

    /**
     * Doctor saves clinical dietary notes.
     */
    public function saveNotes(Request $request, ConsultationRequest $consultation)
    {
        $validated = $request->validate([
            'doctor_notes' => 'required|string',
        ]);

        $consultation->update([
            'doctor_notes' => $validated['doctor_notes'],
        ]);

        return back()->with('success', 'Clinical notes and consultation summary saved successfully.');
    }

    /**
     * Doctor marks consultation as completed (automatically removes/clears from active workspace).
     */
    public function complete(Request $request, ConsultationRequest $consultation)
    {
        $closingNotes = $request->input('closing_notes');
        $currentNotes = $consultation->doctor_notes ? $consultation->doctor_notes . "\n\n" : '';
        $docName = auth()->user()->name ?? 'Doctor';
        $newNotes = $currentNotes . "[Session Completed by Dr. {$docName} on " . now()->format('d M Y, h:i A') . "]: " . ($closingNotes ?: 'Video consultation completed successfully.');

        $consultation->update([
            'video_status' => 'completed',
            'status' => 'completed',
            'doctor_notes' => trim($newNotes),
        ]);

        // Notify Customer & Admin that appointment is done / completed
        try {
            $custUser = $consultation->user ?: ($consultation->email ? \App\Models\User::where('email', strtolower(trim($consultation->email)))->first() : null);
            if ($custUser) {
                $custUser->notify(new \App\Notifications\ConsultationStatusUpdatedNotification($consultation, 'completed', 'customer'));
            }
            $adminRoleIds = \App\Models\Role::whereIn('name', ['Super Admin', 'Admin'])->pluck('id');
            $admins = \App\Models\User::whereIn('role_id', $adminRoleIds)->get();
            foreach ($admins as $adm) {
                $adm->notify(new \App\Notifications\ConsultationStatusUpdatedNotification($consultation, 'completed', 'admin'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Doctor complete notification error: ' . $e->getMessage());
        }

        return back()->with('success', "Consultation #{$consultation->reference_no} completed and removed from active workspace.");
    }

    /**
     * Doctor permanently deletes / removes consultation from workspace.
     */
    public function destroy(ConsultationRequest $consultation)
    {
        $ref = $consultation->reference_no;
        $consultation->delete();

        return back()->with('success', "Consultation #{$ref} deleted from workspace.");
    }
}
