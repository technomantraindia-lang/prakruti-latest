@extends('doctor.layouts.app')

@section('title', 'Patient Consultations Workspace')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1 fw-bold text-dark">
            <i class="fas fa-stethoscope text-success me-2"></i>Doctor Consultation Workspace
        </h2>
        <p class="text-muted small mb-0">
            View your verified patient bookings, attach your video meeting links (Google Meet, Zoom, etc.), and record dietary prescriptions.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fw-bold">
            <i class="fas fa-user-md me-1"></i> Dedicated Doctor Portal
        </span>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="fas fa-exclamation-triangle fs-5 text-danger mt-1"></i>
            <div>
                <strong>Please check:</strong>
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

<!-- KPI Cards (5 categories: All Bookings, Upcoming, Today's Calls, Follow-ups, Completed) -->
<div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-2 g-md-3 mb-4">
    <div class="col">
        <a href="{{ route('doctor.appointments', ['tab' => 'all']) }}" class="text-decoration-none">
            <div class="stat-card-doc border-start border-secondary border-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">All Bookings</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ $allCount }}</div>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('doctor.appointments', ['tab' => 'upcoming']) }}" class="text-decoration-none">
            <div class="stat-card-doc border-start border-primary border-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Upcoming</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ $upcomingCount }}</div>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('doctor.appointments', ['tab' => 'today']) }}" class="text-decoration-none">
            <div class="stat-card-doc border-start border-danger border-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Today's Calls</div>
                <div class="fs-4 fw-bold text-danger mt-1">{{ $todayCount }}</div>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('doctor.appointments', ['tab' => 'followup']) }}" class="text-decoration-none">
            <div class="stat-card-doc border-start border-info border-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">2nd Time / Follow-ups</div>
                <div class="fs-4 fw-bold text-info mt-1">{{ $followupCount }}</div>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('doctor.appointments', ['tab' => 'completed']) }}" class="text-decoration-none">
            <div class="stat-card-doc border-start border-success border-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Completed</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ $completedCount }}</div>
            </div>
        </a>
    </div>
</div>

