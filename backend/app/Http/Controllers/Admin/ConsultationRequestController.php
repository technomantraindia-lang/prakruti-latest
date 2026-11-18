<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\DoctorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConsultationRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ConsultationRequest::query()->latest();

        $filter = $request->get('filter', 'all');

        if ($filter === 'pending_approval') {
            $query->where('admin_approved', false);
        } elseif ($filter === 'approved') {
            $query->where('admin_approved', true);
        } elseif ($filter === 'followup') {
            $query->where(function ($q) {
                $q->where('session_type', 'follow_up')
                  ->orWhere('is_order_followup', true);
            });
        } elseif ($filter === 'scheduled') {
            $query->whereNotNull('scheduled_at');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(15)->withQueryString();

        // Summary counts
        $pendingApprovalCount = ConsultationRequest::where('admin_approved', false)->count();
        $approvedCount = ConsultationRequest::where('admin_approved', true)->count();
        $followupCount = ConsultationRequest::where(function ($q) {
            $q->where('session_type', 'follow_up')
              ->orWhere('is_order_followup', true);
        })->count();
        $scheduledCount = ConsultationRequest::whereNotNull('scheduled_at')->count();

        $doctors = DoctorProfile::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.consultations.index', compact(
            'requests',
            'filter',
            'pendingApprovalCount',
            'approvedCount',
            'followupCount',
            'scheduledCount',
            'doctors'
        ));
    }

    public function show(ConsultationRequest $consultation)
    {
        $doctors = DoctorProfile::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.consultations.show', compact('consultation', 'doctors'));
    }

    public function update(Request $request, ConsultationRequest $consultation)
    {
        $data = $request->validate([
            'status' => 'required|string',
            'admin_approved' => 'nullable|boolean',
            'assigned_to' => 'nullable|string|max:255',
            'internal_note' => 'nullable|string',
        ]);

        if (isset($data['admin_approved'])) {
            $data['admin_approved_at'] = $data['admin_approved'] ? now() : null;
            $data['admin_approved_by'] = $data['admin_approved'] ? auth()->id() : null;
        }

        $consultation->update($data);

        return redirect()->route('admin.consultations.show', $consultation)->with('success', 'Consultation details updated successfully.');
    }

    /**
     * Admin approves or unapproves customer for Doctor Panel via 1-click Tick.
     * Only approved customers appear in the Doctor Panel!
     */
    public function toggleApproval(Request $request, ConsultationRequest $consultation)
    {
        $approved = $request->has('admin_approved')
            ? $request->boolean('admin_approved')
            : ! $consultation->admin_approved;

        $consultation->update([
            'admin_approved' => $approved,
            'admin_approved_at' => $approved ? now() : null,
            'admin_approved_by' => $approved ? auth()->id() : null,
            'status' => $approved ? 'approved' : 'new',
        ]);

        $msg = $approved
            ? "Patient #{$consultation->reference_no} ({$consultation->name}) approved! They are now visible in the Doctor Panel."
            : "Patient #{$consultation->reference_no} unapproved and hidden from Doctor Panel.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'admin_approved' => $consultation->admin_approved,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Admin assigns a registered doctor from dropdown and sends the consultation data to the Doctor Panel.
     */
    public function sendToDoctor(Request $request, ConsultationRequest $consultation)
    {
        if ($request->filled('zoom_link')) {
            $zoomInput = trim((string) $request->input('zoom_link'));
            if (!preg_match('/^https?:\/\//i', $zoomInput)) {
                $zoomInput = 'https://' . $zoomInput;
            }
            $request->merge(['zoom_link' => $zoomInput]);
        }

        $validated = $request->validate([
            'doctor_profile_id' => 'required|exists:doctor_profiles,id',
            'scheduled_at' => 'nullable|date',
            'zoom_link' => 'nullable|url|max:500',
        ]);

        $doctor = DoctorProfile::findOrFail($validated['doctor_profile_id']);

        $updateData = [
            'doctor_profile_id' => $doctor->id,
            'assigned_to' => $doctor->name,
            'doctor_phone' => $doctor->phone,
            'doctor_email' => $doctor->email,
            'admin_approved' => true,
            'admin_approved_at' => now(),
            'admin_approved_by' => auth()->id(),
            'status' => 'approved',
        ];

        if (!empty($validated['scheduled_at'])) {
            $updateData['scheduled_at'] = $validated['scheduled_at'];
            $updateData['video_status'] = 'scheduled';
        }
        if (!empty($validated['zoom_link'])) {
            $updateData['zoom_link'] = trim($validated['zoom_link']);
        }

        $consultation->update($updateData);

        $docName = $doctor->formatted_name;
        return back()->with('success', "Patient #{$consultation->reference_no} ({$consultation->name}) assigned to {$docName} and sent to Doctor Panel!");
    }

    /**
     * Admin sets appointment time, inputs Zoom link, and sends WhatsApp directly to Customer.
     */
    public function scheduleAndNotifyCustomer(Request $request, ConsultationRequest $consultation)
    {
        if ($request->filled('zoom_link')) {
            $zoomInput = trim((string) $request->input('zoom_link'));
            if (!preg_match('/^https?:\/\//i', $zoomInput)) {
                $zoomInput = 'https://' . $zoomInput;
            }
            $request->merge(['zoom_link' => $zoomInput]);
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'zoom_link' => 'nullable|url|max:500',
            'doctor_profile_id' => 'nullable|exists:doctor_profiles,id',
            'channel' => 'nullable|string|in:panel,whatsapp,email,both',
            'auto_approve' => 'nullable|boolean',
            'internal_note' => 'nullable|string',
            'assigned_to' => 'nullable|string|max:255',
        ]);

        $actionType = $request->input('action_type', 'save_and_whatsapp');
        $channel = $validated['channel'] ?? 'whatsapp';
        $zoomLink = !empty($validated['zoom_link']) ? trim($validated['zoom_link']) : $consultation->zoom_link;
        $autoApprove = $request->boolean('auto_approve', true);

        $doctor = null;
        if (!empty($validated['doctor_profile_id'])) {
            $doctor = DoctorProfile::find($validated['doctor_profile_id']);
        }

        $updateData = [
            'scheduled_at' => $validated['scheduled_at'],
            'zoom_link' => $zoomLink,
            'zoom_meeting_id' => null,
            'zoom_passcode' => null,
            'video_status' => 'scheduled',
            'admin_approved' => $autoApprove ? true : $consultation->admin_approved,
            'admin_approved_at' => ($autoApprove && !$consultation->admin_approved) ? now() : $consultation->admin_approved_at,
            'admin_approved_by' => ($autoApprove && !$consultation->admin_approved) ? auth()->id() : $consultation->admin_approved_by,
            'customer_notified_channel' => $channel,
            'internal_note' => !empty($validated['internal_note']) ? trim($validated['internal_note']) : $consultation->internal_note,
        ];

        if ($actionType === 'save_and_whatsapp') {
            $updateData['customer_notified_at'] = now();
            $updateData['customer_whatsapp_sent_at'] = now();
        }

        if ($doctor) {
            $updateData['doctor_profile_id'] = $doctor->id;
            $updateData['assigned_to'] = $doctor->name;
            $updateData['doctor_phone'] = $doctor->phone;
            $updateData['doctor_email'] = $doctor->email;
        } elseif (!empty($validated['assigned_to'])) {
            $updateData['assigned_to'] = trim($validated['assigned_to']);
        }

        $consultation->update($updateData);

        // Dispatch notifications to Customer and Admin
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
            \Illuminate\Support\Facades\Log::warning('Schedule notification dispatch error: ' . $e->getMessage());
        }

        // Clean customer phone number for WhatsApp
        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$consultation->phone);
        if (str_starts_with($cleanPhone, '0') && strlen($cleanPhone) === 11) {
            $cleanPhone = '91' . substr($cleanPhone, 1);
        } elseif (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        $waMessage = $consultation->buildCustomerAppointmentWhatsAppMessage($zoomLink);
        // Direct Web WhatsApp link opens WhatsApp Web in browser immediately
        $waUrl = !empty($cleanPhone) ? "https://web.whatsapp.com/send?phone={$cleanPhone}&text=" . rawurlencode($waMessage) : null;

        $emailSubject = $consultation->buildCustomerAppointmentEmailSubject();
        $emailBody = $consultation->buildCustomerAppointmentEmailBody($zoomLink);
        $gmailUrl = !empty($consultation->email) ? "https://mail.google.com/mail/?view=cm&fs=1&to=" . rawurlencode((string)$consultation->email) . "&su=" . rawurlencode($emailSubject) . "&body=" . rawurlencode($emailBody) : null;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $actionType === 'save_only' ? 'Appointment details saved successfully!' : 'Appointment scheduled and Web WhatsApp link ready.',
                'whatsapp_url' => $waUrl,
                'whatsapp_message' => $waMessage,
                'gmail_url' => $gmailUrl,
                'zoom_link' => $zoomLink,
                'admin_approved' => $consultation->admin_approved,
            ]);
        }

        if ($actionType === 'save_only') {
            return back()->with([
                'success' => "Appointment details saved successfully! Assigned to: {$consultation->doctor_display_name}.",
            ]);
        }

        return back()->with([
            'success' => "Details saved and Web WhatsApp opened for {$consultation->name}!",
            'launch_whatsapp' => $waUrl,
            'whatsapp_message' => $waMessage,
            'launch_gmail' => in_array($channel, ['email', 'both'], true) ? $gmailUrl : null,
        ]);
    }

    /**
     * Delete / remove consultation request.
     */
    public function destroy(ConsultationRequest $consultation)
    {
        $ref = $consultation->reference_no;
        $consultation->delete();

        return redirect()->route('admin.consultations.index')->with('success', "Consultation #{$ref} permanently deleted.");
    }

    /**
     * Admin shares Zoom link & appointment time with Doctor via WhatsApp (optional direct dispatch).
     */
    public function shareWithDoctorWhatsApp(Request $request, ConsultationRequest $consultation)
    {
        $validated = $request->validate([
            'doctor_phone' => 'required|string|max:30',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$validated['doctor_phone']);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        $waMessage = $consultation->buildDoctorAppointmentWhatsAppMessage();
        $waUrl = "https://api.whatsapp.com/send?phone={$cleanPhone}&text=" . rawurlencode($waMessage);

        $consultation->update([
            'doctor_whatsapp_sent_at' => now(),
            'doctor_phone' => $validated['doctor_phone'],
        ]);

        return back()->with([
            'success' => 'Appointment details & Zoom link prepared for Doctor on WhatsApp.',
            'launch_whatsapp' => $waUrl,
        ]);
    }
}
