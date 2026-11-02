@csrf

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0 text-success fw-bold">
                    <i class="fas fa-shield-alt me-2"></i>Food Safety Awareness Alert
                </h5>
                <span class="badge bg-light text-secondary border">Minimal &amp; Simple</span>
            </div>
            <div class="card-body p-4">

                {{-- 1. Headline / Alert Title --}}
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">
                        News Headline / Alert Title <span class="text-danger">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="title" 
                        id="title" 
                        class="form-control form-control-lg @error('title') is-invalid @enderror" 
                        value="{{ old('title', $item->title) }}" 
                        placeholder="e.g. Police Arrest Merchants Selling Expired Spices in Market Raid" 
                        required
                    >
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- 2. Short News Content --}}
                <div class="mb-4">
                    <label for="summary" class="form-label fw-bold">
                        Short News Content (Shown on Card) <span class="text-danger">*</span>
                    </label>
                    <textarea 
                        name="summary" 
                        id="summary" 
                        rows="3" 
                        class="form-control @error('summary') is-invalid @enderror" 
                        placeholder="Write a short concise summary explaining the investigation or reality..." 
                        required
                    >{{ old('summary', $item->summary) }}</textarea>
                    @error('summary')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">This concise paragraph appears directly on the card on the website.</small>
                </div>

                {{-- 3. Instagram Reel or Post Link --}}
                <div class="mb-4">
                    <label for="video_url" class="form-label fw-bold">
                        <i class="fab fa-instagram text-danger me-1"></i> Instagram Reel or Post Link <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-link text-muted"></i></span>
                        <input 
                            type="url" 
                            name="video_url" 
                            id="video_url" 
                            class="form-control @error('video_url') is-invalid @enderror" 
                            value="{{ old('video_url', $item->video_url ?? $item->video) }}" 
                            placeholder="https://www.instagram.com/reel/... or https://www.instagram.com/p/..."
                            autocomplete="off"
                        >
                    </div>
                    @error('video_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted d-block mt-1">
                        Paste the Instagram link. Visitors will see the live reel preview on the Education page, and clicking takes them directly to Instagram.
                    </small>

                    {{-- Live Instagram Preview in Admin --}}
                    <div class="mt-3 p-3 bg-light rounded border text-center" id="insta-preview-box" style="max-width: 340px; margin: 12px auto 0;">
                        <div class="fw-semibold small text-muted mb-2">Live Instagram Preview</div>
                        <div class="position-relative overflow-hidden rounded bg-dark mx-auto" style="width: 220px; height: 220px;" id="insta-preview-container">
                            <iframe 
                                id="insta-iframe" 
                                src="about:blank" 
                                class="d-none w-100 h-100 border-0" 
                                scrolling="no" 
                                allowtransparency="true"
                            ></iframe>
                            <div id="insta-placeholder" class="d-flex flex-column align-items-center justify-content-center h-100 text-white-50 p-2">
                                <i class="fab fa-instagram fa-2x mb-2 text-danger"></i>
                                <span class="small text-white">Paste Instagram Reel link to preview</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Publish Status Toggle --}}
                <div class="form-check form-switch mb-4">
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        id="is_active" 
                        class="form-check-input" 
                        value="1" 
                        {{ old('is_active', $item->exists ? $item->is_active : true) ? 'checked' : '' }}
                    >
                    <label for="is_active" class="form-check-label fw-bold">Publish Alert on Website</label>
                </div>

                {{-- Hidden defaults to preserve database compatibility --}}
                <input type="hidden" name="category" value="{{ $item->category ?? 'spices' }}">
                <input type="hidden" name="severity" value="{{ $item->severity ?? 'warning' }}">
                <input type="hidden" name="media_type" value="video">

                {{-- Action Buttons --}}
                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.food-awareness.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> {{ $item->exists ? 'Update Alert' : 'Save & Publish Alert' }}
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('video_url');
    const iframe = document.getElementById('insta-iframe');
    const placeholder = document.getElementById('insta-placeholder');

    function extractCode(url) {
        if (!url) return null;
        const match = url.trim().match(/(?:instagram\.com\/(?:p|reel|reels|tv)\/)([A-Za-z0-9_-]+)/i);
        return match ? match[1] : null;
    }

    function updatePreview() {
        const val = (input.value || '').trim();
        const code = extractCode(val);
        if (code) {
            const embedSrc = 'https://www.instagram.com/p/' + code + '/embed/';
            if (iframe.src !== embedSrc) {
                iframe.src = embedSrc;
            }
            iframe.classList.remove('d-none');
            placeholder.classList.add('d-none');
        } else {
            iframe.src = 'about:blank';
            iframe.classList.add('d-none');
            placeholder.classList.remove('d-none');
        }
    }

    input.addEventListener('input', updatePreview);
    input.addEventListener('change', updatePreview);
    input.addEventListener('paste', function () {
        setTimeout(updatePreview, 50);
    });

    // Initial load preview if editing existing item
    updatePreview();
});
</script>
