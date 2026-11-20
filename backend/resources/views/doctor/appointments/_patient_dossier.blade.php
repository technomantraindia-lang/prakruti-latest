@php
    $intake = is_array($consultation->patient_intake_data) ? $consultation->patient_intake_data : [];
    $history = is_array($intake['past_history'] ?? null) ? $intake['past_history'] : $intake;
    $meals = is_array($intake['daily_meal_plan'] ?? null) ? $intake['daily_meal_plan'] : ($intake['meal_plan'] ?? []);
    $problems = is_array($intake['current_situation'] ?? null) ? $intake['current_situation'] : [];
    $medications = is_array($intake['medications'] ?? null)
        ? ($intake['medications']['list'] ?? $intake['medications'])
        : [];
    $labFiles = is_array($consultation->lab_reports) ? $consultation->lab_reports : [];
    $value = fn ($key, $fallback = 'Not provided') => filled($intake[$key] ?? null) ? $intake[$key] : $fallback;
@endphp

<div class="card shadow-sm border-0 mb-4 patient-dossier-card" style="border-top: 4px solid #0d6efd !important;">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-file-medical text-primary me-2"></i>Patient Details &amp; Clinical Intake</h5>
            <small class="text-muted">Complete consultation dossier submitted by the patient</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Patient Details</button>
    </div>
    <div class="card-body">
        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-user-circle me-1"></i> 1. Consultation &amp; patient details</h6>
        <div class="row g-3 mb-4">
            @foreach([
                'Consultation date' => $value('consultation_date', $consultation->created_at?->format('d M Y')),
                'Full name' => $value('name', $consultation->name),
                'Gender' => $value('gender'),
                'Date of birth' => $value('date_of_birth', $value('dob')),
                'Age' => $value('age', $consultation->age_range ?: 'Not provided'),
                'Height' => $value('height'),
                'Weight' => $value('weight'),
                'Referred by' => $value('referred_by', 'Self / Direct'),
                'Phone / WhatsApp' => $value('phone', $consultation->phone),
                'Email' => $value('email', $consultation->email ?: 'Not provided'),
                'Preferred contact' => $value('contact_method', $consultation->preferred_contact_method ?: 'Not provided'),
                'Preferred time' => $value('preferred_time', $consultation->preferred_time ?: 'Flexible'),
            ] as $label => $item)
                <div class="col-md-4 col-sm-6"><span class="text-muted small d-block">{{ $label }}</span><strong class="text-dark" style="white-space: pre-wrap;">{{ $item }}</strong></div>
            @endforeach
            <div class="col-12"><span class="text-muted small d-block">Address</span><div class="p-2 bg-light rounded text-dark" style="white-space: pre-wrap;">{{ $value('address') }}</div></div>
        </div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-bullseye me-1"></i> 2. Patient goals</h6>
        <div class="p-3 bg-light rounded border mb-4" style="white-space: pre-wrap;">{{ $value('goals', $consultation->patient_display_concern ?: 'No specific goals provided.') }}</div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-list-ol me-1"></i> 3. Current situation</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light"><tr><th style="width:55px;">#</th><th>Problem, symptoms, duration and how it began</th></tr></thead>
                <tbody>
                @forelse($problems as $index => $problem)
                    <tr><td class="fw-bold text-center">{{ $index + 1 }}</td><td style="white-space: pre-wrap;">{{ is_array($problem) ? implode(' | ', array_filter($problem)) : $problem }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-muted">{{ $consultation->patient_display_concern ?: 'No current problems listed.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-pills me-1"></i> 4. Current medications and supplements</h6>
        <div class="table-responsive mb-2">
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light"><tr><th>Medication / supplement</th><th>For what</th><th>Dosage</th><th>Time</th><th>Since</th><th>Problems</th></tr></thead>
                <tbody>
                @forelse($medications as $medication)
                    @if(is_array($medication) && collect($medication)->filter(fn ($item) => filled($item))->isNotEmpty())
                        <tr><td>{{ $medication['medication'] ?? ($medication['name'] ?? '-') }}</td><td>{{ $medication['for_what'] ?? ($medication['reason'] ?? '-') }}</td><td>{{ $medication['dosage'] ?? '-' }}</td><td>{{ $medication['time'] ?? ($medication['timing'] ?? '-') }}</td><td>{{ $medication['since'] ?? ($medication['duration'] ?? '-') }}</td><td>{{ $medication['problems'] ?? ($medication['side_effects'] ?? '-') }}</td></tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="text-muted">No medication details provided.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-2 bg-light rounded border mb-4"><strong>Other therapy or treatment:</strong> {{ $value('other_therapy', $value('other_therapies')) }}</div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-history me-1"></i> 5. Past history and lifestyle</h6>
        <div class="row g-3 mb-4">
            @foreach([
                'Major past health problems or surgeries' => $history['past_health_problems'] ?? ($history['past_problems_surgeries'] ?? ($history['surgeries'] ?? 'Not provided')),
                'Allergies or food aggravations' => $history['allergies'] ?? ($history['allergies_food_aggravations'] ?? 'Not provided'),
                'Emergency treatments' => $history['emergency_treatments'] ?? 'Not provided',
                'Hospitalizations' => $history['hospitalizations'] ?? ($history['past_hospitalization'] ?? 'Not provided'),
                'Exercise' => $history['exercise'] ?? ($history['regular_exercise'] ?? 'Not provided'),
                'Sleep quality' => $history['sleep_quality'] ?? 'Not provided',
                'Sleep difficulty' => $history['sleep_difficulty'] ?? 'Not provided',
                'Bedtime' => $history['bedtime'] ?? ($history['sleep_bedtime'] ?? 'Not provided'),
                'Wake time' => $history['wake_time'] ?? ($history['sleep_waketime'] ?? 'Not provided'),
                'Major stress' => $history['major_stress'] ?? 'Not provided',
                'Stress details' => $history['stress_details'] ?? 'Not provided',
                'Habits or addictions' => $history['habits'] ?? ($history['habits_addictions'] ?? 'Not provided'),
                'Family history' => $history['family_history'] ?? 'Not provided',
            ] as $label => $item)
                <div class="col-md-6"><div class="p-2 bg-light rounded border h-100"><span class="small fw-bold text-muted d-block">{{ $label }}</span><span class="text-dark" style="white-space: pre-wrap;">{{ $item }}</span></div></div>
            @endforeach
        </div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-utensils me-1"></i> 6. Daily meal plan</h6>
        <div class="row g-2 mb-4">
            @foreach(['wake_up' => 'Wake up', 'breakfast' => 'Breakfast', 'snack' => 'Snack', 'lunch' => 'Lunch', 'dinner' => 'Dinner', 'bedtime' => 'Bedtime', 'anything_in_between' => 'Anything in between'] as $key => $label)
                <div class="col-md-4 col-sm-6"><div class="p-2 bg-light rounded border h-100"><span class="small fw-bold text-muted d-block">{{ $label }}</span><span class="text-dark" style="white-space: pre-wrap;">{{ $meals[$key] ?? '-' }}</span></div></div>
            @endforeach
        </div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-file-invoice me-1"></i> 7. Laboratory tests and scans</h6>
        @if(count($labFiles))
            <div class="row g-2 mb-4">@foreach($labFiles as $report)<div class="col-md-4"><div class="p-2 bg-light rounded border d-flex justify-content-between align-items-center gap-2"><span class="small text-truncate">{{ is_array($report) ? ($report['name'] ?? basename($report['file_path'] ?? 'Report')) : basename($report) }}</span><a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ is_array($report) ? ($report['url'] ?? asset($report['file_path'] ?? '')) : asset($report) }}">View</a></div></div>@endforeach</div>
        @else
            <p class="small text-muted mb-4">No laboratory reports uploaded.</p>
        @endif

        <div class="p-2 bg-light rounded border small text-muted"><i class="fas fa-shield-alt me-1"></i><strong>Disclaimer:</strong> Doctors and nutritionists shall not be held responsible for consequences arising from wrong or incomplete information supplied by the customer.</div>
    </div>
</div>
