@extends('doctor.layouts.app')

@section('title', 'Patient Appointment #' . $consultation->reference_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1 fw-bold text-dark">
            <i class="fas fa-user-circle text-success me-2"></i>Patient: {{ $consultation->name }}
        </h2>
        <div class="text-muted small d-flex align-items-center gap-2 flex-wrap mt-1">
            <span>Ref #<strong>{{ $consultation->reference_no }}</strong></span>
            &bull;
            @if($consultation->visit_number > 1 || $consultation->is_returning_patient)
                <span class="badge bg-primary text-white shadow-sm px-2.5 py-1">
                    <i class="fas fa-redo me-1"></i> 2nd Time Patient (Follow-up #{{ max(2, $consultation->visit_number) }})
                </span>
            @else
                <span class="badge bg-success px-2.5 py-1">
                    <i class="fas fa-seedling me-1"></i> 1st Time Patient (Initial Visit)
                </span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('doctor.appointments.destroy', $consultation) }}" onsubmit="return confirm('Permanently delete consultation #{{ $consultation->reference_no }} from workspace?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-trash-alt me-1"></i> Delete
            </button>
        </form>
        <a href="{{ route('doctor.appointments') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Workspace
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Patient Details -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <span class="fw-bold fs-6 text-dark"><i class="fas fa-id-card text-success me-2"></i>Patient Profile</span>
            </div>
            <div class="card-body">
                <dl class="row mb-0 gy-2">
                    <dt class="col-sm-4 text-muted">Full Name</dt>
                    <dd class="col-sm-8 fw-bold text-dark">{{ $consultation->name }}</dd>

                    <dt class="col-sm-4 text-muted">Phone Number</dt>
                    <dd class="col-sm-8">
                        @if($consultation->phone)
                            <a href="tel:{{ $consultation->phone }}" class="fw-bold text-success text-decoration-none">
                                <i class="fas fa-phone-alt me-1"></i>{{ $consultation->phone }}
                            </a>
                            @php
                                $pClean = preg_replace('/[^0-9]/', '', (string)$consultation->phone);
                                if (strlen($pClean) === 10) $pClean = '91' . $pClean;
                            @endphp
                            <a href="https://wa.me/{{ $pClean }}" target="_blank" class="badge bg-success-subtle text-success text-decoration-none ms-2" title="Chat on WhatsApp">
                                <i class="fab fa-whatsapp"></i> Chat
                            </a>
                        @else
                            <span class="text-muted">Not provided</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8">{{ $consultation->email ?: 'Not provided' }}</dd>

                    <dt class="col-sm-4 text-muted">Age / Diet</dt>
                    <dd class="col-sm-8">{{ $consultation->age_range ?: 'N/A' }} &bull; {{ ucfirst($consultation->dietary_preference ?: 'Standard') }}</dd>

                    <dt class="col-sm-4 text-muted">Preferred Time</dt>
                    <dd class="col-sm-8">{{ $consultation->preferred_time ?: 'Flexible' }}</dd>

                    <dt class="col-sm-4 text-muted">Scheduled Slot</dt>
                    <dd class="col-sm-8 fw-bold text-primary">
                        {{ $consultation->scheduled_at ? $consultation->scheduled_at->format('d M Y, h:i A') : 'Awaiting confirmation from Admin' }}
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold fs-6 text-danger"><i class="fas fa-notes-medical me-2"></i>Patient Health Problem &amp; Goals</span>
                <span class="badge bg-light text-secondary border small">{{ $consultation->clinical_consultation_type }}</span>
            </div>
            <div class="card-body">
                @if(count($consultation->clinical_focus_areas) > 0)
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">Selected Focus Areas &amp; Health Goals:</label>
                        <div class="d-flex flex-wrap gap-1.5">
                            @foreach($consultation->clinical_focus_areas as $area)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fs-7 fw-bold">
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
                                        <i class="fas fa-notes-medical me-1 text-success"></i><strong>Previous Prescription:</strong> {{ $past->doctor_notes }}
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    <!-- Video Call & Notes -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mb-4" style="border-top: 4px solid #2d8cff !important;">
            <div class="card-header bg-white py-3 border-bottom">
                <span class="fw-bold fs-6 text-primary"><i class="fas fa-video me-2"></i>Video Consultation Call</span>
            </div>
            <div class="card-body">
                @if($consultation->zoom_link)
                    <div class="p-3 bg-primary-subtle border border-primary-subtle rounded text-center mb-3">
                        <div class="small text-muted mb-2">Meeting Link Attached by Admin:</div>
                        <a href="{{ $consultation->zoom_link }}" target="_blank" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                            <i class="fas fa-video me-1"></i> Join Zoom Video Call &nearr;
                        </a>
                        <div class="mt-2 text-muted small text-truncate">{{ $consultation->zoom_link }}</div>
                    </div>
                @else
                    <div class="alert alert-warning small">
                        <i class="fas fa-clock me-1"></i> Zoom meeting link has not been set by Admin yet. You can also paste your own Google Meet / Zoom link below.
                    </div>
                @endif

                <form method="POST" action="{{ route('doctor.appointments.meetingLink', $consultation) }}">
                    @csrf
                    <label class="form-label fw-bold small text-dark">Update Meeting Link (Zoom / Google Meet)</label>
                    <div class="input-group">
                        <input type="url" name="meeting_link" class="form-control" value="{{ $consultation->zoom_link }}" placeholder="https://zoom.us/j/... or Google Meet" required>
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fas fa-paper-plane me-1"></i> Save &amp; Send to Admin Panel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4" style="border-top: 4px solid #198754 !important;">
            <div class="card-header bg-white py-3 border-bottom">
                <span class="fw-bold fs-6 text-success"><i class="fas fa-clipboard-check me-2"></i>Clinical Prescription &amp; Notes</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('doctor.appointments.notes', $consultation) }}">
                    @csrf
                    <div class="mb-3">
                        <textarea name="doctor_notes" class="form-control" rows="5" placeholder="Record dietary advice, recommended whole foods, herbs, and routine changes...">{{ $consultation->doctor_notes }}</textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-success fw-bold">
                            <i class="fas fa-save me-1"></i> Save Clinical Notes
                        </button>

                        @if($consultation->video_status !== 'completed')
                            <button type="submit" formaction="{{ route('doctor.appointments.complete', $consultation) }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-check-circle me-1"></i> Mark Consultation Completed
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection