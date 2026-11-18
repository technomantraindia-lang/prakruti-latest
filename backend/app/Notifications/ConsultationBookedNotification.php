<?php

namespace App\Notifications;

use App\Models\ConsultationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConsultationBookedNotification extends Notification
{
    use Queueable;

    public function __construct(public ConsultationRequest $consultation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $visitLabel = $this->consultation->visit_badge_label;
        $isReturning = $this->consultation->is_returning_patient;

        return [
            'title' => $isReturning ? "🔁 2nd Time Patient (#{$this->consultation->visit_number})" : "🌱 New Patient Consultation",
            'message' => "Patient {$this->consultation->name} booked {$visitLabel} (Ref #{$this->consultation->reference_no}). Amount: ₹" . number_format((float) ($this->consultation->payment_amount ?: 1500), 0) . " PAID.",
            'level' => $isReturning ? 'info' : 'success',
            'action_url' => route('admin.consultations.show', $this->consultation),
            'metadata' => [
                'consultation_id' => $this->consultation->id,
                'reference_no' => $this->consultation->reference_no,
                'patient_name' => $this->consultation->name,
                'visit_number' => $this->consultation->visit_number,
                'session_type' => $this->consultation->session_type,
            ],
        ];
    }
}
