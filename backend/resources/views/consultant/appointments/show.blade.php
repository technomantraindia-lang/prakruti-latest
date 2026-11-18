@extends('consultant.layouts.app')

@section('title', 'Appointment #' . $consultation->reference_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1 fw-bold text-dark">
            <i class="fas fa-video text-primary me-2"></i>Consultation #{{ $consultation->reference_no }}
        </h2>
        <span class="text-muted small">
            Patient: <strong>{{ $consultation->name }}</strong> &bull; 
            Status: 
            @if($consultation->video_status === 'completed')
                <span class="badge bg-success">Completed</span>
            @elseif($consultation->video_status === 'link_shared')
                <span class="badge bg-info">Zoom Link Shared</span>
            @elseif($consultation->zoom_link)
                <span class="badge bg-primary">Zoom Scheduled</span>
            @else
                <span class="badge bg-danger">Pending Video Link</span>
            @endif
        </span>
    </div>
    <div class="d-flex gap-2">
        @if($consultation->zoom_link)
            <a href="{{ $consultation->zoom_link }}" target="_blank" class="btn btn-zoom fw-bold">
                <i class="fas fa-video me-1"></i> Start Zoom Call Now
            </a>
        @endif
        <a href="{{ route('consultant.appointments') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Appointments
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Patient Profile & Health Goals -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6 text-dark"><i class="fas fa-user-circle text-primary me-2"></i>Patient Profile</span>
                <span class="badge bg-light text-dark border">Ref #{{ $consultation->reference_no }}</span>
            </div>
            <div class="card-body">
                <dl class="row mb-0 gy-2">
                    <dt class="col-sm-4 text-muted">Full Name</dt>
                    <dd class="col-sm-8 fw-bold text-dark">{{ $consultation->name }}</dd>

                    <dt class="col-sm-4 text-muted">Mobile Number</dt>
                    <dd class="col-sm-8">
                        @if($consultation->phone)
                            <a href="tel:{{ $consultation->phone }}" class="text-decoration-none fw-bold text-primary">
                                <i class="fas fa-phone-alt me-1"></i>{{ $consultation->phone }}
                            </a>
                        @else
                            <span class="text-muted">Not provided</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Email Address</dt>
                    <dd class="col-sm-8">{{ $consultation->email ?: 'Not provided' }}</dd>

                    <dt class="col-sm-4 text-muted">Session Category</dt>
                    <dd class="col-sm-8">
                        @if($consultation->is_order_followup)
                            <span class="badge bg-primary">New Order Follow-up</span>
                            <span class="badge bg-warning-subtle text-dark border border-warning">Zoom Only</span>
                        @else
                            <span class="badge bg-success">Lifestyle Medicine Initial</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Age / Diet</dt>
                    <dd class="col-sm-8">
                        {{ $consultation->age_range ?: 'N/A' }} &bull; {{ ucfirst($consultation->dietary_preference ?: 'Standard') }}
                    </dd>

                    <dt class="col-sm-4 text-muted">Preferred Time</dt>
                    <dd class="col-sm-8">{{ $consultation->preferred_time ?: 'Flexible' }}</dd>
                </dl>
            </div>
        </div>

        <!-- Health Concern Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <span class="fw-bold fs-6 text-dark"><i class="fas fa-notes-medical text-danger me-2"></i>Health Concern / Chief Symptoms</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; line-height: 1.6;">{{ $consultation->concern ?: 'No detailed symptoms entered.' }}</div>
            </div>
        </div>
    </div>

    <!-- Right Column: Zoom Video Consultation & Scheduling -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mb-4" style="border-top: 4px solid var(--zoom-blue) !important;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6 text-primary"><i class="fas fa-video me-2"></i>Zoom Meeting Setup</span>
                @if($consultation->zoom_link)
                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                @else
                    <span class="badge bg-danger">Link Needed</span>
                @endif
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('consultant.appointments.schedule', $consultation) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Zoom Video Meeting Link</label>
                        <div class="input-group">
                            <input type="url" name="zoom_link" id="zoomLinkField" class="form-control" value="{{ $consultation->zoom_link }}" placeholder="https://us05web.zoom.us/j/..." required>
                            <button type="button" class="btn btn-outline-primary" onclick="autoGenerateDetailZoom()">
                                <i class="fas fa-bolt me-1"></i> Auto-Generate
                            </button>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-muted">Meeting ID</label>
                            <input type="text" name="zoom_meeting_id" id="zoomIdField" class="form-control form-control-sm" value="{{ $consultation->zoom_meeting_id }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted">Passcode</label>
                            <input type="text" name="zoom_passcode" id="zoomPassField" class="form-control form-control-sm" value="{{ $consultation->zoom_passcode }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Scheduled Date &amp; Time (IST)</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ $consultation->scheduled_at ? $consultation->scheduled_at->format('Y-m-d\TH:i') : '' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Assigned Doctor</label>
                        <select class="form-select" name="assigned_to">
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->name }}" @selected($consultation->assigned_to === $doc->name || ($loop->first && !$consultation->assigned_to))>
                                    {{ $doc->name }} ({{ $doc->specialization }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Consultant Clinical Notes</label>
                        <textarea name="consultant_notes" class="form-control" rows="3" placeholder="Dietary recommendations, meal timings, organic substitution plan...">{{ $consultation->consultant_notes }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fas fa-save me-1"></i> Update Meeting &amp; Notes
                        </button>

                        @if($consultation->video_status !== 'completed')
                            <form method="POST" action="{{ route('consultant.appointments.complete', $consultation) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success fw-bold" onclick="return confirm('Mark consultation as finished?');">
                                    <i class="fas fa-check-circle me-1"></i> Mark Completed
                                </button>
                            </form>
                        @endif
                    </div>
                </form>

                <hr class="my-4">

                <!-- Patient Notification Section -->
                <div class="fw-bold small text-dark mb-2"><i class="fas fa-paper-plane text-success me-1"></i> Inform Customer with Video Link:</div>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('consultant.appointments.notifyCustomer', $consultation) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="channel" value="whatsapp">
                        <button type="submit" class="btn btn-whatsapp d-inline-flex align-items-center gap-1">
                            <i class="fab fa-whatsapp"></i> Send via WhatsApp
                        </button>
                    </form>

                    <form method="POST" action="{{ route('consultant.appointments.notifyCustomer', $consultation) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="channel" value="email">
                        <button type="submit" class="btn btn-outline-danger d-inline-flex align-items-center gap-1">
                            <i class="fas fa-envelope"></i> Send via Email (Gmail)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function autoGenerateDetailZoom() {
    const random10 = Math.floor(1000000000 + Math.random() * 9000000000);
    const pass = Math.random().toString(36).substring(2, 8);
    const link = 'https://us05web.zoom.us/j/' + random10 + '?pwd=' + pass;

    document.getElementById('zoomLinkField').value = link;
    document.getElementById('zoomIdField').value = random10.toString().replace(/(\d{3})(\d{4})(\d{3})/, '$1 $2 $3');
    document.getElementById('zoomPassField').value = pass;
}
</script>
@endsection
