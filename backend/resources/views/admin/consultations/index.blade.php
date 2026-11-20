@extends('admin.layouts.app')

@section('title', 'Consultation Requests & Doctor Dispatch')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1 fw-bold text-dark">
            <i class="fas fa-stethoscope text-success me-2"></i>Patient Consultations &amp; Doctor Dispatch
        </h2>
        <p class="text-muted small mb-0">
            Assign registered doctors from the dropdown, send patient data to Doctor Panel, paste Zoom meeting links, and notify customers on WhatsApp.
        </p>
    </div>
</div>

@if(isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="fas fa-exclamation-triangle fs-5 text-danger mt-1"></i>
            <div>
                <strong>Please check the errors:</strong>
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
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                    If WhatsApp Web did not open in a new tab automatically, click the button below to send the meeting link to the customer:
                </div>
                @if(session('whatsapp_message'))
                    <div class="p-2 mt-2 bg-white rounded border small text-dark font-monospace" style="white-space: pre-line; max-height: 120px; overflow-y: auto;">
                        {{ session('whatsapp_message') }}
                    </div>
                @endif
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if(session('launch_whatsapp'))
                    <a href="{{ session('launch_whatsapp') }}" target="_blank" class="btn btn-success fw-bold shadow-sm">
                        <i class="fab fa-whatsapp me-1"></i> Send Text to Customer
                    </a>
                @endif
                @if(session('whatsapp_message'))
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyWhatsAppText()">
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

<!-- KPI Filter Tabs -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'all' ? 'active bg-dark text-white' : 'text-dark' }} py-1 px-3 small fw-bold" href="{{ route('admin.consultations.index', ['filter' => 'all']) }}">
                        All ({{ $requests->total() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'pending_approval' ? 'active bg-warning text-dark' : 'text-dark' }} py-1 px-3 small fw-bold" href="{{ route('admin.consultations.index', ['filter' => 'pending_approval']) }}">
                        <i class="fas fa-clock me-1"></i> Awaiting Doctor Send ({{ $pendingApprovalCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'approved' ? 'active bg-success text-white' : 'text-dark' }} py-1 px-3 small fw-bold" href="{{ route('admin.consultations.index', ['filter' => 'approved']) }}">
                        <i class="fas fa-check-circle me-1"></i> Sent to Doctor Panel ({{ $approvedCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'followup' ? 'active bg-info text-dark' : 'text-dark' }} py-1 px-3 small fw-bold" href="{{ route('admin.consultations.index', ['filter' => 'followup']) }}">
                        <i class="fas fa-redo me-1"></i> Follow-up Sessions ({{ $followupCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'scheduled' ? 'active bg-primary text-white' : 'text-dark' }} py-1 px-3 small fw-bold" href="{{ route('admin.consultations.index', ['filter' => 'scheduled']) }}">
                        <i class="fas fa-calendar-check me-1"></i> Scheduled ({{ $scheduledCount }})
                    </a>
                </li>
            </ul>

            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search patient, phone, ref..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.consultations.index', ['filter' => $filter]) }}" class="btn btn-sm btn-link text-muted">Reset</a>
                @endif
            </form>
        </div>
    </div>
</div>

<!-- Consultations Table -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 120px;">Ref # &amp; Date</th>
                    <th style="min-width: 200px;">Patient Details</th>
                    <th style="min-width: 140px;">Session &amp; Payment</th>
                    <th style="min-width: 260px;">Assigned Doctor &amp; Doctor Panel</th>
                    <th style="min-width: 180px;">Appointment Slot &amp; Zoom</th>
                    <th style="min-width: 180px;">Customer WhatsApp</th>
                    <th class="text-end" style="min-width: 90px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $item)
                    <tr>
                        <!-- Ref & Date -->
                        <td>
                            <div class="fw-bold text-primary font-monospace">#{{ $item->reference_no }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                {{ $item->created_at ? $item->created_at->format('d M, h:i A') : 'Recently' }}
                            </small>
                        </td>

                        <!-- Patient Info -->
                        <td>
                            <div class="fw-bold text-dark">{{ $item->name }}</div>
                            <div class="small text-muted"><i class="fas fa-phone-alt me-1"></i>{{ $item->phone ?: 'Not provided' }}</div>
                            <div class="small text-muted text-truncate" style="max-width: 220px;"><i class="fas fa-envelope me-1"></i>{{ $item->email ?: 'Not provided' }}</div>
                            @if($item->concern)
                                <div class="text-truncate small text-secondary mt-1" style="max-width: 220px;" title="{{ $item->concern }}">
                                    <i class="fas fa-notes-medical me-1 text-danger"></i>{{ Str::limit($item->concern, 40) }}
                                </div>
                            @endif
                        </td>

                        <!-- Session & Payment -->
                        <td>
                            @if($item->visit_number > 1 || $item->is_returning_patient)
                                <span class="badge text-white fw-bold shadow-sm d-inline-flex align-items-center gap-1" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7) !important;">
                                    <i class="fas fa-redo"></i> 2nd Time Patient (Follow-up #{{ max(2, $item->visit_number) }})
                                </span>
                            @else
                                <span class="badge bg-success fw-bold d-inline-flex align-items-center gap-1">
                                    <i class="fas fa-seedling"></i> 1st Time Patient (Initial)
                                </span>
                            @endif
                            <div class="small text-success fw-bold mt-1">
                                <i class="fas fa-check-circle me-1"></i>PAID ₹{{ number_format((float) ($item->payment_amount ?: 1500), 0) }}
                            </div>
                            @if($item->previous_consultations && $item->previous_consultations->count() > 0)
                                <div class="small text-primary mt-1" style="font-size: 0.72rem;">
                                    <i class="fas fa-history me-1"></i>Has {{ $item->previous_consultations->count() }} past booking(s)
                                </div>
                            @endif
                        </td>

                        <!-- Assigned Doctor & Doctor Panel Send Action -->
                        <td>
                            @if($item->admin_approved && $item->doctor_profile_id)
                                <div class="p-2 bg-success-subtle border border-success-subtle rounded">
                                    <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                        <span class="badge bg-success fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> In Doctor Panel
                                        </span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="toggleDoctorEdit({{ $item->id }})" title="Change Assigned Doctor">
                                            <i class="fas fa-pencil-alt" style="font-size: 0.75rem;"></i> Change
                                        </button>
                                    </div>
                                    <div class="fw-bold text-dark small">
                                        <i class="fas fa-user-md text-success me-1"></i>{{ $item->doctor_display_name }}
                                    </div>
                                    @if($item->admin_approved_at)
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            Sent on {{ $item->admin_approved_at->format('d M, h:i A') }}
                                        </div>
                                    @endif

                                    <!-- Hidden form to re-assign doctor -->
                                    <div id="doctorEditBox{{ $item->id }}" style="display: none;" class="mt-2 pt-2 border-top">
                                        <form method="POST" action="{{ route('admin.consultations.sendToDoctor', $item) }}" class="d-flex gap-1">
                                            @csrf
                                            <select name="doctor_profile_id" class="form-select form-select-sm" required>
                                                @foreach($doctors as $doc)
                                                    <option value="{{ $doc->id }}" {{ $item->doctor_profile_id == $doc->id ? 'selected' : '' }}>
                                                        {{ $doc->formatted_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary py-0 px-2 fw-bold">Update</button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <!-- Doctor Dropdown & Send Button -->
                                <form method="POST" action="{{ route('admin.consultations.sendToDoctor', $item) }}">
                                    @csrf
                                    <div class="input-group input-group-sm mb-1">
                                        <select name="doctor_profile_id" class="form-select form-select-sm" required style="border-color: #198754;">
                                            <option value="" disabled selected>-- Choose Doctor --</option>
                                            @foreach($doctors as $doc)
                                                <option value="{{ $doc->id }}" {{ $item->doctor_profile_id == $doc->id ? 'selected' : '' }}>
                                                    {{ $doc->formatted_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-success fw-bold px-2.5 shadow-sm" title="Assign doctor and send to Doctor Panel">
                                            <i class="fas fa-paper-plane me-1"></i> Send &check;
                                        </button>
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.72rem;">
                                        <i class="fas fa-exclamation-circle text-warning me-1"></i>Select doctor &amp; click Send
                                    </div>
                                </form>
                            @endif
                        </td>

                        <!-- Scheduled Slot & Zoom Meeting -->
                        <td>
                            @if($item->scheduled_at)
                                <div class="fw-bold text-dark small">
                                    <i class="fas fa-calendar-alt text-primary me-1"></i>{{ $item->scheduled_at->format('d M Y, h:i A') }}
                                </div>
                            @else
                                <span class="badge bg-light text-muted border mb-1">Time not set</span>
                            @endif

                            @if($item->zoom_link)
                                <div class="mt-1">
                                    <a href="{{ $item->zoom_link }}" target="_blank" class="badge bg-primary text-decoration-none d-inline-flex align-items-center gap-1" title="Open Zoom Meeting URL">
                                        <i class="fas fa-video"></i> Zoom Ready &nearr;
                                    </a>
                                </div>
                            @else
                                <div class="small text-danger mt-1">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Zoom link needed
                                </div>
                            @endif
                        </td>

                        <!-- Customer WhatsApp Dispatch ("Send Link") -->
                        <td>
                            <button type="button" class="btn btn-sm btn-success py-1.5 px-2.5 fw-bold d-inline-flex align-items-center gap-1 shadow-sm w-100 justify-content-center"
                                onclick="openScheduleAndNotifyModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ addslashes($item->phone ?? '') }}', '{{ addslashes($item->email ?? '') }}', '{{ addslashes($item->zoom_link ?? '') }}', '{{ $item->scheduled_at ? $item->scheduled_at->format('Y-m-d\TH:i') : now()->addDay()->format('Y-m-d\T17:00') }}', '{{ $item->doctor_profile_id }}')">
                                <i class="fab fa-whatsapp fs-6"></i>
                                <span>Send Zoom Link</span>
                            </button>
                            @if($item->customer_whatsapp_sent_at)
                                <div class="small text-success mt-1 text-center" style="font-size: 0.72rem;">
                                    <i class="fas fa-check-double me-1"></i>Sent on {{ $item->customer_whatsapp_sent_at->format('d M, h:i A') }}
                                </div>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.consultations.show', $item) }}" class="btn btn-sm btn-outline-primary fw-bold" title="View Full Details">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <form method="POST" action="{{ route('admin.consultations.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Permanently delete consultation #{{ $item->reference_no }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Consultation">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-stethoscope fa-3x mb-3 text-secondary d-block"></i>
                            No consultation requests found for this filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $requests->links() }}
</div>

<!-- Modal: Schedule Meeting Time & Send Zoom Link to Customer WhatsApp & Doctor Panel -->
<div class="modal fade" id="scheduleAndNotifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="scheduleNotifyForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fab fa-whatsapp text-success me-2"></i>Send Zoom Meeting Link to Customer &amp; Doctor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="fw-bold text-dark fs-6" id="modalCustName">-</div>
                        <div class="small text-muted" id="modalCustPhone"><i class="fas fa-phone-alt me-1"></i> -</div>
                        <div class="small text-muted" id="modalCustEmail"><i class="fas fa-envelope me-1"></i> -</div>
                    </div>

                    <!-- Doctor Selection in Modal -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Assigned Doctor *</label>
                        <select name="doctor_profile_id" id="modalDoctorSelect" class="form-select" required onchange="updatePreview()">
                            <option value="" disabled>-- Select Doctor --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" data-name="{{ $doc->formatted_name }}">{{ $doc->formatted_name }} ({{ $doc->specialization ?: 'Lifestyle Medicine' }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">The appointment data &amp; Zoom meeting will appear in this doctor's portal.</small>
                    </div>

                    <!-- Appointment Date & Time -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Appointment Date &amp; Time (IST) *</label>
                        <input type="datetime-local" class="form-control" name="scheduled_at" id="modalScheduledAt" required onchange="updatePreview()">
                        <small class="text-muted">This time is sent to the customer on WhatsApp and displayed to the doctor.</small>
                    </div>

                    <!-- Paste Zoom Meeting URL -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Paste Zoom Meeting Link *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white"><i class="fas fa-video"></i></span>
                            <input type="url" class="form-control" name="zoom_link" id="modalZoomLink" placeholder="https://us05web.zoom.us/j/... or Google Meet link" required oninput="updatePreview()">
                        </div>
                        <small class="text-muted">Create the meeting in your Zoom application and paste the URL here.</small>
                    </div>

                    <!-- WhatsApp Message Preview Box -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-success">
                            <i class="fab fa-whatsapp me-1"></i>WhatsApp Message Preview:
                        </label>
                        <div class="p-2.5 bg-light rounded border small text-dark font-monospace" id="modalPreviewBox" style="white-space: pre-line; line-height: 1.4; max-height: 130px; overflow-y: auto;">
                            Loading preview...
                        </div>
                    </div>

                    <div class="form-check form-switch p-2 bg-light rounded border mb-2">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="auto_approve" id="modalAutoApprove" value="1" checked>
                        <label class="form-check-label fw-bold text-dark" for="modalAutoApprove">
                            &check; Send &amp; Make Live in Doctor Panel
                        </label>
                    </div>
                </div>

                <div class="modal-footer bg-light justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="action_type" value="save_only" class="btn btn-primary btn-sm fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> Save Details
                    </button>
                    <button type="button" id="modalOpenWebWhatsAppBtn" class="btn btn-success btn-sm fw-bold shadow-sm" onclick="modalSubmitAndOpenWebWhatsApp()">
                        <i class="fab fa-whatsapp me-1"></i> Send Text to Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentCustName = '';
let currentCustPhone = '';
let currentCustRef = '';

function toggleDoctorEdit(id) {
    const box = document.getElementById('doctorEditBox' + id);
    if (box) {
        box.style.display = box.style.display === 'none' ? 'block' : 'none';
    }
}

function getCleanPhone(raw) {
    let p = (raw || '').replace(/[^0-9]/g, '');
    if (p.startsWith('0') && p.length === 11) {
        p = '91' + p.substring(1);
    } else if (p.length === 10) {
        p = '91' + p;
    }
    return p;
}

function openScheduleAndNotifyModal(id, name, phone, email, zoomLink, scheduledAt, doctorProfileId) {
    currentCustName = name;
    currentCustPhone = phone || '';
    currentCustRef = id;

    document.getElementById('modalCustName').textContent = name;
    document.getElementById('modalCustPhone').textContent = phone ? phone : 'No phone provided';
    document.getElementById('modalCustEmail').textContent = email ? email : 'No email provided';

    document.getElementById('modalZoomLink').value = zoomLink || '';

    if (scheduledAt) {
        document.getElementById('modalScheduledAt').value = scheduledAt;
    }

    if (doctorProfileId) {
        document.getElementById('modalDoctorSelect').value = doctorProfileId;
    } else {
        const select = document.getElementById('modalDoctorSelect');
        if (select.options.length > 1) {
            select.selectedIndex = 1; // Default to first available doctor
        }
    }

    document.getElementById('modalAutoApprove').checked = true;

    const form = document.getElementById('scheduleNotifyForm');
    form.action = '/admin/consultations/' + id + '/schedule-customer';

    updatePreview();

    const modal = new bootstrap.Modal(document.getElementById('scheduleAndNotifyModal'));
    modal.show();
}

function updatePreview() {
    const docSelect = document.getElementById('modalDoctorSelect');
    let doctorName = 'our Doctor';
    if (docSelect && docSelect.selectedIndex >= 0 && docSelect.value) {
        const opt = docSelect.options[docSelect.selectedIndex];
        doctorName = opt.getAttribute('data-name') || opt.text.split('(')[0].trim();
    }
    const timeVal = document.getElementById('modalScheduledAt').value;
    const zoomVal = document.getElementById('modalZoomLink').value || '[Paste Zoom Link Here]';

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
        + "Namaste *" + (currentCustName || 'Customer') + "*!\n\n"
        + "You have a meeting with the doctor *" + doctorName + "*.\n\n"
        + "📅 *Appointment Date & Time:* " + formattedTime + "\n"
        + "🔗 *Zoom / Video Call Link:*\n" + zoomVal + "\n\n"
        + "💡 *Instructions:*\n"
        + "1. Please click the link 5 minutes before your scheduled time.\n"
        + "2. Make sure you are in a quiet room with stable internet connection.\n\n"
        + "📞 Call/WhatsApp: +91 92746 83460 (Prakruti Care)\n"
        + "━━━━━━━━━━━━━━━━━━━━━━━\n"
        + "- Prakruti Health Care Team";

    const box = document.getElementById('modalPreviewBox');
    if (box) box.textContent = text;

    const directBtn = document.getElementById('modalDirectWaBtn');
    const cleanPhone = getCleanPhone(currentCustPhone);
    if (directBtn) {
        if (cleanPhone) {
            directBtn.href = "https://web.whatsapp.com/send?phone=" + cleanPhone + "&text=" + encodeURIComponent(text);
            directBtn.style.display = 'inline-flex';
        } else {
            directBtn.style.display = 'none';
        }
    }
}

function modalSubmitAndOpenWebWhatsApp() {
    const form = document.getElementById('scheduleNotifyForm');
    if (!form) return;
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const cleanPhone = getCleanPhone(currentCustPhone);
    const box = document.getElementById('modalPreviewBox');
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

function copyWhatsAppText() {
    const text = document.getElementById('modalPreviewBox') ? document.getElementById('modalPreviewBox').textContent : '';
    if (text) {
        navigator.clipboard.writeText(text);
        alert('WhatsApp message copied to clipboard!');
    }
}

// Auto open WhatsApp Web if redirected with launch_whatsapp
@if(session('launch_whatsapp'))
document.addEventListener('DOMContentLoaded', function() {
    try {
        window.open('{{ session('launch_whatsapp') }}', '_blank');
    } catch (e) {
        console.log('Popup blocked; use the Open WhatsApp button in the banner.');
    }
});
@endif
</script>
@endpush

@section('scripts')
<script>
// Section mirror for backward compatibility
</script>
@endsection
