@extends('admin.layouts.app')

@section('title', 'Consultation ' . $consultation->reference_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">Consultation Request #{{ $consultation->reference_no }}</h2>
        <div class="text-muted small">
            &nbsp;•&nbsp; Status: 
            @if(in_array($consultation->status, ['sent', 'assigned', 'contacted', 'consultation_scheduled', 'completed', 'closed']))
                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Sent</span>
            @else
                <span class="badge bg-danger"><i class="fas fa-clock me-1"></i>Not Sent</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="#doctorDispatchCard" class="btn btn-success">
            <i class="fab fa-whatsapp me-1"></i><i class="fas fa-envelope me-1"></i> Send to Doctor
        </a>
        <a href="{{ route('admin.consultations.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

@if(session('launch_whatsapp') || session('launch_gmail'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center gap-3">
            <i class="fas fa-check-circle fa-2x text-success"></i>
            <div>
                <h5 class="alert-heading mb-1 fw-bold">Consultation Assigned & Ready to Send!</h5>
                <p class="mb-2 text-dark">Click the link below if your browser blocked the automatic popup window:</p>
                <div class="d-flex gap-2 flex-wrap">
                    @if(session('launch_whatsapp'))
                        <a href="{{ session('launch_whatsapp') }}" target="_blank" class="btn btn-sm btn-success fw-bold">
                            <i class="fab fa-whatsapp me-1"></i> Open WhatsApp Web Now
                        </a>
                    @endif
                    @if(session('launch_gmail'))
                        <a href="{{ session('launch_gmail') }}" target="_blank" class="btn btn-sm btn-danger fw-bold">
                            <i class="fab fa-google me-1"></i> Open Gmail Compose Now
                        </a>
                    @endif
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4">
    <!-- Left Column: Patient Details -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6 text-dark"><i class="fas fa-user-circle text-primary me-2"></i>Patient / Client Inquiry Profile</span>
                <span class="badge bg-light text-dark border">Ref: {{ $consultation->reference_no }}</span>
            </div>
            <div class="card-body">
                <dl class="row mb-0 gy-2">
                    <dt class="col-sm-4 text-muted">Patient Name</dt>
                    <dd class="col-sm-8 fw-bold fs-6 text-dark">{{ $consultation->name }}</dd>

                    <dt class="col-sm-4 text-muted">Mobile Number</dt>
                    <dd class="col-sm-8">
                        @if($consultation->phone)
                            <a href="tel:{{ $consultation->phone }}" class="text-decoration-none fw-bold text-primary">
                                <i class="fas fa-phone-alt me-1"></i>{{ $consultation->phone }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Email Address</dt>
                    <dd class="col-sm-8">
                        @if($consultation->email)
                            <a href="mailto:{{ $consultation->email }}" class="text-decoration-none text-dark">
                                <i class="fas fa-envelope me-1 text-muted"></i>{{ $consultation->email }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Preferred Mode</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-info text-dark text-capitalize px-2 py-1">
                            <i class="fas fa-comments me-1"></i>{{ ucfirst($consultation->preferred_contact_method) }}
                        </span>
                    </dd>

                    <dt class="col-sm-4 text-muted">Age Range</dt>
                    <dd class="col-sm-8">{{ $consultation->age_range ?: 'Not specified' }}</dd>

                    <dt class="col-sm-4 text-muted">Dietary Preference</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            {{ $consultation->dietary_preference ?: 'Standard' }}
                        </span>
                    </dd>

                    <dt class="col-sm-4 text-muted">Preferred Timing</dt>
                    <dd class="col-sm-8">{{ $consultation->preferred_time ?: 'Flexible' }}</dd>

                    <dt class="col-sm-4 text-muted">Consent Provided</dt>
                    <dd class="col-sm-8">
                        @if($consultation->consent)
                            <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Yes, Authorized</span>
                        @else
                            <span class="text-muted">No</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Submitted Time</dt>
                    <dd class="col-sm-8">{{ $consultation->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} (IST)</dd>

                    <dt class="col-sm-12 text-muted mt-2 border-top pt-2">Patient Health Concern / Symptoms</dt>
                    <dd class="col-sm-12">
                        <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 14px; line-height: 1.5;">{{ $consultation->concern }}</div>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Right Column: Dispatch to Doctor & Status Management -->
    <div class="col-lg-6">
        <!-- Direct Forward to Doctor Card -->
        <div class="card shadow-sm border-0 mb-4" id="doctorDispatchCard" style="border-top: 4px solid #198754 !important;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6 text-success">
                    <i class="fas fa-share-square me-2"></i>Forward Case Directly to Doctor
                </span>
                @if($consultation->doctor_notified_at)
                    <span class="badge bg-success">
                        <i class="fas fa-check-double me-1"></i>Sent via {{ strtoupper($consultation->doctor_notified_channel) }}
                    </span>
                @else
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-clock me-1"></i>Not sent yet
                    </span>
                @endif
            </div>
            <div class="card-body">
                @if($consultation->doctor_notified_at)
                    <div class="alert alert-light border d-flex align-items-center gap-2 py-2 px-3 mb-3 small text-muted">
                        <i class="fas fa-info-circle text-success fs-5"></i>
                        <div>
                            Last dispatched on <strong>{{ $consultation->doctor_notified_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</strong>
                            to <strong>{{ $consultation->assigned_to ?: 'Doctor' }}</strong>
                            @if($consultation->doctor_phone) (WhatsApp: {{ $consultation->doctor_phone }}) @endif
                            @if($consultation->doctor_email) (Gmail: {{ $consultation->doctor_email }}) @endif
                        </div>
                    </div>
                @endif

                <form id="doctorForwardForm" action="{{ route('admin.consultations.sendToDoctor', $consultation) }}" method="POST">
                    @csrf
                    <input type="hidden" name="channel" id="forwardChannel" value="whatsapp">

                    <!-- Doctor Name -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">
                            Doctor / Nutritionist Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user-md text-success"></i></span>
                            <input type="text" name="assigned_to" id="assignedToInput" class="form-control" 
                                value="{{ old('assigned_to', $consultation->assigned_to) }}" 
                                placeholder="e.g. Dr. Ramesh Sharma, BAMS" required>
                        </div>
                    </div>

                    <!-- Doctor Phone for WhatsApp -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark d-flex justify-content-between">
                            <span><i class="fab fa-whatsapp text-success me-1"></i>Doctor's WhatsApp Mobile Number <span class="text-danger">*</span></span>
                            <span class="text-muted small fw-normal">Can be updated anytime</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">+91</span>
                            <input type="tel" name="doctor_phone" id="doctorPhoneInput" class="form-control @error('doctor_phone') is-invalid @enderror" 
                                value="{{ old('doctor_phone', $consultation->doctor_phone) }}" 
                                placeholder="10-digit mobile (e.g. 9876543210)">
                            @error('doctor_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text small">Enter doctor's active WhatsApp number. We format and open WhatsApp directly.</div>
                    </div>

                    <!-- Doctor Email for Gmail -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark d-flex justify-content-between">
                            <span><i class="fab fa-google text-danger me-1"></i>Doctor's Gmail / Email Address <span class="text-danger">*</span></span>
                            <span class="text-muted small fw-normal">Can be updated anytime</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-danger"></i></span>
                            <input type="email" name="doctor_email" id="doctorEmailInput" class="form-control @error('doctor_email') is-invalid @enderror" 
                                value="{{ old('doctor_email', $consultation->doctor_email) }}" 
                                placeholder="e.g. doctor.prakruti@gmail.com">
                            @error('doctor_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text small">Enter doctor's Gmail. Opens Gmail Web Compose with pre-filled case details.</div>
                    </div>

                    <!-- Custom Triage Note -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">
                            Special Instructions / Admin Note to Doctor <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <textarea name="custom_note" id="customNoteInput" class="form-control" rows="2" 
                            placeholder="e.g., Priority patient, requested morning call, history of acidity..."></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="small fw-bold text-dark mb-2">Choose Action to Send to Doctor:</div>
                        <div class="d-grid gap-2 d-md-flex">
                            <button type="button" class="btn btn-success flex-grow-1 py-2 fw-bold" onclick="submitDispatch('whatsapp')">
                                <i class="fab fa-whatsapp fs-5 me-1 align-middle"></i> Send via WhatsApp
                            </button>
                            <button type="button" class="btn btn-danger flex-grow-1 py-2 fw-bold" onclick="submitDispatch('gmail')">
                                <i class="fab fa-google fs-5 me-1 align-middle"></i> Send via Gmail
                            </button>
                            <button type="button" class="btn btn-outline-dark py-2 fw-bold" onclick="submitDispatch('both')" title="Send via WhatsApp and Gmail">
                                <i class="fas fa-paper-plane me-1"></i> Send Both
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Message Preview -->
                    <div class="accordion" id="previewAccordion">
                        <div class="accordion-item border">
                            <h2 class="accordion-header" id="headingPreview">
                                <button class="accordion-button collapsed py-2 px-3 small text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePreview" aria-expanded="false" aria-controls="collapsePreview">
                                    <i class="fas fa-eye me-2 text-primary"></i> Preview Formatted Case Message Sent to Doctor
                                </button>
                            </h2>
                            <div id="collapsePreview" class="accordion-collapse collapse" aria-labelledby="headingPreview" data-bs-parent="#previewAccordion">
                                <div class="accordion-body bg-light small font-monospace p-3" style="white-space: pre-wrap; max-height: 220px; overflow-y: auto;">
{{ $consultation->buildDoctorWhatsAppMessage() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Follow-up & Status Card -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <span class="fw-bold fs-6 text-dark"><i class="fas fa-tasks text-secondary me-2"></i>Internal Consultation Status & Notes</span>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.consultations.update', $consultation) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark">Consultation Status</label>
                            <select name="status" class="form-select fw-bold">
                                <option value="not_sent" @selected(in_array($consultation->status, ['not_sent', 'new'])) class="text-danger">❌ Not Sent</option>
                                <option value="sent" @selected(in_array($consultation->status, ['sent', 'assigned', 'contacted', 'consultation_scheduled', 'completed', 'closed'])) class="text-success">✅ Sent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark">Assigned Professional</label>
                            <input type="text" name="assigned_to" class="form-control" value="{{ old('assigned_to', $consultation->assigned_to) }}" placeholder="Doctor / Nutritionist">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark">Doctor WhatsApp Number</label>
                            <input type="text" name="doctor_phone" class="form-control" value="{{ old('doctor_phone', $consultation->doctor_phone) }}" placeholder="Doctor phone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark">Doctor Gmail / Email</label>
                            <input type="email" name="doctor_email" class="form-control" value="{{ old('doctor_email', $consultation->doctor_email) }}" placeholder="Doctor email">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Internal Follow-up Notes & Log</label>
                        <textarea name="internal_note" class="form-control" rows="4" placeholder="Enter clinical notes, patient follow-up progress...">{{ old('internal_note', $consultation->internal_note) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Status & Notes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const docPhoneInput = document.getElementById('doctorPhoneInput');
    const docEmailInput = document.getElementById('doctorEmailInput');
    const assignedToInput = document.getElementById('assignedToInput');

    // Pre-fill from localStorage if fields are currently empty
    if (docPhoneInput && !docPhoneInput.value && localStorage.getItem('prakruti_last_doctor_phone')) {
        docPhoneInput.value = localStorage.getItem('prakruti_last_doctor_phone');
    }
    if (docEmailInput && !docEmailInput.value && localStorage.getItem('prakruti_last_doctor_email')) {
        docEmailInput.value = localStorage.getItem('prakruti_last_doctor_email');
    }
    if (assignedToInput && !assignedToInput.value && localStorage.getItem('prakruti_last_doctor_name')) {
        assignedToInput.value = localStorage.getItem('prakruti_last_doctor_name');
    }
});

function submitDispatch(channel) {
    const docPhone = document.getElementById('doctorPhoneInput').value.trim();
    const docEmail = document.getElementById('doctorEmailInput').value.trim();
    const docName = document.getElementById('assignedToInput').value.trim();
    const customNote = document.getElementById('customNoteInput').value.trim();

    if ((channel === 'whatsapp' || channel === 'both') && !docPhone) {
        alert('Please enter the Doctor’s WhatsApp phone number.');
        document.getElementById('doctorPhoneInput').focus();
        return;
    }

    if ((channel === 'gmail' || channel === 'both') && !docEmail) {
        alert('Please enter the Doctor’s Gmail / Email address.');
        document.getElementById('doctorEmailInput').focus();
        return;
    }

    // Save to localStorage for convenience
    if (docPhone) localStorage.setItem('prakruti_last_doctor_phone', docPhone);
    if (docEmail) localStorage.setItem('prakruti_last_doctor_email', docEmail);
    if (docName) localStorage.setItem('prakruti_last_doctor_name', docName);

    // Call backend API via fetch to record forwarding, then open WhatsApp / Gmail in new tab
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('channel', channel);
    formData.append('doctor_phone', docPhone);
    formData.append('doctor_email', docEmail);
    formData.append('assigned_to', docName);
    if (customNote) formData.append('custom_note', customNote);

    fetch('{{ route("admin.consultations.sendToDoctor", $consultation) }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (channel === 'whatsapp' || channel === 'both') {
                window.open(data.whatsapp_url, '_blank');
            }
            if (channel === 'gmail' || channel === 'both') {
                window.open(data.gmail_url, '_blank');
            }
            // Reload page to reflect updated status and log
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            alert(data.message || 'Error forwarding to doctor.');
        }
    })
    .catch(err => {
        console.error(err);
        // Fallback: standard form submit
        document.getElementById('forwardChannel').value = channel;
        document.getElementById('doctorForwardForm').submit();
    });
}
</script>
@endsection
