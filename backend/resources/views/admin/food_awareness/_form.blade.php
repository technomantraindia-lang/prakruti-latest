@csrf

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-dark fw-bold">📢 News &amp; Alert Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">News Headline / Alert Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control form-control-lg @error('title') is-invalid @enderror" value="{{ old('title', $item->title) }}" placeholder="e.g. Police Arrest Merchants Selling 50kg Expired Spices in Market Raid" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="summary" class="form-label fw-bold">Short News Content (Shown on Card) <span class="text-danger">*</span></label>
                    <textarea name="summary" id="summary" rows="4" class="form-control @error('summary') is-invalid @enderror" placeholder="e.g. Yesterday in India, police and food safety officers arrested individuals selling 50kg of expired spices mixed with toxic chemical colors." required>{{ old('summary', $item->summary) }}</textarea>
                    @error('summary')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">This concise news content is displayed directly on the card on the website.</small>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category" class="form-label fw-bold">Food Category</label>
                        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror">
                            @foreach ($categories as $slug => $label)
                                <option value="{{ $slug }}" {{ old('category', $item->category ?? 'spices') === $slug ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="media_type" class="form-label fw-bold">Media Type</label>
                        <select name="media_type" id="media_type" class="form-select @error('media_type') is-invalid @enderror">
                            <option value="image" {{ old('media_type', $item->media_type ?? 'image') === 'image' ? 'selected' : '' }}>📷 Photo / Banner Image</option>
                            <option value="video" {{ old('media_type', $item->media_type) === 'video' ? 'selected' : '' }}>🎥 Video News Clip</option>
                        </select>
                        @error('media_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Collapsible Optional Details (Optional) -->
                <details class="border rounded p-3 bg-light mt-4">
                    <summary class="fw-semibold text-secondary cursor-pointer" style="cursor: pointer;">
                        <i class="fas fa-sliders-h me-1"></i> Optional Details (Tag, Source, Date)
                    </summary>
                    <div class="mt-3">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="tag" class="form-label fw-semibold small">Badge / Tag (Optional)</label>
                                <input type="text" name="tag" id="tag" class="form-control form-control-sm @error('tag') is-invalid @enderror" value="{{ old('tag', $item->tag) }}" placeholder="e.g. 🚨 Fake Spices Alert, ⚠️ Market Raid">
                            </div>
                            <div class="col-md-6">
                                <label for="source" class="form-label fw-semibold small">Report Source (Optional)</label>
                                <input type="text" name="source" id="source" class="form-control form-control-sm @error('source') is-invalid @enderror" value="{{ old('source', $item->source) }}" placeholder="e.g. National Media / Police Report">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="incident_date" class="form-label fw-semibold small">Incident Date (Optional)</label>
                                <input type="text" name="incident_date" id="incident_date" class="form-control form-control-sm @error('incident_date') is-invalid @enderror" value="{{ old('incident_date', $item->incident_date) }}" placeholder="e.g. Yesterday, October 2026">
                            </div>
                            <div class="col-md-6">
                                <label for="severity" class="form-label fw-semibold small">Alert Severity</label>
                                <select name="severity" id="severity" class="form-select form-select-sm">
                                    <option value="critical" {{ old('severity', $item->severity ?? 'critical') === 'critical' ? 'selected' : '' }}>🚨 Critical Alert</option>
                                    <option value="warning" {{ old('severity', $item->severity) === 'warning' ? 'selected' : '' }}>⚠️ Warning</option>
                                    <option value="info" {{ old('severity', $item->severity) === 'info' ? 'selected' : '' }}>ℹ️ Consumer Awareness</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-dark fw-bold">🎬 Photo &amp; Video Media</h5>
            </div>
            <div class="card-body">
                <!-- Cover Photo / Image -->
                <div class="mb-4">
                    <label for="image" class="form-label fw-bold">
                        <i class="fas fa-image text-primary me-1"></i> Cover Photo
                    </label>
                    @if ($item->image)
                        <div class="mb-2">
                            <img src="{{ $item->image_url }}" alt="Preview" class="img-thumbnail rounded" style="max-height: 140px; width: 100%; object-fit: cover;" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=900&q=80';">
                            <div class="text-muted small mt-1">Current: <code class="text-break">{{ $item->image }}</code></div>
                        </div>
                    @endif
                    <div class="mb-2">
                        <input type="file" name="image" id="image" class="form-control form-control-sm @error('image') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">Upload JPG, PNG, WEBP</small>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <input type="text" name="image_url" id="image_url" class="form-control form-control-sm @error('image_url') is-invalid @enderror" value="{{ old('image_url', str_starts_with($item->image ?? '', 'http') ? $item->image : '') }}" placeholder="Or paste image URL">
                        @error('image_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Video File or Link -->
                <div class="mb-4">
                    <label for="video" class="form-label fw-bold">
                        <i class="fas fa-video text-danger me-1"></i> Video Clip
                    </label>
                    @if ($item->video)
                        <div class="mb-2">
                            <video src="{{ $item->video_url }}" controls class="w-100 rounded border bg-dark" style="max-height: 140px;"></video>
                            <div class="text-muted small mt-1">Current: <code class="text-break">{{ $item->video }}</code></div>
                        </div>
                    @endif
                    <div class="mb-2">
                        <input type="file" name="video" id="video" class="form-control form-control-sm @error('video') is-invalid @enderror" accept="video/mp4,video/quicktime,video/webm">
                        <small class="text-muted">Upload MP4, WebM (Max: 50MB)</small>
                        @error('video')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <input type="text" name="video_url" id="video_url" class="form-control form-control-sm @error('video_url') is-invalid @enderror" value="{{ old('video_url', str_starts_with($item->video ?? '', 'http') ? $item->video : '') }}" placeholder="Or paste video URL">
                        @error('video_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', $item->exists ? $item->is_active : true) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label fw-bold">Publish on Website</label>
                </div>

                <div class="d-grid gap-2 pt-2">
                    <button type="submit" class="btn btn-primary py-2 fw-bold">
                        <i class="fas fa-save me-1"></i> {{ $item->exists ? 'Update Alert' : 'Save & Publish Alert' }}
                    </button>
                    <a href="{{ route('admin.food-awareness.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</div>
