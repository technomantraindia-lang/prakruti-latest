<div class="mb-3">
    <label class="form-label fw-semibold">Doctor name <span class="text-danger">*</span></label>
    <input name="name" class="form-control" value="{{ old('name', $doctor?->name) }}" placeholder="Dr. Ramesh Sharma" required>
</div>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Specialization</label>
        <input name="specialization" class="form-control" value="{{ old('specialization', $doctor?->specialization) }}" placeholder="Lifestyle Medicine">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Experience</label>
        <input name="experience" class="form-control" value="{{ old('experience', $doctor?->experience) }}" placeholder="12+ years">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Mobile / WhatsApp <span class="text-danger">*</span></label>
        <input name="phone" type="tel" class="form-control" value="{{ old('phone', $doctor?->phone) }}" placeholder="+91 98765 43210" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
        <input name="email" type="email" class="form-control" value="{{ old('email', $doctor?->email) }}" placeholder="doctor@example.com" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Qualification</label>
        <input name="qualification" class="form-control" value="{{ old('qualification', $doctor?->qualification) }}" placeholder="MBBS, MD">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Location</label>
        <input name="location" class="form-control" value="{{ old('location', $doctor?->location) }}" placeholder="Vadodara, Gujarat">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Display order</label>
        <input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $doctor?->sort_order ?? 0) }}">
    </div>
    <div class="col-md-8 d-flex align-items-end pb-2">
        <div class="form-check">
            <input name="is_active" value="1" type="checkbox" class="form-check-input" id="active-{{ $doctor?->id ?? 'new' }}" @checked(old('is_active', $doctor?->is_active ?? true))>
            <label class="form-check-label" for="active-{{ $doctor?->id ?? 'new' }}">Available for new consultations</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Short bio</label>
        <textarea name="bio" class="form-control" rows="3" placeholder="A short profile shown on the consultation page">{{ old('bio', $doctor?->bio) }}</textarea>
    </div>
</div>
<button type="submit" class="btn btn-success mt-3"><i class="fas fa-save me-1"></i>{{ $submitLabel }}</button>
