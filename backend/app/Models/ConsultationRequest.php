<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationRequest extends Model
{
    protected $fillable = [
        'reference_no',
        'user_id',
        'name',
        'email',
        'phone',
        'preferred_contact_method',
        'concern',
        'age_range',
        'dietary_preference',
        'preferred_time',
        'consent',
        'session_type',
        'session_number',
        'payment_status',
        'payment_method',
        'payment_gateway',
        'payment_transaction_id',
        'payment_amount',
        'payment_paid_at',
        'status',
        'admin_approved',
        'admin_approved_at',
        'admin_approved_by',
        'internal_note',
        'assigned_to',
        'doctor_profile_id',
        'doctor_phone',
        'doctor_email',
        'doctor_notified_at',
        'doctor_notified_channel',
        'zoom_link',
        'zoom_meeting_id',
        'zoom_passcode',
        'scheduled_at',
        'video_status',
        'customer_notified_at',
        'customer_notified_channel',
        'customer_whatsapp_sent_at',
        'doctor_whatsapp_sent_at',
        'is_order_followup',
        'order_id',
        'consultant_notes',
        'doctor_notes',
    ];

    protected function casts(): array
    {
        return [
            'consent' => 'boolean',
            'admin_approved' => 'boolean',
            'admin_approved_at' => 'datetime',
            'session_number' => 'integer',
            'payment_amount' => 'decimal:2',
            'payment_paid_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'doctor_notified_at' => 'datetime',
            'customer_notified_at' => 'datetime',
            'customer_whatsapp_sent_at' => 'datetime',
            'doctor_whatsapp_sent_at' => 'datetime',
            'is_order_followup' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function adminApprover()
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    /**
     * Calculate whether this consultation is 1st time, 2nd time, etc.
     */
    public function getVisitNumberAttribute(): int
    {
        if ($this->session_number && $this->session_number > 0) {
            return (int) $this->session_number;
        }

        $query = static::where('id', '<=', $this->id);
        if ($this->user_id) {
            $query->where('user_id', $this->user_id);
        } elseif (!empty($this->phone)) {
            $digits = substr(preg_replace('/[^0-9]/', '', (string)$this->phone), -10);
            $query->where('phone', 'like', "%{$digits}%");
        } elseif (!empty($this->email)) {
            $query->where('email', strtolower(trim($this->email)));
        }

        $count = $query->count();
        if ($count <= 1 && ($this->session_type === 'follow_up' || $this->is_order_followup)) {
            return 2;
        }

        return max(1, $count);
    }

    /**
     * Check if this patient is returning (2nd time or more)
     */
    public function getIsReturningPatientAttribute(): bool
    {
        return $this->visit_number > 1 || $this->session_type === 'follow_up' || (bool) $this->is_order_followup;
    }

    /**
     * Human-friendly label for visit number
     */
    public function getVisitBadgeLabelAttribute(): string
    {
        $num = $this->visit_number;
        if ($num === 1 && $this->session_type !== 'follow_up' && !$this->is_order_followup) {
            return '1st Time Patient (Initial Visit)';
        } elseif ($num === 2 || $this->session_type === 'follow_up') {
            return '2nd Time Patient (Follow-up #2)';
        } elseif ($num === 3) {
            return '3rd Time Patient (Follow-up #3)';
        } else {
            return "{$num}th Time Patient (Follow-up #{$num})";
        }
    }

    /**
     * Get list of previous consultations for this patient
     */
    public function getPreviousConsultationsAttribute()
    {
        $query = static::where('id', '!=', $this->id)->latest('id');
        if ($this->user_id) {
            $query->where('user_id', $this->user_id);
        } elseif (!empty($this->phone)) {
            $digits = substr(preg_replace('/[^0-9]/', '', (string)$this->phone), -10);
            $query->where('phone', 'like', "%{$digits}%");
        } elseif (!empty($this->email)) {
            $query->where('email', strtolower(trim($this->email)));
        }

        return $query->limit(5)->get();
    }

    /**
     * Clean patient medical concern without any internal transaction or payment metadata.
     */
    public function getCleanConcernAttribute(): string
    {
        $text = (string) $this->concern;
        // Strip internal tags like [Booking: ...], [PATIENT VISIT ...], [Payment: ...], [Transaction: ...]
        $text = preg_replace('/\[Booking:[^\]]*\]/i', '', $text);
        $text = preg_replace('/\[PATIENT VISIT[^\]]*\]/i', '', $text);
        $text = preg_replace('/\[Payment:[^\]]*\]/i', '', $text);
        $text = preg_replace('/\[Transaction:[^\]]*\]/i', '', $text);
        $text = preg_replace('/\[Consultation:[^\]]*\]/i', '', $text);
        $text = preg_replace('/\[Focus Areas:[^\]]*\]/i', '', $text);
        $text = trim(preg_replace('/\n{3,}/', "\n\n", $text));

        return $text !== '' ? $text : 'Nutritional & Lifestyle Consultation';
    }

    /**
     * Selected Focus Areas & Health Goals for doctor review.
     */
    public function getClinicalFocusAreasAttribute(): array
    {
        if (preg_match('/\[Focus Areas:\s*([^\]]+)\]/i', (string) $this->concern, $matches)) {
            return array_values(array_filter(array_map('trim', explode(',', $matches[1]))));
        }
        return [];
    }

    /**
     * Selected Consultation Specialist type without price tags.
     */
    public function getClinicalConsultationTypeAttribute(): string
    {
        if (preg_match('/\[Consultation:\s*([^\]]+)\]/i', (string) $this->concern, $matches)) {
            return trim(preg_replace('/\([^)]*\)/', '', $matches[1]));
        }
        return 'Lifestyle Medicine Consultation';
    }

    /**
     * Clean, properly prefixed doctor display name (guarantees exactly one 'Dr.').
     */
    public function getDoctorDisplayNameAttribute(): string
    {
        $name = $this->doctorProfile?->name ?: ($this->assigned_to ?: 'Lifestyle Medicine Doctor');
        $clean = preg_replace('/^Dr\.?\s+/i', '', trim((string) $name));
        return 'Dr. ' . $clean;
    }

    /**
     * Patient chief concern formatted cleanly for clinical display.
     */
    public function getPatientDisplayConcernAttribute(): string
    {
        $clean = $this->clean_concern;
        if (in_array(strtolower($clean), ['etc', 'na', 'none', 'n/a', 'no', 'test', 'testing', 'tsting this'], true)) {
            return $clean . ' (General health check & lifestyle counseling)';
        }
        return $clean;
    }

    /**
     * Direct WhatsApp URL for customer appointment notification.
     */
    public function getCustomerWhatsappUrlAttribute(): ?string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$this->phone);
        if (str_starts_with($cleanPhone, '0') && strlen($cleanPhone) === 11) {
            $cleanPhone = '91' . substr($cleanPhone, 1);
        } elseif (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        if (empty($cleanPhone)) {
            return null;
        }

        return "https://web.whatsapp.com/send?phone={$cleanPhone}&text=" . rawurlencode($this->buildCustomerAppointmentWhatsAppMessage());
    }

    /**
     * Build Customer WhatsApp message with scheduled appointment time & meeting link.
     */
    public function buildCustomerAppointmentWhatsAppMessage(?string $customZoom = null, ?string $customTime = null): string
    {
        $zoomLink = $customZoom ?: ($this->zoom_link ?: '');
        $timeStr = $customTime ?: ($this->scheduled_at ? $this->scheduled_at->format('d M Y, h:i A') . ' (IST)' : 'As scheduled by care team');
        $sessionName = $this->session_type === 'follow_up' ? 'Follow-up Consultation' : 'Lifestyle Medicine Consultation';
        $doctorName = $this->doctor_display_name;

        $msg = "🌿 *PRAKRUTI LIFESTYLE MEDICINE - CONSULTATION MEETING* 📹\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Namaste *{$this->name}*!\n\n";
        $msg .= "You have a meeting with the doctor *{$doctorName}*.\n\n";
        $msg .= "📅 *Appointment Date & Time:* {$timeStr}\n";
        if (!empty($zoomLink)) {
            $msg .= "🔗 *Zoom / Video Call Link:*\n{$zoomLink}\n\n";
        }
        $msg .= "📋 *Session Details:*\n";
        $msg .= "• *Reference No:* #{$this->reference_no}\n";
        $msg .= "• *Doctor:* {$doctorName}\n";
        $msg .= "• *Session:* {$sessionName}\n";
        $msg .= "• *Payment:* PAID (₹" . number_format((float) ($this->payment_amount ?: 1500), 0) . ")\n\n";
        $msg .= "🌐 *Access from Customer Dashboard:*\n";
        $msg .= "You can also join directly by logging in to your account at http://127.0.0.1:5174/#account\n\n";
        $msg .= "💡 *Instructions:*\n";
        $msg .= "1. Please click the link 5 minutes before your scheduled time.\n";
        $msg .= "2. Make sure you are in a quiet room with stable internet connection.\n";
        $msg .= "3. Keep any recent medical or nutrition reports handy for the doctor.\n\n";
        $msg .= "Need help or need to reschedule?\n";
        $msg .= "📞 Call/WhatsApp: +91 92746 83460 (Prakruti Care)\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Looking forward to your healthy wellness transformation!\n";
        $msg .= "- Prakruti Lifestyle Medicine Team";

        return $msg;
    }

    /**
     * Build Doctor WhatsApp notification message with scheduled appointment time & meeting link.
     */
    public function buildDoctorAppointmentWhatsAppMessage(?string $customZoom = null, ?string $customTime = null): string
    {
        $zoomLink = $customZoom ?: ($this->zoom_link ?: '');
        $timeStr = $customTime ?: ($this->scheduled_at ? $this->scheduled_at->format('d M Y, h:i A') . ' (IST)' : 'As scheduled by admin');
        $sessionName = $this->session_type === 'follow_up' ? 'Follow-up Session' : 'New Consultation';

        $msg = "🩺 *PRAKRUTI DOCTOR PORTAL - APPROVED APPOINTMENT* 📹\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Dear Doctor,\n\n";
        $msg .= "An appointment has been approved and scheduled by Admin:\n\n";
        $msg .= "👤 *Patient:* {$this->name}\n";
        $msg .= "📱 *Mobile:* " . ($this->phone ?: 'Not provided') . "\n";
        $msg .= "📅 *Appointment Time:* {$timeStr}\n";
        $msg .= "📋 *Session Type:* {$sessionName}\n";
        if (!empty($zoomLink)) {
            $msg .= "🔗 *Meeting Link:* {$zoomLink}\n";
        }
        $msg .= "\n🩺 *Chief Complaint / Health Goals:*\n";
        $msg .= "{$this->concern}\n\n";
        $msg .= "This patient is now live in your Doctor Panel:\n";
        $msg .= "👉 http://127.0.0.1:8000/doctor/appointments\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "- Prakruti Admin Desk";

        return $msg;
    }

    /**
     * Build Customer Email subject.
     */
    public function buildCustomerAppointmentEmailSubject(): string
    {
        $timeStr = $this->scheduled_at ? $this->scheduled_at->format('d M, h:i A') : 'Scheduled Slot';
        return "🌿 Your Consultation Appointment Time: {$timeStr} [Ref #{$this->reference_no}]";
    }

    /**
     * Build Customer Email body.
     */
    public function buildCustomerAppointmentEmailBody(?string $customZoom = null, ?string $customTime = null): string
    {
        $zoomLink = $customZoom ?: ($this->zoom_link ?: '');
        $timeStr = $customTime ?: ($this->scheduled_at ? $this->scheduled_at->format('d M Y, h:i A') . ' (IST)' : 'As scheduled by care team');

        $body = "Dear {$this->name},\n\n";
        $body .= "Namaste! This is the appointment time for your Lifestyle Medicine consultation meeting with our doctor.\n\n";
        $body .= "=== CONSULTATION DETAILS ===\n";
        $body .= "Booking Reference: #{$this->reference_no}\n";
        $body .= "Appointment Date & Time: {$timeStr}\n";
        if (!empty($zoomLink)) {
            $body .= "Video Meeting Link: {$zoomLink}\n";
        }
        $body .= "\nYou can also join the consultation directly from your Prakruti Customer Account Dashboard at any time.\n\n";
        $body .= "=== GUIDELINES ===\n";
        $body .= "1. Please be ready 5 minutes prior to your scheduled time.\n";
        $body .= "2. Have any recent medical or nutrition reports ready for review.\n";
        $body .= "3. Ensure you have stable audio and video connection.\n\n";
        $body .= "If you have any questions, reach us on WhatsApp at +91 92746 83460 or reply to this email.\n\n";
        $body .= "Warm regards,\n";
        $body .= "Prakruti Lifestyle Medicine Team\n";
        $body .= "support@prakruticorp.org | +91 92746 83460";

        return $body;
    }
}
