<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationRequest extends Model
{
    protected $fillable = [
        'reference_no',
        'name',
        'email',
        'phone',
        'preferred_contact_method',
        'concern',
        'age_range',
        'dietary_preference',
        'preferred_time',
        'consent',
        'status',
        'internal_note',
        'assigned_to',
        'doctor_phone',
        'doctor_email',
        'doctor_notified_at',
        'doctor_notified_channel',
    ];

    protected function casts(): array
    {
        return [
            'consent' => 'boolean',
            'doctor_notified_at' => 'datetime',
        ];
    }

    /**
     * Build formatted message for Doctor WhatsApp.
     */
    public function buildDoctorWhatsAppMessage(?string $assignedDoctor = null, ?string $additionalNote = null): string
    {
        $doc = $assignedDoctor ?: ($this->assigned_to ?: 'Doctor');
        $date = $this->created_at ? $this->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : date('d M Y, h:i A');

        $msg = "🌿 *PRAKRUTI CORPORATION - NEW CONSULTATION INQUIRY*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Dear Dr. {$doc},\n";
        $msg .= "A new patient consultation inquiry has been forwarded to you.\n\n";
        $msg .= "📋 *Inquiry Details:*\n";
        $msg .= "• *Reference No:* #{$this->reference_no}\n";
        $msg .= "• *Patient Name:* {$this->name}\n";
        $msg .= "• *Mobile:* " . ($this->phone ?: 'Not provided') . "\n";
        $msg .= "• *Email:* " . ($this->email ?: 'Not provided') . "\n";
        $msg .= "• *Age Range:* " . ($this->age_range ?: 'Not specified') . "\n";
        $msg .= "• *Dietary Pref:* " . ($this->dietary_preference ?: 'Standard') . "\n";
        $msg .= "• *Preferred Time:* " . ($this->preferred_time ?: 'Flexible') . "\n";
        $msg .= "• *Contact Channel:* " . ucfirst($this->preferred_contact_method) . "\n";
        $msg .= "• *Received At:* {$date} (IST)\n\n";
        $msg .= "🩺 *Health Concern / Symptoms:*\n";
        $msg .= "{$this->concern}\n";

        if ($additionalNote || $this->internal_note) {
            $note = $additionalNote ?: $this->internal_note;
            $msg .= "\n📝 *Admin / Triage Note:*\n{$note}\n";
        }

        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Please review this case and initiate consultation. Thank you!\n";
        $msg .= "- Prakruti Corporation Care Team";

        return $msg;
    }

    /**
     * Build formatted subject for Doctor Email / Gmail.
     */
    public function buildDoctorEmailSubject(): string
    {
        return "New Consultation Inquiry [Ref #{$this->reference_no}] - Patient: {$this->name}";
    }

    /**
     * Build formatted body for Doctor Email / Gmail.
     */
    public function buildDoctorEmailBody(?string $assignedDoctor = null, ?string $additionalNote = null): string
    {
        $doc = $assignedDoctor ?: ($this->assigned_to ?: 'Doctor');
        $date = $this->created_at ? $this->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : date('d M Y, h:i A');

        $body = "Dear Dr. {$doc},\n\n";
        $body .= "A new patient consultation inquiry has been received through the Prakruti Corporation portal and assigned to you.\n\n";
        $body .= "=== PATIENT & INQUIRY SUMMARY ===\n";
        $body .= "Reference Number: #{$this->reference_no}\n";
        $body .= "Patient Name: {$this->name}\n";
        $body .= "Contact Mobile: " . ($this->phone ?: 'Not provided') . "\n";
        $body .= "Email Address: " . ($this->email ?: 'Not provided') . "\n";
        $body .= "Age Range: " . ($this->age_range ?: 'Not specified') . "\n";
        $body .= "Dietary Preference: " . ($this->dietary_preference ?: 'Standard') . "\n";
        $body .= "Preferred Consultation Time: " . ($this->preferred_time ?: 'Flexible') . "\n";
        $body .= "Preferred Mode of Contact: " . ucfirst($this->preferred_contact_method) . "\n";
        $body .= "Submission Date & Time: {$date} (IST)\n\n";
        $body .= "=== HEALTH CONCERNS & SYMPTOMS ===\n";
        $body .= "{$this->concern}\n\n";

        if ($additionalNote || $this->internal_note) {
            $note = $additionalNote ?: $this->internal_note;
            $body .= "=== INTERNAL TRIAGE / ADMIN NOTE ===\n";
            $body .= "{$note}\n\n";
        }

        $body .= "Please reach out to the patient or schedule the consultation at your earliest convenience.\n\n";
        $body .= "Warm regards,\n";
        $body .= "Prakruti Corporation Operations Team\n";
        $body .= "care@prakruticorporation.com | +91 79 2583 0000";

        return $body;
    }
}
