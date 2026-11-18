<?php

namespace App\Notifications;

use App\Models\ConsultationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConsultationStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ConsultationRequest $consultation,
        public string $eventType, // 'booked', 'scheduled', 'completed'
        public string $targetRole = 'customer' // 'customer', 'admin', 'doctor'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $docName = $this->consultation->doctorProfile?->name 
            ?: ($this->consultation->assigned_to ?: 'Lifestyle Medicine Specialist');
        $timeStr = $this->consultation->scheduled_at 
            ? $this->consultation->scheduled_at->format('d M Y, h:i A (IST)') 
            : 'Scheduled slot';
        $zoomLink = $this->consultation->zoom_link ?: '';
        $ref = $this->consultation->reference_no;
        $patientName = $this->consultation->name;
        $visitLabel = $this->consultation->visit_badge_label;

        if ($this->eventType === 'booked') {
            if ($this->targetRole === 'customer') {
                return [
                    'title' => '🌿 Consultation Booking Confirmed',
                    'message' => "Namaste {$patientName}! Your consultation booking (#{$ref}) has been received. Our health desk is assigning your specialist and scheduling your appointment time.",
                    'level' => 'success',
                    'action_url' => '/#account',
                    'metadata' => [
                        'consultation_id' => $this->consultation->id,
                        'reference_no' => $ref,
                        'event_type' => 'booked',
                        'visit_label' => $visitLabel,
                    ],
                ];
            } else {
                return [
                    'title' => $this->consultation->is_returning_patient ? "🔁 2nd Time Patient (#{$this->consultation->visit_number})" : "🌱 New Patient Consultation",
                    'message' => "Patient {$patientName} booked {$visitLabel} (Ref #{$ref}). Amount: ₹" . number_format((float) ($this->consultation->payment_amount ?: 1500), 0) . " PAID.",
                    'level' => $this->consultation->is_returning_patient ? 'info' : 'success',
                    'action_url' => route('admin.consultations.show', $this->consultation),
                    'metadata' => [
                        'consultation_id' => $this->consultation->id,
                        'reference_no' => $ref,
                        'event_type' => 'booked',
                        'visit_number' => $this->consultation->visit_number,
                    ],
                ];
            }
        } elseif ($this->eventType === 'scheduled') {
            if ($this->targetRole === 'customer') {
                return [
                    'title' => '📹 Video Consultation Appointment Scheduled',
                    'message' => "You have a meeting with Dr. {$docName} on {$timeStr}." . (!empty($zoomLink) ? " Zoom link is ready in your account." : ""),
                    'level' => 'primary',
                    'action_url' => '/#account',
                    'metadata' => [
                        'consultation_id' => $this->consultation->id,
                        'reference_no' => $ref,
                        'scheduled_at' => $timeStr,
                        'zoom_link' => $zoomLink,
                        'doctor_name' => $docName,
                        'event_type' => 'scheduled',
                    ],
                ];
            } else {
                return [
                    'title' => '📅 Consultation Scheduled & Dispatched',
                    'message' => "Consultation for {$patientName} (Ref #{$ref}) scheduled for {$timeStr} with Dr. {$docName}. Meeting link sent to Customer WhatsApp.",
                    'level' => 'info',
                    'action_url' => route('admin.consultations.show', $this->consultation),
                    'metadata' => [
                        'consultation_id' => $this->consultation->id,
                        'reference_no' => $ref,
                        'scheduled_at' => $timeStr,
                        'doctor_name' => $docName,
                        'event_type' => 'scheduled',
                    ],
                ];
            }
        } elseif ($this->eventType === 'completed') {
            if ($this->targetRole === 'customer') {
                return [
                    'title' => '✅ Consultation Completed',
                    'message' => "Your consultation session with Dr. {$docName} is completed. Doctor's dietary advice & clinical notes are now available in your account dashboard.",
                    'level' => 'success',
                    'action_url' => '/#account',
                    'metadata' => [
                        'consultation_id' => $this->consultation->id,
                        'reference_no' => $ref,
                        'doctor_name' => $docName,
                        'event_type' => 'completed',
                    ],
                ];
            } else {
                return [
                    'title' => '🩺 Consultation Completed',
                    'message' => "Dr. {$docName} marked consultation for patient {$patientName} (Ref #{$ref}) as completed.",
                    'level' => 'success',
                    'action_url' => route('admin.consultations.show', $this->consultation),
                    'metadata' => [
                        'consultation_id' => $this->consultation->id,
                        'reference_no' => $ref,
                        'doctor_name' => $docName,
                        'event_type' => 'completed',
                    ],
                ];
            }
        }

        return [
            'title' => "Consultation #{$ref} Update",
            'message' => "Status updated for consultation #{$ref}.",
            'level' => 'secondary',
            'action_url' => '/#account',
        ];
    }
}
