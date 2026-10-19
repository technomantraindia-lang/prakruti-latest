<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="fas fa-info-circle me-1"></i> Guideline Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Guideline Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $item->title) }}" placeholder="e.g. ICMR-NIN Dietary Guidelines for Indians" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Description <span class="text-danger">*</span></label>
                    <textarea name="description" rows="5" class="form-control @error('description') is-invalid @enderror" placeholder="Describe the dietary guidelines, key findings, daily allowances, or clinical recommendations..." required>{{ old('description', $item->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="fas fa-file-pdf me-1"></i> PDF Document Upload</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Upload PDF File</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf">
                    <small class="form-text text-muted">Upload official PDF guideline document (up to 50MB).</small>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    @if($item->file_path)
                        <div class="alert alert-light border mt-3 py-2 px-3 d-flex align-items-center justify-content-between mb-0">
                            <div>
                                <i class="fas fa-file-pdf text-danger me-1"></i> Current file: <strong>{{ basename($item->file_path) }}</strong>
                                @if($item->file_size)
                                    <span class="badge bg-secondary ms-2">{{ $item->file_size }}</span>
                                @endif
                            </div>
                            @if($item->download_url)
                                <a href="{{ $item->download_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i> View Current File
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="fas fa-cog me-1"></i> Publication Status</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-4">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="is_active">Publish in Library</label>
                    <small class="form-text text-muted d-block">Visible to users in the PDF Guidelines Library.</small>
                </div>

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