<!-- Tabs & Search -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-2 p-md-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 gap-md-3">
            <ul class="nav nav-pills gap-1 nav-pills-scroll">
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'all' ? 'active bg-dark' : 'text-dark' }} py-1 px-2.5 px-md-3 small fw-bold" href="{{ route('doctor.appointments', ['tab' => 'all']) }}">
                        All ({{ $allCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'upcoming' ? 'active bg-primary' : 'text-dark' }} py-1 px-2.5 px-md-3 small fw-bold" href="{{ route('doctor.appointments', ['tab' => 'upcoming']) }}">
                        <i class="fas fa-calendar-alt me-1"></i> Upcoming ({{ $upcomingCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'today' ? 'active bg-danger' : 'text-dark' }} py-1 px-2.5 px-md-3 small fw-bold" href="{{ route('doctor.appointments', ['tab' => 'today']) }}">
                        <i class="fas fa-video me-1"></i> Today's Calls ({{ $todayCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'followup' ? 'active bg-info text-white' : 'text-dark' }} py-1 px-2.5 px-md-3 small fw-bold" href="{{ route('doctor.appointments', ['tab' => 'followup']) }}">
                        <i class="fas fa-redo me-1"></i> 2nd Time Patients ({{ $followupCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'completed' ? 'active bg-success' : 'text-dark' }} py-1 px-2.5 px-md-3 small fw-bold" href="{{ route('doctor.appointments', ['tab' => 'completed']) }}">
                        <i class="fas fa-check-double me-1"></i> Completed ({{ $completedCount }})
                    </a>
                </li>
            </ul>

            <form method="GET" class="d-flex gap-2 w-100 w-md-auto mt-2 mt-md-0">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="text" name="search" class="form-control form-control-sm flex-grow-1" placeholder="Search patient, phone, ref, concern..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                @if(request('search'))
                    <a href="{{ route('doctor.appointments', ['tab' => $tab]) }}" class="btn btn-sm btn-link text-muted">Reset</a>
                @endif
            </form>
        </div>
    </div>
</div>

<!-- Patient Consultations List -->
@forelse($appointments as $item)
    <div class="appointment-card shadow-sm mb-3">
        <!-- Top Header: Patient Name, Visit Badge, Specialist Badge, Time -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom pb-2.5 mb-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.15rem;">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark d-inline-block">{{ $item->name }}</h5>
                    <span class="badge bg-light text-secondary border font-monospace ms-1">#{{ $item->reference_no }}</span>
                </div>
                @if($item->visit_number > 1 || $item->is_returning_patient)
                    <span class="badge text-white shadow-sm fw-bold px-2.5 py-1" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7) !important;">
                        <i class="fas fa-redo me-1"></i> 2nd Time Patient (Follow-up #{{ max(2, $item->visit_number) }})
                    </span>
                @else
                    <span class="badge bg-success text-white fw-bold px-2.5 py-1">
                        <i class="fas fa-seedling me-1"></i> 1st Time Patient (Initial)
                    </span>
                @endif
                <span class="badge bg-light text-dark border px-2.5 py-1">{{ $item->clinical_consultation_type }}</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if($item->scheduled_at)
                    <span class="badge bg-primary text-white px-2.5 py-1.5 fs-7 fw-semibold">
                        <i class="fas fa-calendar-alt me-1"></i> {{ $item->scheduled_at->format('d M Y, h:i A') }}
                    </span>
                @elseif($item->preferred_time)
                    <span class="badge bg-light text-muted border px-2 py-1">
                        <i class="fas fa-clock me-1"></i> Window: {{ $item->preferred_time }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Middle: Two Columns (Left: Clinical Profile & Goals, Right: Video Link & Doctor Actions) -->
        <div class="row g-3 align-items-start">
            <!-- Patient Clinical & Wellness Profile -->
            <div class="col-lg-7 col-md-12">
                <!-- Health Goals & Focus Areas -->
                <div class="mb-2">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem;">
                        <i class="fas fa-bullseye text-success me-1"></i>Wellness Goals &amp; Focus Areas:
                    </span>
                    @if(count($item->clinical_focus_areas) > 0)
                        <div class="d-flex flex-wrap gap-1.5">
                            @foreach($item->clinical_focus_areas as $area)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold fs-7">
                                    <i class="fas fa-check-circle me-1"></i>{{ $area }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <span class="badge bg-light text-muted border px-2 py-1 small">General Lifestyle &amp; Nutrition Wellness</span>
                    @endif
                </div>

                <!-- Chief Health Complaint Box -->
                <div class="p-2.5 bg-light rounded border mb-2">
                    <div class="small fw-bold text-danger mb-1">
                        <i class="fas fa-notes-medical me-1"></i>Patient's Chief Complaint / Symptoms:
                    </div>
                    <div class="text-dark small" style="white-space: pre-wrap; line-height: 1.5;">
                        {{ $item->patient_display_concern }}
                    </div>
                </div>

                <!-- Lifestyle Habits & Contact Strip -->
                <div class="d-flex flex-wrap gap-3 text-muted small align-items-center pt-1">
                    <div><i class="fas fa-user text-secondary me-1"></i><strong>Age:</strong> {{ $item->age_range ?: 'N/A' }}</div>
                    <div><i class="fas fa-utensils text-success me-1"></i><strong>Diet:</strong> {{ ucfirst($item->dietary_preference ?: 'Standard') }}</div>
                    <div>
                        <i class="fas fa-phone-alt text-primary me-1"></i>
                        @if($item->phone)
                            <a href="tel:{{ $item->phone }}" class="text-decoration-none fw-semibold text-dark">{{ $item->phone }}</a>
                            @php
                                $pClean = preg_replace('/[^0-9]/', '', (string)$item->phone);
                                if (strlen($pClean) === 10) $pClean = '91' . $pClean;
                            @endphp
                            <a href="https://wa.me/{{ $pClean }}" target="_blank" class="badge bg-success-subtle text-success text-decoration-none ms-1" title="Message Patient on WhatsApp">
                                <i class="fab fa-whatsapp"></i> Chat
                            </a>
                        @else
                            <span>Not provided</span>
                        @endif
                    </div>
                    @if($item->email)
                        <div class="text-truncate" style="max-width: 220px;">
                            <i class="fas fa-envelope text-secondary me-1"></i>{{ $item->email }}
                        </div>
                    @endif
                </div>

                @if($item->previous_consultations && $item->previous_consultations->count() > 0)
                    <div class="mt-2 text-primary small fw-semibold">
                        <i class="fas fa-history me-1"></i> Returning Patient: {{ $item->previous_consultations->count() }} past consultation(s) on record.
                    </div>
                @endif
            </div>

            <!-- Video Meeting & Doctor Actions -->
            <div class="col-lg-5 col-md-12">
                <div class="p-3 bg-light rounded border h-100 d-flex flex-column justify-content-between">
                    <!-- Video Call Block -->
                    <div class="mb-3">
                        <div class="small fw-bold text-dark text-uppercase mb-2" style="font-size: 0.72rem;">
                            <i class="fas fa-video text-primary me-1"></i> Video Consultation Link:
                        </div>
                        @if($item->zoom_link)
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ $item->zoom_link }}" target="_blank" class="btn btn-zoom btn-sm py-2 px-3 fw-bold flex-grow-1 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <i class="fas fa-video fs-6"></i> Join Video Call
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-2 px-2.5" data-bs-toggle="modal" data-bs-target="#linkModal{{ $item->id }}" title="Change meeting link">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                            </div>
                            <div class="small text-muted text-truncate mt-1.5 font-monospace" style="font-size: 0.72rem;">
                                {{ $item->zoom_link }}
                            </div>
                        @else
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm py-2 px-3 fw-semibold w-100" data-bs-toggle="modal" data-bs-target="#linkModal{{ $item->id }}">
                                    <i class="fas fa-plus me-1"></i> Attach Meeting Link (Zoom / Meet)
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold flex-fill d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#notesModal{{ $item->id }}">
                            <i class="fas fa-notes-medical"></i> Clinical Notes
                        </button>

                        <a href="{{ route('doctor.appointments.show', $item) }}" class="btn btn-sm btn-outline-info fw-bold flex-fill d-flex align-items-center justify-content-center gap-1">
                            <i class="fas fa-id-card"></i> Patient Details
                        </a>

                        @if($item->video_status !== 'completed')
                            <form method="POST" action="{{ route('doctor.appointments.complete', $item) }}" class="flex-fill" onsubmit="return confirm('Complete meeting with {{ addslashes($item->name) }} and remove from active workspace?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success fw-bold w-100 d-flex align-items-center justify-content-center gap-1" title="Mark consultation completed and remove from active list">
                                    <i class="fas fa-check-circle"></i> Complete
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('doctor.appointments.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete consultation #{{ $item->reference_no }} from workspace?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger px-2.5 py-1.5" title="Delete Consultation">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if($item->doctor_notes)
            <div class="mt-3 pt-2 border-top small bg-light p-2.5 rounded">
                <strong class="text-success"><i class="fas fa-file-medical-alt me-1"></i> Today's Clinical Notes &amp; Prescription:</strong>
                <div class="text-dark mt-1" style="white-space: pre-wrap;">{{ $item->doctor_notes }}</div>
            </div>
        @endif

        @php
            $pastNotes = $item->previous_consultations ? $item->previous_consultations->pluck('doctor_notes')->filter()->first() : null;
        @endphp
        @if($pastNotes)
            <div class="mt-2 p-2.5 bg-info bg-opacity-10 border border-info border-opacity-25 rounded small text-dark">
                <i class="fas fa-history text-info me-1"></i><strong>Notes from Patient's Previous Visit:</strong>
                <div class="text-secondary mt-1" style="white-space: pre-wrap;">{{ $pastNotes }}</div>
            </div>
        @endif
    </div>

    <!-- Set/Edit Meeting Link Modal for Item -->
    <div class="modal fade" id="linkModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form method="POST" action="{{ route('doctor.appointments.meetingLink', $item) }}">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-video me-2"></i>Set Video Meeting Link - {{ $item->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="p-2.5 bg-light rounded border mb-3 small">
                            <strong>Patient:</strong> {{ $item->name }} (#{{ $item->reference_no }})<br>
                            <strong>Specialty:</strong> {{ $item->clinical_consultation_type }}
                        </div>
                        <p class="text-muted small mb-3">
                            Paste your actual video meeting URL below (Google Meet, Zoom, etc.). Saving this link immediately sends and synchronizes it with the <strong>Admin Panel</strong> and <strong>Customer Dashboard</strong>!
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Meeting / Video Call URL *</label>
                            <input type="url" name="meeting_link" class="form-control" value="{{ $item->zoom_link }}" required placeholder="https://meet.google.com/... or https://zoom.us/j/...">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">
                            <i class="fas fa-paper-plane me-1"></i> Save &amp; Send to Admin Panel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Clinical Notes Modal for Item -->
    <div class="modal fade" id="notesModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <form method="POST" action="{{ route('doctor.appointments.notes', $item) }}">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-stethoscope me-2"></i>Clinical Notes &amp; Prescription - {{ $item->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <strong class="text-success fs-6"><i class="fas fa-notes-medical me-1"></i> Patient Clinical Dossier:</strong>
                                <span class="badge bg-white text-secondary border small">{{ $item->clinical_consultation_type }}</span>
                            </div>

                            @if(count($item->clinical_focus_areas) > 0)
                                <div class="mb-2">
                                    <span class="small text-muted fw-bold d-block mb-1">Wellness Goals &amp; Focus Areas:</span>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($item->clinical_focus_areas as $area)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small fw-bold">
                                                <i class="fas fa-check me-1"></i>{{ $area }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mb-2">
                                <span class="small text-muted fw-bold d-block mb-1">Chief Health Complaint:</span>
                                <div class="p-2.5 bg-white rounded border small text-dark" style="white-space: pre-wrap; max-height: 120px; overflow-y: auto;">
                                    {{ $item->patient_display_concern }}
                                </div>
                            </div>

                            <div class="text-muted small mt-2">
                                <i class="fas fa-user me-1"></i>Age: <strong>{{ $item->age_range ?: 'N/A' }}</strong> &bull;
                                <i class="fas fa-utensils ms-2 me-1"></i>Diet: <strong>{{ ucfirst($item->dietary_preference ?: 'Standard') }}</strong> &bull;
                                <i class="fas fa-clock ms-2 me-1"></i>Preferred: <strong>{{ $item->preferred_time ?: 'Flexible' }}</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">Doctor Advice, Dietary Prescription &amp; Clinical Summary *</label>
                            <textarea name="doctor_notes" class="form-control" rows="6" required placeholder="Enter therapeutic diet recommendation, organic staples plan, lifestyle habits, herbs, follow-up recommendations...">{{ $item->doctor_notes }}</textarea>
                            <small class="text-muted">These notes are permanently archived in the patient's record and viewable in future consultations.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                            <i class="fas fa-save me-1"></i> Save Clinical Prescription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Full Patient Profile Modal for Item -->
    <div class="modal fade" id="profileModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-id-card text-success me-2"></i>Full Patient Profile - {{ $item->name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
                                    <i class="fas fa-user-circle text-primary me-1"></i> Patient Demographics
                                </h6>
                                <dl class="row mb-0 gy-1 small">
                                    <dt class="col-5 text-muted">Name</dt>
                                    <dd class="col-7 fw-bold text-dark">{{ $item->name }}</dd>

                                    <dt class="col-5 text-muted">Reference</dt>
                                    <dd class="col-7 font-monospace">#{{ $item->reference_no }}</dd>

                                    <dt class="col-5 text-muted">Visit Type</dt>
                                    <dd class="col-7">
                                        @if($item->visit_number > 1 || $item->is_returning_patient)
                                            <span class="badge text-white px-2 py-0.5" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7) !important;">
                                                Follow-up (#{{ max(2, $item->visit_number) }})
                                            </span>
                                        @else
                                            <span class="badge bg-success text-white px-2 py-0.5">Initial Visit</span>
                                        @endif
                                    </dd>

                                    <dt class="col-5 text-muted">Phone</dt>
                                    <dd class="col-7">{{ $item->phone ?: 'Not provided' }}</dd>

                                    <dt class="col-5 text-muted">Email</dt>
                                    <dd class="col-7">{{ $item->email ?: 'Not provided' }}</dd>

                                    <dt class="col-5 text-muted">Age Bracket</dt>
                                    <dd class="col-7">{{ $item->age_range ?: 'N/A' }}</dd>

                                    <dt class="col-5 text-muted">Diet Habit</dt>
                                    <dd class="col-7">{{ ucfirst($item->dietary_preference ?: 'Standard') }}</dd>

                                    <dt class="col-5 text-muted">Preferred Time</dt>
                                    <dd class="col-7">{{ $item->preferred_time ?: 'Flexible' }}</dd>
                                </dl>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
                                    <i class="fas fa-notes-medical text-danger me-1"></i> Clinical Goals &amp; Concern
                                </h6>
                                <div class="mb-2">
                                    <span class="small text-muted fw-bold d-block mb-1">Focus Areas:</span>
                                    @if(count($item->clinical_focus_areas) > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($item->clinical_focus_areas as $area)
                                                <span class="badge bg-success text-white px-2 py-0.5 small">{{ $area }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small">General Wellness</span>
                                    @endif
                                </div>

                                <div class="mb-2">
                                    <span class="small text-muted fw-bold d-block mb-1">Chief Health Complaint:</span>
                                    <div class="p-2 bg-white rounded border small text-dark" style="white-space: pre-wrap;">
                                        {{ $item->patient_display_concern }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($item->previous_consultations && $item->previous_consultations->count() > 0)
                        <div class="mt-3">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
                                <i class="fas fa-history text-primary me-1"></i> Previous Consultation History ({{ $item->previous_consultations->count() }})
                            </h6>
                            <div class="list-group list-group-flush border rounded">
                                @foreach($item->previous_consultations as $past)
                                    <div class="list-group-item p-2.5 small">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>#{{ $past->reference_no }}</strong>
                                            <span class="badge bg-light text-muted border">{{ $past->created_at?->format('d M Y') }}</span>
                                        </div>
                                        <div class="text-muted mt-1">Doctor: <strong>{{ $past->doctor_display_name }}</strong></div>
                                        @if($past->doctor_notes)
                                            <div class="p-2 bg-light rounded mt-1 text-dark" style="white-space: pre-wrap;">
                                                <strong>Prescription:</strong> {{ $past->doctor_notes }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    @if($item->zoom_link)
                        <a href="{{ $item->zoom_link }}" target="_blank" class="btn btn-primary btn-sm fw-bold">
                            <i class="fas fa-video me-1"></i> Join Video Call
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="card shadow-sm border-0 py-5 text-center text-muted">
        <div class="card-body">
            <i class="fas fa-calendar-check fa-3x text-secondary mb-3"></i>
            <h5 class="fw-bold">No Consultations in this view</h5>
            <p class="small text-muted mb-0">
                Patients will automatically appear here when approved by Admin.
            </p>
        </div>
    </div>
@endforelse

<div class="mt-4">
    {{ $appointments->links() }}
</div>
@endsection
