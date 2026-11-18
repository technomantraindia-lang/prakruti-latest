@extends('admin.layouts.app')

@section('title', 'Consultation #' . $consultation->reference_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1 fw-bold text-dark">Consultation #{{ $consultation->reference_no }}</h2>
        <div class="text-muted small">
            Patient: <strong>{{ $consultation->name }}</strong> &bull;
            Doctor Panel Status:
            @if($consultation->admin_approved)
                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Live in Doctor Panel</span>
            @else
                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Awaiting Doctor Assignment</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="POST" action="{{ route('admin.consultations.destroy', $consultation) }}" onsubmit="return confirm('Permanently delete consultation #{{ $consultation->reference_no }}? This action cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="fas fa-trash-alt me-1"></i> Delete
            </button>
        </form>
        <a href="{{ route('admin.consultations.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

@if(isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="fas fa-exclamation-triangle fs-5 text-danger mt-1"></i>
            <div>
                <strong>Please check the form:</strong>
                <ul class="mb-0 mt-1 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('launch_whatsapp') || session('launch_gmail'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="background: #e8f5e9; border-left: 5px solid #2e7d32 !important;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h6 class="fw-bold mb-1 text-success">
                    <i class="fab fa-whatsapp me-1 fs-5"></i> Customer WhatsApp Ready to Send!
                </h6>
                <div class="small text-muted">
                    Click the green button below to send the meeting link directly to <strong>{{ $consultation->name }} ({{ $consultation->phone }})</strong>:
                </div>
                @if(session('whatsapp_message'))
                    <div class="p-2 mt-2 bg-white rounded border small text-dark font-monospace" style="white-space: pre-line; max-height: 120px; overflow-y: auto;">
                        {{ session('whatsapp_message') }}
                    </div>
                @endif
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if(session('launch_whatsapp'))
                    <a href="{{ session('launch_whatsapp') }}" target="_blank" class="btn btn-success fw-bold shadow-sm px-3 py-2">
                        <i class="fab fa-whatsapp me-1 fs-5"></i> Open WhatsApp Web Now
                    </a>
                @endif
                @if(session('whatsapp_message'))
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(`{{ addslashes(session('whatsapp_message') ?? '') }}`); alert('WhatsApp message copied to clipboard!');">
                        <i class="fas fa-copy me-1"></i> Copy Message
                    </button>
                @endif
                @if(session('launch_gmail'))
                    <a href="{{ session('launch_gmail') }}" target="_blank" class="btn btn-sm btn-outline-danger fw-bold">
                        <i class="fab fa-google me-1"></i> Open Gmail
                    </a>
                @endif
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <!-- Left Column: Patient Profile & Health Concern -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6 text-dark"><i class="fas fa-user-circle text-primary me-2"></i>Patient Profile</span>
                <span class="badge bg-light text-dark border font-monospace">Ref #{{ $consultation->reference_no }}</span>
            </div>
            <div class="card-body">
                <dl class="row mb-0 gy-2">
                    <dt class="col-sm-4 text-muted">Patient Name</dt>
                    <dd class="col-sm-8 fw-bold fs-6 text-dark">{{ $consultation->name }}</dd>

                    <dt class="col-sm-4 text-muted">Patient Visit</dt>
                    <dd class="col-sm-8">
                        @if($consultation->visit_number > 1 || $consultation->is_returning_patient)
                            <span class="badge text-white fw-bold px-2.5 py-1.5 shadow-sm" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7) !important;">
                                <i class="fas fa-redo me-1"></i> 2nd Time Patient (Follow-up #{{ max(2, $consultation->visit_number) }})
                            </span>
                        @else
                            <span class="badge bg-success text-white fw-bold px-2.5 py-1.5">
                                <i class="fas fa-seedling me-1"></i> 1st Time Patient (Initial Visit)
                            </span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Payment Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>PAID</span>
                        <span class="fw-bold text-dark ms-1">₹{{ number_format((float) ($consultation->payment_amount ?: 1500), 2) }}</span>
                    </dd>

                    <dt class="col-sm-4 text-muted">Mobile Number</dt>
                    <dd class="col-sm-8">
                        @if($consultation->phone)
                            <a href="tel:{{ $consultation->phone }}" class="text-decoration-none fw-bold text-primary">
                                <i class="fas fa-phone-alt me-1"></i>{{ $consultation->phone }}
                            </a>
                            @if($consultation->customer_whatsapp_url)
                                <a href="{{ $consultation->customer_whatsapp_url }}" target="_blank" class="btn btn-xs btn-outline-success ms-2 py-0 px-2 fw-semibold" title="Chat on WhatsApp">
                                    <i class="fab fa-whatsapp me-1"></i>WhatsApp
                                </a>
                            @endif
                        @else
                            <span class="text-muted">Not provided</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Email Address</dt>
                    <dd class="col-sm-8">{{ $consultation->email ?: 'Not provided' }}</dd>

                    <dt class="col-sm-4 text-muted">Age / Diet</dt>
                    <dd class="col-sm-8">{{ $consultation->age_range ?: 'N/A' }} &bull; {{ ucfirst($consultation->dietary_preference ?: 'Standard') }}</dd>

                    <dt class="col-sm-4 text-muted">Preferred Time</dt>
                    <dd class="col-sm-8">{{ $consultation->preferred_time ?: 'Flexible' }}</dd>

                    <dt class="col-sm-4 text-muted">Submission Date</dt>
                    <dd class="col-sm-8 text-muted small">{{ $consultation->created_at?->format('d M Y, h:i A') }}</dd>
                </dl>
            </div>
        </div>

        <!-- Health Concern & Goals Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold fs-6 text-danger"><i class="fas fa-notes-medical me-2"></i>Patient Health Goals &amp; Concerns</span>
                <span class="badge bg-light text-secondary border small">{{ $consultation->clinical_consultation_type }}</span>
            </div>
            <div class="card-body">
                @if(count($consultation->clinical_focus_areas) > 0)
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">Focus Areas &amp; Wellness Goals:</label>
                        <div class="d-flex flex-wrap gap-1.5">
                            @foreach($consultation->clinical_focus_areas as $area)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fw-bold">
                                    <i class="fas fa-check-circle me-1"></i>{{ $area }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <label class="form-label small fw-bold text-muted mb-1">Chief Health Complaint / Symptoms:</label>
                    <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; line-height: 1.6;">{{ $consultation->patient_display_concern }}</div>
                </div>
            </div>
        </div>

        @if($consultation->doctor_notes)
            <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #198754 !important;">
                <div class="card-header bg-white py-3 border-bottom">
                    <span class="fw-bold fs-6 text-success"><i class="fas fa-file-medical-alt me-2"></i>Doctor Clinical Notes &amp; Prescription</span>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; line-height: 1.6;">{{ $consultation->doctor_notes }}</div>
                </div>
            </div>
        @endif

        @if($consultation->previous_consultations && $consultation->previous_consultations->count() > 0)
            <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #0d6efd !important;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-6 text-primary"><i class="fas fa-history me-2"></i>Patient History ({{ $consultation->previous_consultations->count() }} Past Consultations)</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($consultation->previous_consultations as $past)
                            <li class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <strong class="font-monospace text-dark">#{{ $past->reference_no }}</strong>
                                    <span class="badge bg-light text-muted border">{{ $past->created_at?->format('d M Y') }}</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    Doctor: <strong>{{ $past->doctor_display_name }}</strong>
                                    &bull; Status: <span class="badge bg-{{ $past->video_status === 'completed' ? 'success' : 'secondary' }}">{{ ucfirst($past->video_status ?: 'Booked') }}</span>
                                </div>
                                @if($past->doctor_notes)
                                    <div class="p-2 bg-light rounded mt-2 small text-dark">
                                        <i class="fas fa-notes-medical me-1 text-success"></i><strong>Doctor Notes:</strong> {{ $past->doctor_notes }}
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    <!-- Right Column: Doctor Assignment, Zoom Link, Customer WhatsApp -->
    <div class="col-lg-6">
        <!-- Unified Appointment Management Card -->
        <div class="card shadow-sm border-0 mb-4" style="border-top: 4px solid #198754 !important;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6 text-success">
                    <i class="fas fa-calendar-check me-2"></i>Doctor Assignment &amp; Meeting Schedule
                </span>
                @if($consultation->admin_approved)
                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>In Doctor Panel</span>
                @else
                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Awaiting Assignment</span>
                @endif
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Choose the doctor, specify the consultation date &amp; time, paste the Zoom meeting link, and click <strong>Save &amp; Send to WhatsApp</strong>.
                </p>

                <form method="POST" action="{{ route('admin.consultations.scheduleCustomer', $consultation) }}" id="mainScheduleForm">
                    @csrf
                    <input type="hidden" name="auto_approve" value="1">
                    <input type="hidden" name="channel" value="whatsapp">

                    <!-- 1. Select Registered Doctor -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">1. Select Registered Doctor *</label>
                        <select name="doctor_profile_id" id="detailDoctorSelect" class="form-select" required onchange="updateDetailPreview()">
                            <option value="" disabled {{ !$consultation->doctor_profile_id ? 'selected' : '' }}>-- Select Doctor --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" {{ $consultation->doctor_profile_id == $doc->id ? 'selected' : '' }} data-name="{{ $doc->formatted_name }}">
                                    {{ $doc->formatted_name }} ({{ $doc->specialization ?: 'Lifestyle Medicine' }}) - {{ $doc->phone }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">The patient case and Zoom meeting will appear directly in this doctor's workspace.</small>
                    </div>

                    <!-- 2. Appointment Date & Time -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">2. Appointment Date &amp; Time (IST) *</label>
                        <input type="datetime-local" name="scheduled_at" id="detailScheduledAt" class="form-control" value="{{ $consultation->scheduled_at ? $consultation->scheduled_at->format('Y-m-d\TH:i') : now()->addDay()->format('Y-m-d\T17:00') }}" required onchange="updateDetailPreview()">
                        <small class="text-muted">This slot is shared with both the Doctor and Customer on WhatsApp.</small>
                    </div>

                    <!-- 3. Paste Zoom Meeting Link -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">3. Paste Zoom Meeting Link *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white"><i class="fas fa-video"></i></span>
                            <input type="url" name="zoom_link" id="detailZoomLink" class="form-control" value="{{ $consultation->zoom_link }}" placeholder="https://us05web.zoom.us/j/... or Google Meet" required oninput="updateDetailPreview()">
                            @if($consultation->zoom_link)
                                <a href="{{ $consultation->zoom_link }}" target="_blank" class="btn btn-outline-primary" title="Test / Open Meeting Link">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            @endif
                        </div>
                        <small class="text-muted">Create the meeting link in your Zoom/Meet app and paste it here.</small>
                    </div>

                    <!-- 4. WhatsApp Message Preview Box -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-success d-flex align-items-center justify-content-between">
                            <span><i class="fab fa-whatsapp me-1"></i>WhatsApp Message Preview:</span>
                            <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.72rem;">Live Auto-Generated</span>
                        </label>
                        <div class="p-3 bg-light rounded border small text-dark font-monospace" id="detailPreviewBox" style="white-space: pre-line; line-height: 1.45; max-height: 160px; overflow-y: auto;">{{ $consultation->buildCustomerAppointmentWhatsAppMessage() }}</div>
                    </div>

                    <!-- 2 Distinct Action Buttons requested by user: Save Details and Open Web WhatsApp -->
                    <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                            <button type="submit" name="action_type" value="save_only" class="btn btn-primary fw-bold w-100 py-2.5 shadow-sm">
                                <i class="fas fa-save me-1.5"></i> Save Details
                            </button>
                        </div>
                        <div class="col-sm-6">
                            <button type="button" id="btnOpenWebWhatsApp" class="btn btn-success fw-bold w-100 py-2.5 shadow-sm" onclick="submitAndOpenWebWhatsApp()">
                                <i class="fab fa-whatsapp me-1.5 fs-5"></i> Open Web WhatsApp
                            </button>
                        </div>
                    </div>
                </form>

                <div class="border-top mt-3 pt-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="small">
                            <span class="text-muted">Assigned Doctor:</span>
                            <strong class="text-dark">{{ $consultation->doctor_display_name }}</strong>
                            @if($consultation->admin_approved_at)
                                <span class="text-muted" style="font-size: 0.75rem;">(sent {{ $consultation->admin_approved_at->format('d M, h:i A') }})</span>
                            @endif
                        </div>

                        @if($consultation->admin_approved)
                            <form method="POST" action="{{ route('admin.consultations.toggleApproval', $consultation) }}" class="d-inline" onsubmit="return confirm('Revoke this patient from Doctor Panel?');">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0 text-decoration-none">
                                    <i class="fas fa-times me-1"></i>Revoke from Doctor
                                </button>
                            </form>
                        @endif
                    </div>

                    @if($consultation->customer_whatsapp_sent_at)
                        <div class="alert alert-success d-flex align-items-center justify-content-between py-2 px-3 mt-2 mb-0 small">
                            <div>
                                <i class="fas fa-check-circle text-success me-1"></i>
                                WhatsApp sent on <strong>{{ $consultation->customer_whatsapp_sent_at->format('d M Y, h:i A') }}</strong>
                            </div>
                            @if($consultation->customer_whatsapp_url)
                                <a href="{{ $consultation->customer_whatsapp_url }}" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2 fw-bold">
                                    <i class="fab fa-whatsapp me-1"></i> Re-send
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Optional Direct Dispatch to Doctor WhatsApp -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <span class="fw-bold fs-6 text-dark"><i class="fab fa-whatsapp text-success me-2"></i>Send Meeting Details to Doctor WhatsApp (Direct)</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    The assigned doctor can already view this consultation in their <strong>Doctor Workspace</strong>. You can also send the appointment details &amp; Zoom link directly to their WhatsApp mobile:
                </p>
                <form method="POST" action="{{ route('admin.consultations.shareDoctor', $consultation) }}">
                    @csrf
                    <div class="input-group mb-2">
                        <input type="text" name="doctor_phone" class="form-control" placeholder="Doctor mobile number" value="{{ $consultation->doctor_phone ?: ($consultation->doctorProfile?->phone ?: '9274683460') }}" required>
                        <button type="submit" class="btn btn-outline-success fw-bold">
                            <i class="fab fa-whatsapp me-1"></i> Send to Doctor WhatsApp
                        </button>
                    </div>
                </form>
                @if($consultation->doctor_whatsapp_sent_at)
                    <small class="text-success"><i class="fas fa-check-circle me-1"></i>Dispatched to Doctor WhatsApp on {{ $consultation->doctor_whatsapp_sent_at->format('d M Y, h:i A') }}</small>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function getCleanPhone(raw) {
    let p = (raw || '').replace(/[^0-9]/g, '');
    if (p.startsWith('0') && p.length === 11) {
        p = '91' + p.substring(1);
    } else if (p.length === 10) {
        p = '91' + p;
    }
    return p;
}

function updateDetailPreview() {
    const docSelect = document.getElementById('detailDoctorSelect');
    let doctorName = 'our Lifestyle Medicine Doctor';
    if (docSelect && docSelect.selectedIndex >= 0 && docSelect.value) {
        const opt = docSelect.options[docSelect.selectedIndex];
        doctorName = opt.getAttribute('data-name') || opt.text.split('(')[0].trim();
    } else {
        doctorName = '{{ addslashes($consultation->doctor_display_name) }}';
    }

    const patientName = '{{ addslashes($consultation->name) }}';
    const patientPhone = '{{ addslashes($consultation->phone ?? '') }}';
    const cleanPhone = getCleanPhone(patientPhone);

    const timeInput = document.getElementById('detailScheduledAt');
    const timeVal = timeInput ? timeInput.value : '';
    const zoomInput = document.getElementById('detailZoomLink');
    const zoomVal = zoomInput && zoomInput.value ? zoomInput.value.trim() : '[Paste Zoom Link Here]';

    let formattedTime = '[Date & Time]';
    if (timeVal) {
        try {
            formattedTime = new Date(timeVal).toLocaleString('en-IN', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            }) + ' (IST)';
        } catch (e) {
            formattedTime = timeVal;
        }
    }

    const text = "🌿 *PRAKRUTI LIFESTYLE MEDICINE - CONSULTATION MEETING* 📹\n"
        + "━━━━━━━━━━━━━━━━━━━━━━━\n"
        + "Namaste *" + patientName + "*!\n\n"
        + "You have a meeting with the doctor *" + doctorName + "*.\n\n"
        + "📅 *Appointment Date & Time:* " + formattedTime + "\n"
        + "🔗 *Zoom / Video Call Link:*\n" + zoomVal + "\n\n"
        + "📋 *Session Details:*\n"
        + "• *Reference No:* #{{ $consultation->reference_no }}\n"
        + "• *Doctor:* " + doctorName + "\n"
        + "• *Session:* {{ $consultation->session_type === 'follow_up' ? 'Follow-up Consultation' : 'Lifestyle Medicine Consultation' }}\n"
        + "• *Payment:* PAID (₹{{ number_format((float) ($consultation->payment_amount ?: 1500), 0) }})\n\n"
        + "🌐 *Access from Customer Dashboard:*\n"
        + "You can also join directly by logging in to your account at http://127.0.0.1:5174/#account\n\n"
        + "💡 *Instructions:*\n"
        + "1. Please click the link 5 minutes before your scheduled time.\n"
        + "2. Make sure you are in a quiet room with stable internet connection.\n"
        + "3. Keep any recent medical or nutrition reports handy for the doctor.\n\n"
        + "Need help or need to reschedule?\n"
        + "📞 Call/WhatsApp: +91 92746 83460 (Prakruti Care)\n"
        + "━━━━━━━━━━━━━━━━━━━━━━━\n"
        + "Looking forward to your healthy wellness transformation!\n"
        + "- Prakruti Lifestyle Medicine Team";

    const box = document.getElementById('detailPreviewBox');
    if (box) box.textContent = text;

    const directBtn = document.getElementById('directWaBtn');
    if (directBtn && cleanPhone) {
        directBtn.href = "https://web.whatsapp.com/send?phone=" + cleanPhone + "&text=" + encodeURIComponent(text);
    }
}

function submitAndOpenWebWhatsApp() {
    const form = document.getElementById('mainScheduleForm');
    if (!form) return;

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const patientPhone = '{{ addslashes($consultation->phone ?? '') }}';
    const cleanPhone = getCleanPhone(patientPhone);
    const box = document.getElementById('detailPreviewBox');
    const msg = box ? box.textContent : '';

    if (cleanPhone) {
        const waUrl = "https://web.whatsapp.com/send?phone=" + encodeURIComponent(cleanPhone) + "&text=" + encodeURIComponent(msg);
        window.open(waUrl, '_blank');
    }

    let actionInput = form.querySelector('input[name="action_type"]');
    if (!actionInput) {
        actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action_type';
        form.appendChild(actionInput);
    }
    actionInput.value = 'save_and_whatsapp';

    form.submit();
}

document.addEventListener('DOMContentLoaded', function() {
    updateDetailPreview();

    @if(session('launch_whatsapp'))
        // Attempt popup opening safely
        try {
            window.open('{{ session('launch_whatsapp') }}', '_blank');
        } catch (e) {
            console.log('Popup blocked; user can use explicit Open WhatsApp button.');
        }
    @endif
});
</script>
@endpush

@section('scripts')
<script>
// Mirror script call for section compatibility
if (typeof updateDetailPreview === 'function') {
    updateDetailPreview();
}
</script>
@endsection
