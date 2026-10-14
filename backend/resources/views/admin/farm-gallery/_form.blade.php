<div class="mb-4">
    <label class="form-label fw-bold d-block text-dark">Card Format / Display Option *</label>
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <label class="card h-100 p-3 border text-center cursor-pointer option-card {{ old('display_mode', $item->display_mode ?? 'image_text') === 'image_text' ? 'border-success bg-success-subtle shadow-sm' : 'bg-light' }}" style="cursor: pointer;">
                <input type="radio" name="display_mode" value="image_text" class="form-check-input d-none" @checked(old('display_mode', $item->display_mode ?? 'image_text') === 'image_text') onchange="selectDisplayMode(this)">
                <div class="fs-2 mb-1">🖼️📝</div>
                <div class="fw-bold text-dark">Image with Text</div>
                <small class="text-muted d-block">Photo + visible Title &amp; Description</small>
            </label>
        </div>
        <div class="col-md-3 col-6">
            <label class="card h-100 p-3 border text-center cursor-pointer option-card {{ old('display_mode', $item->display_mode ?? '') === 'simple_image' ? 'border-success bg-success-subtle shadow-sm' : 'bg-light' }}" style="cursor: pointer;">
                <input type="radio" name="display_mode" value="simple_image" class="form-check-input d-none" @checked(old('display_mode', $item->display_mode ?? '') === 'simple_image') onchange="selectDisplayMode(this)">
                <div class="fs-2 mb-1">🖼️</div>
                <div class="fw-bold text-dark">Simple Image Only</div>
                <small class="text-muted d-block">Clean photo card without text</small>
            </label>
        </div>
        <div class="col-md-3 col-6">
            <label class="card h-100 p-3 border text-center cursor-pointer option-card {{ old('display_mode', $item->display_mode ?? '') === 'video_text' ? 'border-success bg-success-subtle shadow-sm' : 'bg-light' }}" style="cursor: pointer;">
                <input type="radio" name="display_mode" value="video_text" class="form-check-input d-none" @checked(old('display_mode', $item->display_mode ?? '') === 'video_text') onchange="selectDisplayMode(this)">
                <div class="fs-2 mb-1">🎬📝</div>
                <div class="fw-bold text-dark">Video with Text</div>
                <small class="text-muted d-block">Video player + Title &amp; Description</small>
            </label>
        </div>
        <div class="col-md-3 col-6">
            <label class="card h-100 p-3 border text-center cursor-pointer option-card {{ old('display_mode', $item->display_mode ?? '') === 'simple_video' ? 'border-success bg-success-subtle shadow-sm' : 'bg-light' }}" style="cursor: pointer;">
                <input type="radio" name="display_mode" value="simple_video" class="form-check-input d-none" @checked(old('display_mode', $item->display_mode ?? '') === 'simple_video') onchange="selectDisplayMode(this)">
                <div class="fs-2 mb-1">🎬</div>
                <div class="fw-bold text-dark">Simple Video Only</div>
                <small class="text-muted d-block">Clean video card without text</small>
            </label>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="mb-3">
            <label class="form-label fw-bold">Title <span id="titleRequiredAsterisk">*</span></label>
            <input type="text" name="title" id="titleInput" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Traditional Bilona Ghee or Community Gathering" value="{{ old('title', $item->title ?? '') }}">
            <div class="form-text" id="titleHelpText">Displayed on the card when 'Image with Text' or 'Video with Text' is selected.</div>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-5">
        <div class="mb-3">
            <label class="form-label fw-bold">Category *</label>
            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                <option value="" disabled {{ !isset($item) ? 'selected' : '' }}>Select Category</option>
                @foreach($farmGalleryCategories as $galleryCategory)
                    <option value="{{ $galleryCategory->slug }}" @selected(old('category', $item->category ?? '') === $galleryCategory->slug)>
                        @if($galleryCategory->slug === 'farmer-community') 🧑‍🌾 @elseif($galleryCategory->slug === 'fields-cultivation') 🌾 @elseif($galleryCategory->slug === 'crops-vegetables') 🐄 @elseif($galleryCategory->slug === 'events' || $galleryCategory->slug === 'awards-events') 🎪 @endif
                        {{ $galleryCategory->name }}
                    </option>
                @endforeach
            </select>
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- Image Upload Card -->
    <div class="col-md-6">
        <div class="card h-100 border p-3 bg-light">
            <label class="form-label fw-bold mb-1">
                <i class="fas fa-image text-success me-1"></i> Photo / Cover Image
            </label>
            <div class="text-muted small mb-2">Upload JPG, PNG, or WebP photo</div>
            <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror

            @if(isset($item) && $item->image)
                <div class="mt-3 p-2 bg-white rounded border d-inline-block">
                    <div class="small text-muted mb-1">Current Image:</div>
                    <img src="{{ $item->image_url }}" class="rounded" style="max-height: 120px; max-width: 100%; object-fit: cover;">
                </div>
            @endif
        </div>
    </div>

    <!-- Video Upload Card -->
    <div class="col-md-6">
        <div class="card h-100 border p-3 bg-light">
            <label class="form-label fw-bold mb-1">
                <i class="fas fa-video text-primary me-1"></i> Video (Optional)
            </label>
            <div class="text-muted small mb-2">Upload MP4, MOV, or WebM video</div>
            <input type="file" name="video" class="form-control @error('video') is-invalid @enderror" accept="video/mp4,video/quicktime,video/webm">
            @error('video')<div class="invalid-feedback">{{ $message }}</div>@enderror

            @if(isset($item) && $item->videos->isNotEmpty())
                <div class="mt-3 p-2 bg-white rounded border">
                    <div class="small text-muted mb-1">Current Video:</div>
                    <video src="{{ $item->videos->first()->video_url }}" controls class="rounded w-100" style="max-height: 140px; background: #000;"></video>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_video" value="1" id="removeVideoCheck">
                        <label class="form-check-label text-danger small fw-semibold" for="removeVideoCheck">
                            Remove current video
                        </label>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label fw-bold">Tag / Badge <span class="text-muted fw-normal">(Optional)</span></label>
            <input type="text" name="tag" class="form-control @error('tag') is-invalid @enderror" placeholder="e.g. Field Work, Community Event" value="{{ old('tag', $item->tag ?? '') }}">
            <div class="form-text">If left empty, the Category name will automatically be used.</div>
            @error('tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label fw-bold">Status *</label>
            <select name="status" class="form-select" required>
                <option value="active" @selected(old('status', $item->status ?? 'active') === 'active')>Active (Visible on Website)</option>
                <option value="inactive" @selected(old('status', $item->status ?? '') === 'inactive')>Inactive (Hidden)</option>
            </select>
        </div>
    </div>
</div>

<div class="mb-4">
    <label class="form-label fw-bold">Description / Caption <span class="text-muted fw-normal">(Optional)</span></label>
    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Brief caption or story displayed on the card and in the modal">{{ old('description', $item->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<script>
function selectDisplayMode(radio) {
    document.querySelectorAll('.option-card').forEach(card => {
        card.classList.remove('border-success', 'bg-success-subtle', 'shadow-sm');
        card.classList.add('bg-light');
    });
    const parentCard = radio.closest('.option-card');
    if (parentCard) {
        parentCard.classList.remove('bg-light');
        parentCard.classList.add('border-success', 'bg-success-subtle', 'shadow-sm');
    }
}
</script>
