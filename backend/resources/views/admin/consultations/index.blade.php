@extends('admin.layouts.app')

@section('title', 'Consultation Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">Consultation Requests</h2>
        <p class="text-muted small mb-0">Review inbound patient requests and forward inquiries directly to doctors via WhatsApp or Gmail.</p>
    </div>
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="not_sent" @selected(request('status') === 'not_sent')>Not Sent</option>
            <option value="sent" @selected(request('status') === 'sent')>Sent</option>
        </select>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Reference</th>
                    <th>Patient</th>
                    <th>Contact Info</th>
                    <th>Status</th>
                    <th>Doctor Dispatch</th>
                    <th>Submitted</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $requestItem)
                    <tr>
                        <td class="fw-bold text-primary">#{{ $requestItem->reference_no }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $requestItem->name }}</div>
                            <small class="text-muted">
                                {{ $requestItem->age_range ?: 'Age N/A' }} &bull; {{ $requestItem->dietary_preference ?: 'Diet N/A' }}
                            </small>
                        </td>
                        <td>
                            <div><i class="fas fa-phone-alt me-1 text-muted small"></i>{{ $requestItem->phone ?: '-' }}</div>
                            <small class="text-muted"><i class="fas fa-envelope me-1 text-muted small"></i>{{ $requestItem->email ?: '-' }}</small>
                        </td>
                        <td>
                            @if(in_array($requestItem->status, ['sent', 'assigned', 'contacted', 'consultation_scheduled', 'completed', 'closed']))
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Sent</span>
                            @else
                                <span class="badge bg-danger"><i class="fas fa-clock me-1"></i>Not Sent</span>
                            @endif
                        </td>
                        <td>
                            @if($requestItem->doctor_notified_at)
                                <span class="badge bg-success-subtle text-success border border-success-subtle" title="Dispatched on {{ $requestItem->doctor_notified_at->format('d M, h:i A') }}">
                                    <i class="fas fa-check-double me-1"></i>Sent ({{ strtoupper($requestItem->doctor_notified_channel) }})
                                </span>
                                @if($requestItem->assigned_to)
                                    <div class="small text-muted mt-1">Dr. {{ $requestItem->assigned_to }}</div>
                                @endif
                            @else
                                <span class="badge bg-light text-muted border">
                                    <i class="fas fa-clock me-1"></i>Not sent yet
                                </span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $requestItem->created_at?->timezone('Asia/Kolkata')->format('d M Y') }}</div>
                            <small class="text-muted">{{ $requestItem->created_at?->timezone('Asia/Kolkata')->format('h:i A') }}</small>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.consultations.show', $requestItem) }}" class="btn btn-outline-primary" title="View details">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <button type="button" class="btn btn-outline-success" 
                                    onclick="openQuickDoctorModal(
                                        {{ $requestItem->id }}, 
                                        '{{ addslashes($requestItem->reference_no) }}', 
                                        '{{ addslashes($requestItem->name) }}', 
                                        '{{ addslashes($requestItem->assigned_to ?? '') }}', 
                                        '{{ addslashes($requestItem->doctor_phone ?? '') }}', 
                                        '{{ addslashes($requestItem->doctor_email ?? '') }}'
                                    )" 
                                    title="Quick Send to Doctor via WhatsApp / Gmail">
                                    <i class="fab fa-whatsapp text-success me-1"></i><i class="fas fa-envelope text-danger"></i> Send
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-2x mb-2 d-block text-muted"></i>
                            No consultation requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $requests->links() }}</div>

