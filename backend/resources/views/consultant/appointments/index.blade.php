@extends('consultant.layouts.app')

@section('title', 'Pending Appointments & Zoom Consultations')

@section('content')
<!-- Header & Welcome -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1 fw-bold text-dark">
            <i class="fas fa-stethoscope text-success me-2"></i>Lifestyle Medicine Appointments
        </h2>
        <p class="text-muted small mb-0">
            Handle pending patient consultations, generate dedicated Zoom video links, and notify customers via WhatsApp &amp; Email.
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <button type="button" class="btn btn-success shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newOrderFollowupModal">
            <i class="fas fa-video"></i> + Connect Patient via Zoom (Order Follow-up)
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="{{ route('consultant.appointments', ['filter' => 'pending']) }}" class="text-decoration-none">
            <div class="stat-card border-start border-danger border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Pending Appointments</div>
                        <div class="fs-3 fw-bold text-dark mt-1">{{ $pendingCount }}</div>
                    </div>
                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('consultant.appointments', ['filter' => 'followup']) }}" class="text-decoration-none">
            <div class="stat-card border-start border-primary border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Order Follow-ups (Zoom)</div>
                        <div class="fs-3 fw-bold text-primary mt-1">{{ $followupCount }}</div>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="fas fa-video"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('consultant.appointments', ['filter' => 'scheduled']) }}" class="text-decoration-none">
            <div class="stat-card border-start border-info border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Scheduled Today</div>
                        <div class="fs-3 fw-bold text-info mt-1">{{ $scheduledTodayCount }}</div>
                    </div>
                    <div class="stat-icon bg-info-subtle text-info">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('consultant.appointments', ['filter' => 'completed']) }}" class="text-decoration-none">
            <div class="stat-card border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Completed</div>
                        <div class="fs-3 fw-bold text-success mt-1">{{ $completedCount }}</div>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'pending' ? 'active bg-danger text-white' : 'text-dark' }} py-1 px-3 small fw-bold" href="{{ route('consultant.appointments', ['filter' => 'pending']) }}">
                        <i class="fas fa-exclamation-circle me-1"></i> Pending Action ({{ $pendingCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'followup' ? 'active bg-primary text-white' : 'text-dark' }} py-1 px-3 small fw-bold" href="{{ route('consultant.appointments', ['filter' => 'followup']) }}">
                        <i class="fas fa-video me-1"></i> Order Follow-ups (Zoom Only) ({{ $followupCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'scheduled' ? 'active bg-info text-white' : 'text-dark' }} py-1 px-3 small fw-bold" href="{{ route('consultant.appointments', ['filter' => 'scheduled']) }}">
                        <i class="fas fa-calendar-alt me-1"></i> Scheduled
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'all' ? 'active bg-dark text-white' : 'text-dark' }} py-1 px-3 small fw-bold" href="{{ route('consultant.appointments', ['filter' => 'all']) }}">
                        <i class="fas fa-layer-group me-1"></i> All Appointments
                    </a>
                </li>
            </ul>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search patient, phone, ref..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                @if(request('search'))
                    <a href="{{ route('consultant.appointments', ['filter' => $filter]) }}" class="btn btn-sm btn-link text-muted">Reset</a>
                @endif
            </form>
        </div>
    </div>
</div>

