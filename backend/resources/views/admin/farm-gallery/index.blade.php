@extends('admin.layouts.app')

@section('title', 'Farm Gallery')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1 fw-bold text-dark"><i class="fas fa-seedling text-success me-2"></i>Farm Gallery Management</h2>
        <p class="text-muted small mb-0">Manage farm photos, videos, community moments & awards for the frontend gallery</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.farm-gallery.create') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus me-1"></i> Add Photo / Video
        </a>
    </div>
</div>

<!-- Quick Stats & Filter Tabs -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <!-- Filter Pills -->
            <div class="nav nav-pills gap-2">
                <a href="{{ route('admin.farm-gallery.index') }}" 
                   class="btn btn-sm {{ !request('media') && !request('category') ? 'btn-dark' : 'btn-outline-secondary' }}">
                    <i class="fas fa-th-large me-1"></i> All Items 
                    <span class="badge bg-secondary ms-1">{{ $totalCount ?? $items->total() }}</span>
                </a>
                <a href="{{ route('admin.farm-gallery.index', ['media' => 'video']) }}" 
                   class="btn btn-sm {{ request('media') === 'video' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="fas fa-video me-1"></i> With Videos 
                    <span class="badge {{ request('media') === 'video' ? 'bg-light text-primary' : 'bg-primary text-white' }} ms-1">
                        {{ $videoCount ?? 0 }}
                    </span>
                </a>
                <a href="{{ route('admin.farm-gallery.index', ['media' => 'photo']) }}" 
                   class="btn btn-sm {{ request('media') === 'photo' ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="fas fa-image me-1"></i> Photos Only 
                    <span class="badge {{ request('media') === 'photo' ? 'bg-light text-success' : 'bg-success text-white' }} ms-1">
                        {{ $photoOnlyCount ?? 0 }}
                    </span>
                </a>
            </div>

            <!-- Category & Search Filter Form -->
            <form action="{{ route('admin.farm-gallery.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                @if(request('media'))
                    <input type="hidden" name="media" value="{{ request('media') }}">
                @endif
                <select name="category" class="form-select form-select-sm" style="min-width: 180px;" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" @selected(request('category') === $cat->slug)>
                            @if($cat->slug === 'farmer-community') 🧑‍🌾 @elseif($cat->slug === 'fields-cultivation') 🌾 @elseif($cat->slug === 'crops-vegetables') 🐄 @elseif($cat->slug === 'events' || $cat->slug === 'awards-events') 🎪 @endif
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <div class="input-group input-group-sm" style="max-width: 240px;">
                    <input type="text" name="search" class="form-control" placeholder="Search gallery..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                </div>

                @if(request()->hasAny(['media', 'category', 'search']))
                    <a href="{{ route('admin.farm-gallery.index') }}" class="btn btn-sm btn-outline-danger" title="Clear filters">
                        <i class="fas fa-times me-1"></i> Reset
                    </a>
                @endif
            </form>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 110px;">Cover Photo</th>
                        <th style="width: 170px;">Attached Video(s)</th>
                        <th>Title &amp; Story</th>
                        <th>Category</th>
                        <th>Format</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                    <tr>
                        <!-- Cover Photo Column -->
                        <td>
                            @if($item->image)
                                <div class="position-relative d-inline-block">
                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="rounded border shadow-xs" style="width: 90px; height: 60px; object-fit: cover;">
                                </div>
                            @else
                                <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 90px; height: 60px;">
                                    <i class="fas fa-image fa-lg"></i>
                                </div>
                            @endif
                        </td>

                        <!-- Attached Video(s) Column -->
                        <td>
                            @if($item->videos->isNotEmpty())
                                <div class="d-flex flex-column gap-1">
                                    <div class="position-relative rounded overflow-hidden border bg-dark" style="width: 140px; height: 75px;">
                                        <video src="{{ $item->videos->first()->video_url }}" 
                                               preload="metadata" 
                                               muted 
                                               class="w-100 h-100" 
                                               style="object-fit: cover; cursor: pointer;"
                                               onclick="previewVideo('{{ $item->videos->first()->video_url }}', '{{ addslashes($item->title) }}', '{{ addslashes($item->videos->first()->title) }}')">
                                        </video>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger position-absolute top-50 start-50 translate-middle rounded-circle p-0 d-flex align-items-center justify-content-center shadow"
                                                style="width: 32px; height: 32px;"
                                                onclick="previewVideo('{{ $item->videos->first()->video_url }}', '{{ addslashes($item->title) }}', '{{ addslashes($item->videos->first()->title) }}')"
                                                title="Play Video">
                                            <i class="fas fa-play fa-xs" style="margin-left: 2px;"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mt-1">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold" style="font-size: 0.72rem;">
                                            <i class="fas fa-video me-1"></i> {{ $item->videos->count() }} Video{{ $item->videos->count() > 1 ? 's' : '' }}
                                        </span>
                                        <button type="button" 
                                                class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold"
                                                style="font-size: 0.75rem;"
                                                onclick="previewVideo('{{ $item->videos->first()->video_url }}', '{{ addslashes($item->title) }}', '{{ addslashes($item->videos->first()->title) }}')">
                                            Watch ▶
                                        </button>
                                    </div>
                                    @if($item->videos->count() > 1)
                                        <div class="dropdown">
                                            <button class="btn btn-xs btn-outline-secondary dropdown-toggle w-100 py-0" style="font-size: 0.7rem;" type="button" data-bs-toggle="dropdown">
                                                All {{ $item->videos->count() }} Clips
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 0.8rem;">
                                                @foreach($item->videos as $idx => $vid)
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)" onclick="previewVideo('{{ $vid->video_url }}', '{{ addslashes($item->title) }}', '{{ addslashes($vid->title) }}')">
                                                            <i class="fas fa-play text-primary me-2"></i> {{ $vid->title ?: 'Video ' . ($idx + 1) }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="badge bg-light text-muted border">
                                    <i class="fas fa-video-slash me-1 opacity-50"></i> No video
                                </span>
                            @endif
                        </td>

                        <!-- Title & Story Column -->
                        <td>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <strong class="text-dark">{{ $item->title }}</strong>
                                @if($item->tag)
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;">{{ $item->tag }}</span>
                                @endif
                            </div>
                            @if($item->description)
                                <p class="text-muted small mb-1" style="max-width: 380px; line-height: 1.35;">
                                    {{ Str::limit($item->description, 85) }}
                                </p>
                            @endif
                            @if($item->location)
                                <div class="text-secondary" style="font-size: 0.75rem;">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>{{ $item->location }}
                                </div>
                            @endif
                        </td>

                        <!-- Category Column -->
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                @if($item->category === 'farmer-community') 🧑‍🌾 @elseif($item->category === 'fields-cultivation') 🌾 @elseif($item->category === 'crops-vegetables') 🐄 @elseif($item->category === 'events' || $item->category === 'awards-events') 🎪 @endif
                                {{ $item->category_name }}
                            </span>
                        </td>

                        <!-- Card Format Column -->
                        <td>
                            @if(($item->display_mode ?? 'image_text') === 'simple_image')
                                <span class="badge bg-light text-dark border"><i class="fas fa-image me-1"></i> Simple Photo</span>
                            @elseif(($item->display_mode ?? 'image_text') === 'simple_video')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fas fa-video me-1"></i> Simple Video</span>
                            @elseif(($item->display_mode ?? 'image_text') === 'video_text')
                                <span class="badge bg-info-subtle text-dark border"><i class="fas fa-film me-1"></i> Video + Text</span>
                            @else
                                <span class="badge bg-light text-secondary border"><i class="fas fa-file-image me-1"></i> Image + Text</span>
                            @endif
                        </td>

                        <!-- Status Column -->
                        <td>
                            <span class="badge bg-{{ $item->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>

                        <!-- Actions Column -->
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('admin.farm-gallery.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit Item">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.farm-gallery.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this gallery item?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete Item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-photo-video fa-3x mb-3 d-block text-secondary opacity-50"></i>
                            <h6 class="fw-bold">No farm gallery items found</h6>
                            <p class="small text-muted mb-3">Try adjusting your filters or click below to add new media.</p>
                            <a href="{{ route('admin.farm-gallery.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Add Photo / Video
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} gallery items
                </div>
                <div>
                    {{ $items->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal: Interactive Video Player Lightbox -->
<div class="modal fade" id="videoPreviewModal" tabindex="-1" aria-labelledby="videoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-play-circle text-danger fs-5"></i>
                    <div>
                        <h6 class="modal-title mb-0 fw-bold" id="videoModalTitle">Video Preview</h6>
                        <small class="text-white-50" id="modalVideoSubtitle"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopModalVideo()"></button>
            </div>
            <div class="modal-body p-0 bg-black text-center position-relative">
                <video id="modalVideoPlayer" 
                       src="" 
                       controls 
                       playsinline 
                       style="max-height: 72vh; width: 100%; background: #000; outline: none;">
                </video>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                <a id="videoDownloadLink" href="" target="_blank" download class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-external-link-alt me-1"></i> Open Video Directly
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="stopModalVideo()">
                    Close Player
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function previewVideo(videoUrl, itemTitle, videoTitle) {
    if (!videoUrl) return;
    const player = document.getElementById('modalVideoPlayer');
    document.getElementById('videoModalTitle').innerText = itemTitle || 'Video Preview';
    document.getElementById('modalVideoSubtitle').innerText = videoTitle ? ('Clip: ' + videoTitle) : '';
    document.getElementById('videoDownloadLink').href = videoUrl;

    player.src = videoUrl;
    player.load();
    player.play().catch(e => console.log('Autoplay deferred:', e));

    const modalEl = document.getElementById('videoPreviewModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function stopModalVideo() {
    const player = document.getElementById('modalVideoPlayer');
    if (player) {
        player.pause();
        player.src = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('videoPreviewModal');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', stopModalVideo);
    }
});
</script>
@endsection
