<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;

class ConsultationRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ConsultationRequest::query()->latest();

        if ($request->filled('status')) {
            if ($request->status === 'not_sent') {
                $query->whereIn('status', ['not_sent', 'new']);
            } elseif ($request->status === 'sent') {
                $query->whereIn('status', ['sent', 'assigned', 'contacted', 'consultation_scheduled', 'completed', 'closed']);
            } else {
                $query->where('status', $request->status);
            }
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('admin.consultations.index', compact('requests'));
    }

    public function show(ConsultationRequest $consultation)
    {
        return view('admin.consultations.show', compact('consultation'));
    }

    public function update(Request $request, ConsultationRequest $consultation)
    {
        $data = $request->validate([
            'status' => 'required|in:not_sent,sent,new,assigned',
            'assigned_to' => 'nullable|string|max:255',
            'doctor_phone' => 'nullable|string|max:30',
            'doctor_email' => 'nullable|email|max:191',
            'internal_note' => 'nullable|string',
        ]);

        $data['status'] = in_array($data['status'], ['new', 'not_sent'], true) ? 'not_sent' : 'sent';

        $consultation->update($data);

        return redirect()->route('admin.consultations.show', $consultation)->with('success', 'Consultation request updated successfully.');
    }

    public function sendToDoctor(Request $request, ConsultationRequest $consultation)
    {
        $validated = $request->validate([
            'channel' => 'required|in:whatsapp,gmail,both',
            'doctor_phone' => 'nullable|string|max:30',
            'doctor_email' => 'nullable|email|max:191',
            'assigned_to' => 'nullable|string|max:255',
            'custom_note' => 'nullable|string',
        ]);

        $channel = $validated['channel'];
        $doctorPhone = !empty($validated['doctor_phone']) ? trim($validated['doctor_phone']) : trim((string)$consultation->doctor_phone);
        $doctorEmail = !empty($validated['doctor_email']) ? trim($validated['doctor_email']) : trim((string)$consultation->doctor_email);
        $assignedTo = !empty($validated['assigned_to']) ? trim($validated['assigned_to']) : trim((string)$consultation->assigned_to);
        $customNote = !empty($validated['custom_note']) ? trim($validated['custom_note']) : null;

        if (in_array($channel, ['whatsapp', 'both'], true) && empty($doctorPhone)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Doctor phone number is required for WhatsApp forwarding.'], 422);
            }
            return back()->withErrors(['doctor_phone' => 'Doctor phone number is required for WhatsApp forwarding.']);
        }

        if (in_array($channel, ['gmail', 'both'], true) && empty($doctorEmail)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Doctor email address is required for Gmail forwarding.'], 422);
            }
            return back()->withErrors(['doctor_email' => 'Doctor email address is required for Gmail forwarding.']);
        }

        // Clean phone number for WhatsApp
        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$doctorPhone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone; // default India prefix for 10-digit mobile
        }

        $waMessage = $consultation->buildDoctorWhatsAppMessage($assignedTo, $customNote);
        $waUrl = "https://api.whatsapp.com/send?phone={$cleanPhone}&text=" . rawurlencode($waMessage);

        $emailSubject = $consultation->buildDoctorEmailSubject();
        $emailBody = $consultation->buildDoctorEmailBody($assignedTo, $customNote);
        $gmailUrl = "https://mail.google.com/mail/?view=cm&fs=1&to=" . rawurlencode((string)$doctorEmail) . "&su=" . rawurlencode($emailSubject) . "&body=" . rawurlencode($emailBody);
        $mailtoUrl = "mailto:" . rawurlencode((string)$doctorEmail) . "?subject=" . rawurlencode($emailSubject) . "&body=" . rawurlencode($emailBody);

        // Update consultation record
        $updateData = [
            'doctor_phone' => $doctorPhone ?: $consultation->doctor_phone,
            'doctor_email' => $doctorEmail ?: $consultation->doctor_email,
            'doctor_notified_at' => now(),
            'doctor_notified_channel' => $channel,
            'status' => 'sent',
        ];
        if (!empty($assignedTo)) {
            $updateData['assigned_to'] = $assignedTo;
        }
        if (!empty($customNote)) {
            $prefix = "[Forwarded to Dr. " . ($assignedTo ?: 'Consultant') . " on " . now()->format('d M Y, h:i A') . " via " . strtoupper($channel) . "]: ";
            $updateData['internal_note'] = trim(($consultation->internal_note ? $consultation->internal_note . "\n\n" : '') . $prefix . $customNote);
        }
        $consultation->update($updateData);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Consultation inquiry forwarded to doctor successfully.',
                'whatsapp_url' => $waUrl,
                'gmail_url' => $gmailUrl,
                'mailto_url' => $mailtoUrl,
                'channel' => $channel,
                'status' => $consultation->status,
                'doctor_phone' => $doctorPhone,
                'doctor_email' => $doctorEmail,
                'doctor_notified_at' => $consultation->doctor_notified_at?->format('d M Y, h:i A'),
            ]);
        }

        return redirect()->route('admin.consultations.show', $consultation)->with([
            'success' => 'Inquiry forwarded to doctor successfully.',
            'launch_whatsapp' => in_array($channel, ['whatsapp', 'both'], true) ? $waUrl : null,
            'launch_gmail' => in_array($channel, ['gmail', 'both'], true) ? $gmailUrl : null,
        ]);
    }
}