<!-- Quick Doctor Dispatch Modal -->
<div class="modal fade" id="quickDoctorModal" tabindex="-1" aria-labelledby="quickDoctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold" id="quickDoctorModalLabel">
                    <i class="fas fa-share-square me-2"></i>Send Case to Doctor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border mb-3 py-2 px-3 small">
                    <strong>Patient:</strong> <span id="modalPatientName" class="fw-bold"></span> &nbsp;|&nbsp; 
                    <strong>Ref:</strong> #<span id="modalRefNo" class="font-monospace fw-bold"></span>
                </div>

                <form id="modalDoctorForm" method="POST">
                    @csrf
                    <input type="hidden" name="channel" id="modalChannel" value="whatsapp">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">
                            Doctor / Specialist Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user-md text-success"></i></span>
                            <input type="text" name="assigned_to" id="modalDoctorName" class="form-control" placeholder="e.g. Dr. Ramesh Sharma" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark d-flex justify-content-between">
                            <span><i class="fab fa-whatsapp text-success me-1"></i>Doctor's WhatsApp Mobile <span class="text-danger">*</span></span>
                            <span class="text-muted small fw-normal">Can be edited</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">+91</span>
                            <input type="tel" name="doctor_phone" id="modalDoctorPhone" class="form-control" placeholder="10-digit mobile number">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark d-flex justify-content-between">
                            <span><i class="fab fa-google text-danger me-1"></i>Doctor's Gmail / Email <span class="text-danger">*</span></span>
                            <span class="text-muted small fw-normal">Can be edited</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-danger"></i></span>
                            <input type="email" name="doctor_email" id="modalDoctorEmail" class="form-control" placeholder="e.g. doctor@gmail.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Quick Note for Doctor <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="text" name="custom_note" id="modalCustomNote" class="form-control" placeholder="e.g., Requested callback in evening">
                    </div>

                    <div class="d-grid gap-2 pt-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-success w-100 fw-bold py-2" onclick="executeQuickDispatch('whatsapp')">
                                    <i class="fab fa-whatsapp fs-5 me-1 align-middle"></i> WhatsApp
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-danger w-100 fw-bold py-2" onclick="executeQuickDispatch('gmail')">
                                    <i class="fab fa-google fs-5 me-1 align-middle"></i> Gmail
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-dark fw-bold py-2" onclick="executeQuickDispatch('both')">
                            <i class="fas fa-paper-plane me-1"></i> Send Both (WhatsApp & Gmail)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentConsultationId = null;

function openQuickDoctorModal(id, refNo, patientName, assignedTo, doctorPhone, doctorEmail) {
    currentConsultationId = id;
    document.getElementById('modalRefNo').textContent = refNo;
    document.getElementById('modalPatientName').textContent = patientName;

    // Pre-fill fields with existing values or fall back to localStorage
    const savedDocName = localStorage.getItem('prakruti_last_doctor_name') || '';
    const savedDocPhone = localStorage.getItem('prakruti_last_doctor_phone') || '';
    const savedDocEmail = localStorage.getItem('prakruti_last_doctor_email') || '';

    document.getElementById('modalDoctorName').value = assignedTo || savedDocName;
    document.getElementById('modalDoctorPhone').value = doctorPhone || savedDocPhone;
    document.getElementById('modalDoctorEmail').value = doctorEmail || savedDocEmail;
    document.getElementById('modalCustomNote').value = '';

    const modal = new bootstrap.Modal(document.getElementById('quickDoctorModal'));
    modal.show();
}

function executeQuickDispatch(channel) {
    if (!currentConsultationId) return;

    const docPhone = document.getElementById('modalDoctorPhone').value.trim();
    const docEmail = document.getElementById('modalDoctorEmail').value.trim();
    const docName = document.getElementById('modalDoctorName').value.trim();
    const customNote = document.getElementById('modalCustomNote').value.trim();

    if ((channel === 'whatsapp' || channel === 'both') && !docPhone) {
        alert('Please enter doctor WhatsApp phone number.');
        document.getElementById('modalDoctorPhone').focus();
        return;
    }

    if ((channel === 'gmail' || channel === 'both') && !docEmail) {
        alert('Please enter doctor Gmail address.');
        document.getElementById('modalDoctorEmail').focus();
        return;
    }

    // Remember in localStorage
    if (docPhone) localStorage.setItem('prakruti_last_doctor_phone', docPhone);
    if (docEmail) localStorage.setItem('prakruti_last_doctor_email', docEmail);
    if (docName) localStorage.setItem('prakruti_last_doctor_name', docName);

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('channel', channel);
    formData.append('doctor_phone', docPhone);
    formData.append('doctor_email', docEmail);
    formData.append('assigned_to', docName);
    if (customNote) formData.append('custom_note', customNote);

    const targetUrl = "{{ url('admin/consultations') }}/" + currentConsultationId + "/send-to-doctor";

    fetch(targetUrl, {
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
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            alert(data.message || 'Error sending to doctor.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Could not complete request. Please open the consultation details page to send.');
    });
}
</script>
@endsection