<!-- Appointments List -->
@forelse($appointments as $item)
    <div class="appointment-row shadow-sm">
        <div class="row align-items-center gy-3">
            <!-- Patient Info -->
            <div class="col-lg-3 col-md-4">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon bg-light text-primary border flex-shrink-0" style="width: 38px; height: 38px; font-size: 1rem;">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">{{ $item->name }}</h6>
                        <span class="badge bg-light text-secondary border small mb-1">#{{ $item->reference_no }}</span>
                        <div class="small text-muted"><i class="fas fa-phone-alt me-1"></i>{{ $item->phone ?: 'Not provided' }}</div>
                        <div class="small text-muted text-truncate" style="max-width: 190px;"><i class="fas fa-envelope me-1"></i>{{ $item->email ?: 'Not provided' }}</div>
                    </div>
                </div>
            </div>

            <!-- Health Goals & Order Context -->
            <div class="col-lg-3 col-md-4">
                <div>
                    @if($item->is_order_followup)
                        <span class="badge bg-primary text-white mb-1"><i class="fas fa-shopping-bag me-1"></i> New Order Follow-up</span>
                        <span class="badge bg-warning-subtle text-dark border border-warning mb-1">Zoom Only</span>
                    @else
                        <span class="badge {{ $item->session_type === 'follow_up' ? 'bg-info text-dark' : 'bg-success' }} mb-1">
                            {{ $item->session_type === 'follow_up' ? 'Follow-up Session' : 'Lifestyle Consultation' }}
                        </span>
                    @endif
                    <div class="small text-dark fw-semibold text-truncate" style="max-width: 260px;" title="{{ $item->concern }}">
                        {{ $item->concern ?: 'Nutritional & Dietary Review' }}
                    </div>
                    <small class="text-muted d-block mt-1">
                        Age: {{ $item->age_range ?: 'N/A' }} &bull; Diet: {{ ucfirst($item->dietary_preference ?: 'Standard') }}
                    </small>
                </div>
            </div>

            <!-- Zoom Video Status & Link -->
            <div class="col-lg-3 col-md-4">
                @if($item->zoom_link)
                    <div class="mb-1">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            <i class="fas fa-video me-1"></i> Zoom Ready
                        </span>
                        @if($item->scheduled_at)
                            <div class="small text-dark fw-bold mt-1">
                                <i class="fas fa-calendar-alt text-muted me-1"></i>{{ $item->scheduled_at->format('d M, h:i A') }}
                            </div>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-1 mt-1">
                        <a href="{{ $item->zoom_link }}" target="_blank" class="btn btn-sm btn-zoom py-1 px-2 d-inline-flex align-items-center gap-1" title="Launch Zoom Video Call">
                            <i class="fas fa-video"></i> Start Zoom Call
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="navigator.clipboard.writeText('{{ $item->zoom_link }}'); alert('Zoom link copied!');" title="Copy Link">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div class="small text-muted mt-1 font-monospace" style="font-size: 0.72rem;">
                        ID: {{ $item->zoom_meeting_id ?: 'Generated' }} | Pass: {{ $item->zoom_passcode ?: 'prakruti' }}
                    </div>
                @else
                    <form method="POST" action="{{ route('consultant.appointments.generateZoom', $item) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary fw-bold d-inline-flex align-items-center gap-1">
                            <i class="fas fa-bolt text-warning"></i> Generate Zoom Link
                        </button>
                    </form>
                    <div class="small text-danger mt-1">
                        <i class="fas fa-exclamation-triangle me-1"></i> No video link created yet
                    </div>
                @endif

                @if($item->customer_notified_at)
                    <div class="small text-success mt-1">
                        <i class="fas fa-check-circle me-1"></i>Patient notified via {{ strtoupper($item->customer_notified_channel) }} ({{ $item->customer_notified_at->format('d M') }})
                    </div>
                @endif
            </div>

            <!-- Action Buttons: WhatsApp, Email, Schedule, Complete -->
            <div class="col-lg-3 col-md-12 text-lg-end">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                    <!-- WhatsApp Dispatch Button -->
                    <button type="button" class="btn btn-sm btn-whatsapp d-inline-flex align-items-center gap-1" 
                        onclick="openCustomerNotifyModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ addslashes($item->phone ?? '') }}', '{{ addslashes($item->email ?? '') }}', '{{ addslashes($item->zoom_link ?? '') }}', '{{ $item->scheduled_at ? $item->scheduled_at->format('d M Y, h:i A') : '' }}', 'whatsapp')">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </button>

                    <!-- Email Dispatch Button -->
                    <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                        onclick="openCustomerNotifyModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ addslashes($item->phone ?? '') }}', '{{ addslashes($item->email ?? '') }}', '{{ addslashes($item->zoom_link ?? '') }}', '{{ $item->scheduled_at ? $item->scheduled_at->format('d M Y, h:i A') : '' }}', 'email')">
                        <i class="fas fa-envelope"></i> Email
                    </button>

                    <!-- Schedule / Reschedule Modal Trigger -->
                    <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#scheduleModal{{ $item->id }}" title="Schedule or edit meeting">
                        <i class="fas fa-calendar-plus"></i>
                    </button>

                    <!-- Complete Session -->
                    @if($item->video_status !== 'completed')
                        <form method="POST" action="{{ route('consultant.appointments.complete', $item) }}" class="d-inline" onsubmit="return confirm('Mark this consultation session as completed?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success" title="Mark Consultation Completed">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2">
                            <i class="fas fa-check-circle me-1"></i> Completed
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Modal for item -->
    <div class="modal fade" id="scheduleModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('consultant.appointments.schedule', $item) }}">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="fas fa-video text-primary me-2"></i>Schedule Zoom Consultation
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Patient Name</label>
                            <input type="text" class="form-control" value="{{ $item->name }} ({{ $item->phone }})" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Appointment Date &amp; Time (IST)</label>
                            <input type="datetime-local" class="form-control" name="scheduled_at" value="{{ $item->scheduled_at ? $item->scheduled_at->format('Y-m-d\TH:i') : now()->addHour()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Assigned Doctor / Consultant</label>
                            <select class="form-select" name="assigned_to">
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->name }}" @selected($item->assigned_to === $doc->name || ($loop->first && !$item->assigned_to))>
                                        {{ $doc->name }} ({{ $doc->specialization }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Zoom Meeting Link</label>
                            <div class="input-group">
                                <input type="url" class="form-control" name="zoom_link" id="zoomLinkInput{{ $item->id }}" value="{{ $item->zoom_link }}" placeholder="https://us05web.zoom.us/j/...">
                                <button type="button" class="btn btn-outline-primary" onclick="autoGenerateZoomForModal({{ $item->id }})">
                                    <i class="fas fa-magic me-1"></i> Auto Generate
                                </button>
                            </div>
                            <small class="text-muted">Click Auto Generate to create an instant unique Zoom link.</small>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-muted">Meeting ID</label>
                                <input type="text" class="form-control form-control-sm" name="zoom_meeting_id" id="zoomIdInput{{ $item->id }}" value="{{ $item->zoom_meeting_id }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Passcode</label>
                                <input type="text" class="form-control form-control-sm" name="zoom_passcode" id="zoomPassInput{{ $item->id }}" value="{{ $item->zoom_passcode }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Consultant Internal Notes</label>
                            <textarea class="form-control" name="consultant_notes" rows="2" placeholder="Dietary observations, previous medical reports, questions...">{{ $item->consultant_notes }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">
                            <i class="fas fa-save me-1"></i> Save Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="card shadow-sm border-0 py-5 text-center text-muted">
        <div class="card-body">
            <i class="fas fa-calendar-check fa-3x text-secondary mb-3"></i>
            <h5 class="fw-bold">No {{ $filter === 'pending' ? 'Pending' : ($filter === 'followup' ? 'Order Follow-up' : '') }} Appointments Found</h5>
            <p class="small text-muted mb-0">All patient consultation requests in this view are up-to-date.</p>
        </div>
    </div>
@endforelse

<div class="mt-4">
    {{ $appointments->links() }}
</div>

<!-- Modal: Notify Customer via WhatsApp / Email with Zoom Details -->
<div class="modal fade" id="customerNotifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="customerNotifyForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="customerNotifyTitle">
                        <i class="fas fa-paper-plane text-warning me-2"></i>Send Video Consultation Link to Patient
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="fw-bold text-dark" id="modalPatientName">-</div>
                        <div class="small text-muted" id="modalPatientPhone"><i class="fas fa-phone-alt me-1"></i> -</div>
                        <div class="small text-muted" id="modalPatientEmail"><i class="fas fa-envelope me-1"></i> -</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Notification Channel</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="channel" id="channelWa" value="whatsapp" checked>
                            <label class="btn btn-outline-success fw-bold" for="channelWa">
                                <i class="fab fa-whatsapp me-1"></i> WhatsApp
                            </label>
                            <input type="radio" class="btn-check" name="channel" id="channelEmail" value="email">
                            <label class="btn btn-outline-danger fw-bold" for="channelEmail">
                                <i class="fas fa-envelope me-1"></i> Email
                            </label>
                            <input type="radio" class="btn-check" name="channel" id="channelBoth" value="both">
                            <label class="btn btn-outline-primary fw-bold" for="channelBoth">
                                <i class="fas fa-bullhorn me-1"></i> Both
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Zoom Meeting Link</label>
                        <input type="url" class="form-control" name="custom_zoom_link" id="modalZoomLink" required placeholder="https://us05web.zoom.us/j/...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Meeting Date &amp; Time (Display in message)</label>
                        <input type="text" class="form-control" name="custom_time" id="modalTime" placeholder="e.g. 18 Nov 2026, 05:00 PM (IST)">
                    </div>

                    <div class="alert alert-info py-2 small mb-0">
                        <i class="fas fa-info-circle me-1"></i> Clicking <strong>Send Notification</strong> will record the dispatch timestamp and open the WhatsApp/Gmail app with the pre-filled Zoom meeting details.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold">
                        <i class="fas fa-paper-plane me-1"></i> Send &amp; Launch Link
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: + New Order Follow-up via Zoom -->
<div class="modal fade" id="newOrderFollowupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="{{ route('consultant.appointments.createOrderFollowup') }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-video me-2"></i>New Order Follow-up (Connect Patient via Zoom)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted">
                        Protocol requirement: <strong>All patients are connected via Zoom for new order follow-up</strong> to review meal planning, pantry items, and health goals.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Select Recent Order (Optional)</label>
                        <select class="form-select form-select-sm" name="order_id" onchange="populateOrderPatient(this)">
                            <option value="">-- Manual Patient Entry --</option>
                            @foreach($recentOrders as $ro)
                                <option value="{{ $ro->id }}" 
                                    data-name="{{ $ro->user?->name ?? 'Customer' }}" 
                                    data-phone="{{ $ro->user?->phone ?? '' }}" 
                                    data-email="{{ $ro->user?->email ?? '' }}">
                                    Order #{{ $ro->order_num }} &bull; {{ $ro->user?->name ?? 'Customer' }} (₹{{ number_format($ro->total, 0) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Patient Full Name *</label>
                        <input type="text" class="form-control" name="patient_name" id="folPatientName" required placeholder="John Doe">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Mobile Number *</label>
                            <input type="text" class="form-control" name="patient_phone" id="folPatientPhone" required placeholder="9876543210">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Email Address</label>
                            <input type="email" class="form-control" name="patient_email" id="folPatientEmail" placeholder="patient@example.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Consultation Slot Date &amp; Time</label>
                        <input type="datetime-local" class="form-control" name="scheduled_at" value="{{ now()->addDay()->format('Y-m-d\T17:00') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Assigned Consultant</label>
                        <select class="form-select" name="assigned_to">
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->name }}">{{ $doc->name }} ({{ $doc->specialization }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Order Notes / Dietary Focus</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Special focus on oil / bilona ghee cooking orientation or gluten alternatives..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold">
                        <i class="fas fa-check-circle me-1"></i> Create &amp; Generate Zoom Meeting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function autoGenerateZoomForModal(id) {
    const random10 = Math.floor(1000000000 + Math.random() * 9000000000);
    const pass = Math.random().toString(36).substring(2, 8);
    const link = 'https://us05web.zoom.us/j/' + random10 + '?pwd=' + pass;

    const linkInput = document.getElementById('zoomLinkInput' + id);
    const idInput = document.getElementById('zoomIdInput' + id);
    const passInput = document.getElementById('zoomPassInput' + id);

    if (linkInput) linkInput.value = link;
    if (idInput) idInput.value = random10.toString().replace(/(\d{3})(\d{4})(\d{3})/, '$1 $2 $3');
    if (passInput) passInput.value = pass;
}

function openCustomerNotifyModal(id, name, phone, email, zoomLink, timeStr, defaultChannel) {
    document.getElementById('modalPatientName').textContent = name;
    document.getElementById('modalPatientPhone').textContent = phone || 'No phone provided';
    document.getElementById('modalPatientEmail').textContent = email || 'No email provided';

    if (!zoomLink) {
        const random10 = Math.floor(1000000000 + Math.random() * 9000000000);
        const pass = Math.random().toString(36).substring(2, 8);
        zoomLink = 'https://us05web.zoom.us/j/' + random10 + '?pwd=' + pass;
    }

    document.getElementById('modalZoomLink').value = zoomLink;
    document.getElementById('modalTime').value = timeStr || 'Tomorrow at 5:00 PM (IST)';

    if (defaultChannel === 'whatsapp') {
        document.getElementById('channelWa').checked = true;
    } else if (defaultChannel === 'email') {
        document.getElementById('channelEmail').checked = true;
    } else {
        document.getElementById('channelBoth').checked = true;
    }

    const form = document.getElementById('customerNotifyForm');
    form.action = '/consultant/appointments/' + id + '/notify-customer';

    const modal = new bootstrap.Modal(document.getElementById('customerNotifyModal'));
    modal.show();
}

function populateOrderPatient(selectElem) {
    const selected = selectElem.options[selectElem.selectedIndex];
    if (selected && selected.value) {
        document.getElementById('folPatientName').value = selected.getAttribute('data-name') || '';
        document.getElementById('folPatientPhone').value = selected.getAttribute('data-phone') || '';
        document.getElementById('folPatientEmail').value = selected.getAttribute('data-email') || '';
    }
}
</script>
@endsection
