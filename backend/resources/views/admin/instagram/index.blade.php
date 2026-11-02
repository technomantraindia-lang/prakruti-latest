@extends('admin.layouts.app')
@section('title', 'Instagram Feed - 6 Display Boxes')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1"><i class="fab fa-instagram text-danger me-2"></i>Instagram Feed Management</h2>
            <p class="text-muted mb-0">Configure the 6 Instagram display boxes shown on the homepage footer section.</p>
        </div>
        <a href="https://prakrutiheal.com" target="_blank" class="btn btn-outline-success btn-sm">
            <i class="fas fa-external-link-alt me-1"></i> View Live Storefront
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4" style="border-radius: 12px; background: #eef6ff;">
        <i class="fas fa-info-circle fa-2x text-primary me-3"></i>
        <div>
            <div class="fw-bold text-dark">Simple &amp; Direct Instagram Integration</div>
            <div class="text-muted small">Only paste the Instagram Post or Reel link. The preview displays directly from Instagram without uploading files from your computer. Boxes left empty automatically show the Prakruti logo.</div>
        </div>
    </div>

    <form action="{{ route('admin.instagram.update') }}" method="POST">
        @csrf

        {{-- Account Configuration Card --}}
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-success"><i class="fas fa-user-circle me-2"></i>Official Instagram Account Details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Official Instagram Account Link</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fab fa-instagram text-danger"></i></span>
                            <input type="url" name="instagram_profile_url" class="form-control" value="{{ $profileUrl }}" placeholder="https://www.instagram.com/prakrutiorganics_/" required>
                        </div>
                        <small class="text-muted">Users are redirected to this account when clicking empty boxes or the "Follow Us" button.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Instagram Handle</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-at text-muted"></i></span>
                            <input type="text" name="instagram_handle" class="form-control" value="{{ $handle }}" placeholder="@prakrutiorganics_" required>
                        </div>
                        <small class="text-muted">Displayed on the "Follow Us" card next to the 6 boxes.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- 6 Boxes Grid --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-th-large me-2"></i>Homepage 6 Display Boxes</h5>
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fas fa-leaf text-success me-1"></i> Empty slots display the Prakruti logo
            </span>
        </div>

        <div class="row g-4 mb-4">
            @foreach($boxes as $idx => $box)
                @php
                    $slot = $idx + 1;
                    $hasUrl = !empty($box['url']);
                    $embedUrl = $box['embed_url'] ?? '';
                    $isVideo = ($box['type'] ?? '') === 'video';
                @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm border-0 insta-box-card" id="box-card-{{ $idx }}" style="border-radius: 14px; overflow: hidden; background: #ffffff;">
                        {{-- Card Header --}}
                        <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 px-3 border-bottom">
                            <span class="fw-bold text-dark d-flex align-items-center gap-2">
                                <span class="badge bg-success">Box #{{ $slot }}</span>
                                <span class="slot-status-badge text-muted small" id="slot-status-{{ $idx }}">
                                    @if(!$hasUrl || empty($embedUrl))
                                        <span class="badge bg-light text-secondary border"><i class="fas fa-leaf text-success me-1"></i> Empty (Logo)</span>
                                    @elseif($isVideo)
                                        <span class="badge bg-danger text-white"><i class="fas fa-play-circle me-1"></i> Instagram Reel</span>
                                    @else
                                        <span class="badge bg-primary text-white"><i class="fas fa-image me-1"></i> Instagram Post</span>
                                    @endif
                                </span>
                            </span>
                            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 clear-box-btn" data-index="{{ $idx }}" title="Clear URL and reset to Prakruti logo">
                                <i class="fas fa-trash-alt me-1"></i> Clear
                            </button>
                        </div>

                        {{-- Card Body --}}
                        <div class="card-body p-3 d-flex flex-column">
                            {{-- Visual 1:1 Live Instagram Preview --}}
                            <div class="insta-preview-container mb-3 position-relative" id="preview-wrap-{{ $idx }}" style="aspect-ratio: 1; border-radius: 10px; overflow: hidden; background: #faf7ee; border: 1.5px dashed #dcd3c1; display: flex; align-items: center; justify-content: center;">
                                
                                {{-- Empty State (Prakruti Logo) --}}
                                <div class="empty-state-preview text-center p-3 {{ ($hasUrl && !empty($embedUrl)) ? 'd-none' : '' }}" id="empty-preview-{{ $idx }}">
                                    <img src="{{ asset('assets/prakruti-logo.png') }}" alt="Prakruti" style="max-height: 80px; max-width: 80px; object-fit: contain; margin-bottom: 8px;" onerror="this.src='/favicon.ico'">
                                    <div class="fw-bold text-success" style="font-size: 0.85rem;">Prakruti Logo</div>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">No link added — Shows logo on storefront</small>
                                </div>

                                {{-- Live Direct Instagram Embed Iframe --}}
                                <iframe 
                                    id="iframe-preview-{{ $idx }}" 
                                    src="{{ ($hasUrl && !empty($embedUrl)) ? $embedUrl : 'about:blank' }}" 
                                    class="w-100 h-100 {{ (!$hasUrl || empty($embedUrl)) ? 'd-none' : '' }}" 
                                    frameborder="0" 
                                    scrolling="no" 
                                    allowtransparency="true" 
                                    style="border: 0; width: 100%; height: 100%;"
                                ></iframe>
                            </div>

                            {{-- Only One Input: Instagram Post or Reel URL --}}
                            <div class="mt-auto">
                                <label class="form-label small fw-bold mb-1">
                                    <i class="fab fa-instagram text-danger me-1"></i> Instagram Post or Reel URL
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-link text-muted"></i></span>
                                    <input 
                                        type="url" 
                                        name="url[{{ $idx }}]" 
                                        id="url-input-{{ $idx }}" 
                                        class="form-control insta-url-input" 
                                        data-index="{{ $idx }}" 
                                        value="{{ $box['url'] }}" 
                                        placeholder="https://www.instagram.com/reel/... or /p/..."
                                        autocomplete="off"
                                    >
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                    Paste the link — preview updates live directly from Instagram.
                                </small>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Save Action Bar --}}
        <div class="card shadow-sm border-0 p-3 mb-5 text-end bg-white" style="border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="text-muted small">
                    <i class="fas fa-check-circle text-success me-1"></i> Changes apply immediately to the live homepage once saved.
                </span>
                <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm">
                    <i class="fas fa-save me-2"></i> Save Instagram Feed
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Helper to extract Instagram shortcode from URL
    function getInstagramCode(url) {
        if (!url) return null;
        const trimmed = url.trim();
        const match = trimmed.match(/(?:instagram\.com\/(?:p|reel|reels|tv)\/)([A-Za-z0-9_-]+)/i);
        return match ? match[1] : null;
    }

    function isReelUrl(url) {
        if (!url) return false;
        return /(?:reel|reels|tv)/i.test(url);
    }

    function updateBoxPreview(idx) {
        const input = document.getElementById('url-input-' + idx);
        const urlVal = (input.value || '').trim();
        const code = getInstagramCode(urlVal);
        const emptyDiv = document.getElementById('empty-preview-' + idx);
        const iframe = document.getElementById('iframe-preview-' + idx);
        const statusBadge = document.getElementById('slot-status-' + idx);

        if (code) {
            const embedSrc = 'https://www.instagram.com/p/' + code + '/embed/';
            if (iframe.src !== embedSrc) {
                iframe.src = embedSrc;
            }
            iframe.classList.remove('d-none');
            emptyDiv.classList.add('d-none');

            const isReel = isReelUrl(urlVal);
            if (statusBadge) {
                statusBadge.innerHTML = isReel 
                    ? '<span class="badge bg-danger text-white"><i class="fas fa-play-circle me-1"></i> Instagram Reel</span>'
                    : '<span class="badge bg-primary text-white"><i class="fas fa-image me-1"></i> Instagram Post</span>';
            }
        } else {
            iframe.src = 'about:blank';
            iframe.classList.add('d-none');
            emptyDiv.classList.remove('d-none');
            if (statusBadge) {
                statusBadge.innerHTML = '<span class="badge bg-light text-secondary border"><i class="fas fa-leaf text-success me-1"></i> Empty (Logo)</span>';
            }
        }
    }

    // Bind URL input changes for real-time live preview
    document.querySelectorAll('.insta-url-input').forEach(function (input) {
        const idx = input.getAttribute('data-index');
        input.addEventListener('input', function () {
            updateBoxPreview(idx);
        });
        input.addEventListener('change', function () {
            updateBoxPreview(idx);
        });
        input.addEventListener('paste', function () {
            setTimeout(function () {
                updateBoxPreview(idx);
            }, 50);
        });
    });

    // Clear box button
    document.querySelectorAll('.clear-box-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const idx = btn.getAttribute('data-index');
            const input = document.getElementById('url-input-' + idx);
            input.value = '';
            updateBoxPreview(idx);
        });
    });
});
</script>
@endpush
@endsection
