<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\DoctorProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConsultantAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = ConsultationRequest::query()->latest();

        // Status / category tabs
        $filter = $request->get('filter', 'pending');

        if ($filter === 'pending') {
            $query->where(function ($q) {
                $q->whereIn('video_status', ['pending', 'not_sent'])
                  ->orWhereNull('video_status');
            });
        } elseif ($filter === 'followup') {
            $query->where(function ($q) {
                $q->where('is_order_followup', true)
                  ->orWhere('session_type', 'follow_up');
            });
        } elseif ($filter === 'scheduled') {
            $query->whereIn('video_status', ['scheduled', 'link_shared']);
        } elseif ($filter === 'completed') {
            $query->where('video_status', 'completed');
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference_no', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('concern', 'like', "%{$s}%");
            });
        }

        $appointments = $query->paginate(15)->withQueryString();

        // Quick KPI counts
        $pendingCount = ConsultationRequest::where(function ($q) {
            $q->whereIn('video_status', ['pending', 'not_sent'])
              ->orWhereNull('video_status');
        })->count();

        $followupCount = ConsultationRequest::where(function ($q) {
            $q->where('is_order_followup', true)
              ->orWhere('session_type', 'follow_up');
        })->count();

        $scheduledTodayCount = ConsultationRequest::whereDate('scheduled_at', today())->count();
        $completedCount = ConsultationRequest::where('video_status', 'completed')->count();

        $doctors = DoctorProfile::where('is_active', true)->orderBy('sort_order')->get();
        $recentOrders = Order::with('user')->latest()->take(20)->get();

        return view('consultant.appointments.index', compact(
            'appointments',
            'pendingCount',
            'followupCount',
            'scheduledTodayCount',
            'completedCount',
            'filter',
            'doctors',
            'recentOrders'
        ));
    }

    public function show(ConsultationRequest $consultation)
    {
        $doctors = DoctorProfile::where('is_active', true)->orderBy('sort_order')->get();
        return view('consultant.appointments.show', compact('consultation', 'doctors'));
    }

    public function generateZoom(Request $request, ConsultationRequest $consultation)
    {
        $meetingId = rand(810, 899) . ' ' . rand(1000, 9999) . ' ' . rand(100, 999);
        $rawId = str_replace(' ', '', $meetingId);
        $passcode = strtolower(Str::random(6));
        $zoomLink = "https://us05web.zoom.us/j/{$rawId}?pwd={$passcode}";

        $consultation->update([
            'zoom_link' => $zoomLink,
            'zoom_meeting_id' => $meetingId,
            'zoom_passcode' => $passcode,
            'video_status' => $consultation->video_status === 'completed' ? 'completed' : 'scheduled',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'zoom_link' => $zoomLink,
                'zoom_meeting_id' => $meetingId,
                'zoom_passcode' => $passcode,
                'message' => 'New Zoom video consultation meeting link generated successfully.',
            ]);
        }

        return back()->with('success', 'Zoom meeting link generated: ' . $zoomLink);
    }

    public function schedule(Request $request, ConsultationRequest $consultation)
    {
        $data = $request->validate([
            'scheduled_at' => 'nullable|date',
            'zoom_link' => 'nullable|url|max:500',
            'zoom_meeting_id' => 'nullable|string|max:100',
            'zoom_passcode' => 'nullable|string|max:50',
            'assigned_to' => 'nullable|string|max:255',
            'consultant_notes' => 'nullable|string',
            'video_status' => 'nullable|string|in:pending,scheduled,link_shared,in_progress,completed,cancelled',
            'is_order_followup' => 'nullable|boolean',
        ]);

        if (empty($data['zoom_link']) && !empty($request->auto_generate_zoom)) {
            $meetingId = rand(810, 899) . ' ' . rand(1000, 9999) . ' ' . rand(100, 999);
            $rawId = str_replace(' ', '', $meetingId);
            $passcode = strtolower(Str::random(6));
            $data['zoom_link'] = "https://us05web.zoom.us/j/{$rawId}?pwd={$passcode}";
            $data['zoom_meeting_id'] = $meetingId;
            $data['zoom_passcode'] = $passcode;
        }

        if (!empty($data['scheduled_at']) && empty($data['video_status'])) {
            $data['video_status'] = 'scheduled';
        }

        $consultation->update($data);

        return back()->with('success', 'Appointment schedule and video consultation details updated successfully.');
    }

    public function notifyCustomer(Request $request, ConsultationRequest $consultation)
    {
        $validated = $request->validate([
            'channel' => 'required|in:whatsapp,email,both',
            'custom_zoom_link' => 'nullable|string|max:500',
            'custom_time' => 'nullable|string|max:255',
            'additional_note' => 'nullable|string',
        ]);

        $channel = $validated['channel'];
        $customZoom = !empty($validated['custom_zoom_link']) ? trim($validated['custom_zoom_link']) : $consultation->zoom_link;

        if (empty($customZoom)) {
            // Auto-generate if not set
            $meetingId = rand(810, 899) . ' ' . rand(1000, 9999) . ' ' . rand(100, 999);
            $rawId = str_replace(' ', '', $meetingId);
            $passcode = strtolower(Str::random(6));
            $customZoom = "https://us05web.zoom.us/j/{$rawId}?pwd={$passcode}";

            $consultation->update([
                'zoom_link' => $customZoom,
                'zoom_meeting_id' => $meetingId,
                'zoom_passcode' => $passcode,
            ]);
        }

        $timeStr = !empty($validated['custom_time']) ? trim($validated['custom_time']) : null;

        // WhatsApp message & link
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $consultation->phone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        $waMessage = $consultation->buildCustomerZoomWhatsAppMessage($customZoom, $timeStr);
        $waUrl = !empty($cleanPhone) ? "https://api.whatsapp.com/send?phone={$cleanPhone}&text=" . rawurlencode($waMessage) : null;

        // Email message & link
        $emailSubject = $consultation->buildCustomerZoomEmailSubject();
        $emailBody = $consultation->buildCustomerZoomEmailBody($customZoom, $timeStr);
        $gmailUrl = !empty($consultation->email) ? "https://mail.google.com/mail/?view=cm&fs=1&to=" . rawurlencode($consultation->email) . "&su=" . rawurlencode($emailSubject) . "&body=" . rawurlencode($emailBody) : null;
        $mailtoUrl = !empty($consultation->email) ? "mailto:" . rawurlencode($consultation->email) . "?subject=" . rawurlencode($emailSubject) . "&body=" . rawurlencode($emailBody) : null;

        // Update consultation record
        $consultation->update([
            'customer_notified_at' => now(),
            'customer_notified_channel' => $channel,
            'video_status' => 'link_shared',
            'status' => 'sent',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Zoom video consultation dispatched to patient successfully.',
                'whatsapp_url' => $waUrl,
                'gmail_url' => $gmailUrl,
                'mailto_url' => $mailtoUrl,
                'zoom_link' => $customZoom,
                'channel' => $channel,
                'notified_at' => $consultation->customer_notified_at->format('d M Y, h:i A'),
            ]);
        }

        return back()->with([
            'success' => 'Customer notified of Zoom video consultation successfully.',
            'launch_whatsapp' => in_array($channel, ['whatsapp', 'both'], true) ? $waUrl : null,
            'launch_gmail' => in_array($channel, ['email', 'both'], true) ? $gmailUrl : null,
        ]);
    }

    public function complete(Request $request, ConsultationRequest $consultation)
    {
        $consultation->update([
            'video_status' => 'completed',
            'status' => 'completed',
            'consultant_notes' => trim(($consultation->consultant_notes ? $consultation->consultant_notes . "\n\n" : '') . '[Session Completed on ' . now()->format('d M Y, h:i A') . ']: ' . ($request->notes ?: 'Video consultation conducted successfully.')),
        ]);

        return back()->with('success', 'Consultation # ' . $consultation->reference_no . ' marked as completed.');
    }

    public function createOrderFollowup(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'patient_name' => 'required|string|max:255',
            'patient_email' => 'nullable|email|max:191',
            'patient_phone' => 'required|string|max:30',
            'scheduled_at' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $order = !empty($data['order_id']) ? Order::find($data['order_id']) : null;
        $orderNum = $order ? $order->order_num : 'DIRECT';

        $meetingId = rand(810, 899) . ' ' . rand(1000, 9999) . ' ' . rand(100, 999);
        $rawId = str_replace(' ', '', $meetingId);
        $passcode = strtolower(Str::random(6));
        $zoomLink = "https://us05web.zoom.us/j/{$rawId}?pwd={$passcode}";

        $appointment = ConsultationRequest::create([
            'reference_no' => 'ZOOM-ORD-' . strtoupper(Str::random(6)),
            'user_id' => $order?->user_id,
            'name' => $data['patient_name'],
            'email' => $data['patient_email'] ?? ($order?->user?->email),
            'phone' => $data['patient_phone'],
            'preferred_contact_method' => 'whatsapp',
            'concern' => "New Order Follow-up & Nutritional Orientation for Order #{$orderNum}. Patient connected via Zoom for tailored grocery usage advice.",
            'session_type' => 'follow_up',
            'session_number' => 1,
            'is_order_followup' => true,
            'order_id' => $order?->id,
            'zoom_link' => $zoomLink,
            'zoom_meeting_id' => $meetingId,
            'zoom_passcode' => $passcode,
            'scheduled_at' => $data['scheduled_at'] ?? now()->addDay()->setHour(17)->setMinute(0),
            'video_status' => 'scheduled',
            'assigned_to' => $data['assigned_to'] ?: 'Dr. Varsha Shah',
            'status' => 'new',
            'payment_status' => 'paid',
            'payment_amount' => 0.00,
            'consultant_notes' => $data['notes'] ?? 'Automated Zoom follow-up consultation created for new order customer.',
        ]);

        return redirect()->route('consultant.appointments', ['filter' => 'followup'])
            ->with('success', "New Order Follow-up appointment #{$appointment->reference_no} created with Zoom link: {$zoomLink}");
    }
}
