<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="fas fa-info-circle me-1"></i> Guideline Overview</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Guideline Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $item->title) }}" placeholder="e.g. ICMR-NIN Dietary Guidelines for Indians (2024)" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Organization / Authority <span class="text-danger">*</span></label>
                        <input type="text" name="organization" class="form-control @error('organization') is-invalid @enderror" value="{{ old('organization', $item->organization) }}" placeholder="e.g. World Health Organization (WHO)" required>
                        @error('organization')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ old('category', $item->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Summary / Scope Description <span class="text-danger">*</span></label>
                    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Describe the dietary guidelines, key findings, daily allowances, or clinical recommendations..." required>{{ old('description', $item->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="fas fa-file-pdf me-1"></i> Document &amp; Link Settings</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Upload PDF Document</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf">
                    <small class="form-text text-muted">Upload a PDF guideline up to 50MB.</small>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    @if($item->file_path)
                        <div class="alert alert-light border mt-2 py-2 px-3 d-flex align-items-center justify-content-between mb-0">
                            <div>
                                <i class="fas fa-file-pdf text-danger me-1"></i> Current file: <strong>{{ basename($item->file_path) }}</strong>
                            </div>
                            <a href="{{ $item->download_url }}" target="_blank" class="btn btn-sm btn-outline-primary">View Current File</a>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">OR Official External Guideline URL</label>
                    <input type="url" name="external_url" class="form-control @error('external_url') is-invalid @enderror" value="{{ old('external_url', $item->external_url) }}" placeholder="https://www.who.int/... or https://nin.res.in/...">
                    <small class="form-text text-muted">Link directly to the official WHO, ICMR, or FSSAI document page.</small>
                    @error('external_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Published Year</label>
                        <input type="text" name="published_year" class="form-control @error('published_year') is-invalid @enderror" value="{{ old('published_year', $item->published_year ?? date('Y')) }}" placeholder="e.g. 2024">
                        @error('published_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estimated File Size</label>
                        <input type="text" name="file_size" class="form-control @error('file_size') is-invalid @enderror" value="{{ old('file_size', $item->file_size) }}" placeholder="e.g. 3.4 MB (Auto-calculated if file uploaded)">
                        @error('file_size')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="fas fa-cog me-1"></i> Status &amp; Placement</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="is_active">Publish in Education Library</label>
                    <small class="form-text text-muted d-block">Visible to users on the storefront Education page.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Display Order</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order ?? 0) }}" min="0">
                    <small class="form-text text-muted">Lower numbers appear first.</small>
                </div>

                <hr class="my-4">

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-1"></i> Save Guideline
                    </button>
                    <a href="{{ route('admin.guidelines.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
