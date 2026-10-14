@php
    $oldPackages = old('packages');
    if ($oldPackages) {
        $packageRows = collect($oldPackages)->values();
    } elseif (isset($product) && $product->relationLoaded('variations') && $product->variations->count()) {
        $packageRows = $product->variations->map(fn ($variation) => [
            'id' => $variation->id,
            'label' => $variation->attr_val,
            'price' => $variation->price,
            'sale_price' => $variation->sale_price,
            'stock_qty' => $variation->stock_qty,
            'status' => $variation->status,
        ])->values();
    } else {
        $packageRows = collect([[
            'label' => '',
            'price' => $product->price ?? '',
            'sale_price' => $product->sale_price ?? '',
            'stock_qty' => $product->stock_qty ?? 0,
            'status' => 'active',
        ]]);
    }

    $productInfo = old('product_information', $product->product_information ?? []);
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="alert alert-light border mb-0">
            <strong>Product detail essentials only.</strong>
            Fill product content, package sizes, pricing, stock, and images needed for the frontend product card and product detail page.
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <h5 class="mb-0">Basic Product Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Product Name *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name ?? '') }}" placeholder="e.g. A2 Gir Cow Ghee Bilona" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Category *</label>
                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="card border-success-subtle bg-light mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Package Size & Price</h5>
                            <small class="text-muted">Add options like 500g pack, 1kg pack, 5kg family pack.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" id="addPackageRow">+ Add Package</button>
                    </div>
                    <div class="card-body">
                        @error('packages')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        <div id="packageRows" class="vstack gap-3">
                            @foreach($packageRows as $index => $package)
                                <div class="package-row border rounded-3 bg-white p-3">
                                    <input type="hidden" name="packages[{{ $index }}][id]" value="{{ $package['id'] ?? '' }}">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label">Package Size *</label>
                                            <input type="text" name="packages[{{ $index }}][label]" class="form-control @error("packages.$index.label") is-invalid @enderror" value="{{ $package['label'] ?? '' }}" placeholder="500g / 1kg" required>
                                            @error("packages.$index.label")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Regular Price *</label>
                                            <input type="number" step="0.01" name="packages[{{ $index }}][price]" class="form-control @error("packages.$index.price") is-invalid @enderror" value="{{ $package['price'] ?? '' }}" placeholder="750" required>
                                            @error("packages.$index.price")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Sale Price</label>
                                            <input type="number" step="0.01" name="packages[{{ $index }}][sale_price]" class="form-control @error("packages.$index.sale_price") is-invalid @enderror" value="{{ $package['sale_price'] ?? '' }}" placeholder="675">
                                            @error("packages.$index.sale_price")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Stock *</label>
                                            <input type="number" name="packages[{{ $index }}][stock_qty]" class="form-control @error("packages.$index.stock_qty") is-invalid @enderror" value="{{ $package['stock_qty'] ?? 0 }}" min="0" required>
                                            @error("packages.$index.stock_qty")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Status</label>
                                            <select name="packages[{{ $index }}][status]" class="form-select">
                                                <option value="active" @selected(($package['status'] ?? 'active') === 'active')>Active</option>
                                                <option value="inactive" @selected(($package['status'] ?? '') === 'inactive')>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger w-100 remove-package-row" title="Remove package">×</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted d-block mt-3">The first active package becomes the product card price. Total package stock is synced as product stock.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Short Description</label>
                    <textarea name="short_desc" class="form-control @error('short_desc') is-invalid @enderror" rows="3" placeholder="Short text shown on product cards and the product detail top section.">{{ old('short_desc', $product->short_desc ?? '') }}</textarea>
                    @error('short_desc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Full Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="7" placeholder="Detailed product overview shown on the product detail page.">{{ old('description', $product->description ?? '') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-0">
                    <label class="form-label">Nutritional Info</label>
                    <textarea name="nutritional_info" class="form-control @error('nutritional_info') is-invalid @enderror" rows="6" placeholder="Example: Energy: 343 kcal&#10;Protein: 22g&#10;Carbohydrates: 60g">{{ old('nutritional_info', $product->nutritional_info ?? '') }}</textarea>
                    <small class="text-muted">This content appears in the Nutritional Info tab on the product detail page.</small>
                    @error('nutritional_info')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="card border-0 bg-light mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Product Information</h5>
                        <small class="text-muted">Optional. Leave blank to use the default details on the frontend.</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Type</label>
                                <input type="text" name="product_information[product_type]" class="form-control" value="{{ $productInfo['product_type'] ?? '' }}" placeholder="Default: selected category">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Shelf Life</label>
                                <input type="text" name="product_information[shelf_life]" class="form-control" value="{{ $productInfo['shelf_life'] ?? '' }}" placeholder="Default: 12 Months">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ingredient</label>
                                <input type="text" name="product_information[ingredient]" class="form-control" value="{{ $productInfo['ingredient'] ?? '' }}" placeholder="Default: Pure product name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Packaging Type</label>
                                <input type="text" name="product_information[packaging_type]" class="form-control" value="{{ $productInfo['packaging_type'] ?? '' }}" placeholder="Default: Food Grade Standing Pouch">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Storage</label>
                                <input type="text" name="product_information[storage]" class="form-control" value="{{ $productInfo['storage'] ?? '' }}" placeholder="Default: Store in dry, airtight container">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Visibility</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $product->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $product->status ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="featured" value="1" class="form-check-input" id="featured" @checked(old('featured', $product->featured ?? false))>
                    <label class="form-check-label" for="featured">Show in Best Sellers</label>
                    <div class="form-text">Enable this for any product you want to show in the homepage Best Sellers section.</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-camera text-success me-2"></i>Product Images</h5>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Card & Detail Gallery</span>
            </div>
            <div class="card-body">
                <!-- 1. MAIN CARD PHOTO -->
                <div class="p-3 mb-4 rounded-3 border border-success-subtle bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold mb-0 text-success d-flex align-items-center gap-2">
                            <i class="fas fa-star text-warning"></i> Main Product Photo (Card Display)
                        </label>
                        <span class="badge bg-success text-white">Shown on Product Cards</span>
                    </div>
                    <p class="text-muted small mb-3">
                        This is the photo shown in the catalog, search, cart, and product cards. Choose a photo below or click <strong>"Set as Main"</strong> on any gallery photo.
                    </p>

                    <div class="d-flex flex-column flex-sm-row align-items-start gap-3">
                        <div class="main-photo-preview-wrapper border rounded-3 p-2 bg-white text-center shadow-sm" style="width: 130px; min-height: 130px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            @php
                                $currentMainUrl = isset($product) && $product->image ? $product->image_url : null;
                            @endphp
                            <img id="mainProductPhotoPreview"
                                 src="{{ $currentMainUrl ?: 'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23a0aec0\' stroke-width=\'1.5\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><path d=\'M21 15l-5-5L5 21\'/></svg>' }}"
                                 alt="Main product photo"
                                 class="rounded img-fluid"
                                 style="max-height: 100px; object-fit: contain; {{ $currentMainUrl ? '' : 'opacity: 0.5;' }}"
                                 data-original-src="{{ $currentMainUrl ?: '' }}">
                            <span id="mainPhotoTag" class="badge bg-success-subtle text-success border border-success-subtle mt-2 small" style="{{ $currentMainUrl ? '' : 'display:none;' }}">
                                Current Main
                            </span>
                        </div>

                        <div class="flex-grow-1">
                            <div class="mb-2 d-flex flex-wrap gap-2 align-items-center">
                                <label class="btn btn-sm btn-outline-success mb-0">
                                    <i class="fas fa-upload me-1"></i> Upload Main Photo
                                    <input type="file" name="image" id="mainImageInput" class="d-none" accept="image/*">
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="resetMainPhotoBtn" title="Reset main photo">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </button>
                            </div>
                            <small class="text-muted d-block">Recommended 1:1 square ratio (e.g. 800x800 px).</small>
                            <input type="hidden" name="set_main_gallery_id" id="setMainGalleryId" value="">
                            <input type="hidden" name="remove_main_image" id="removeMainImage" value="0">
                            <div id="mainPhotoStatusNotice" class="small text-success mt-2 fw-semibold" style="display:none;"></div>
                            @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <!-- 2. PRODUCT DETAIL GALLERY & DRAG-AND-DROP REORDER -->
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold mb-0">
                            <i class="fas fa-images me-1 text-primary"></i> Product Detail Photos <span class="text-danger">*</span>
                        </label>
                        <span class="badge bg-secondary" id="galleryCountBadge">1 - 7 photos</span>
                    </div>
                    <small class="text-muted d-block mb-3">
                        Thumbnails shown on the product detail page. <strong>Drag and drop</strong> to rearrange the display sequence!
                    </small>

                    <!-- Reorder helper bar -->
                    <div class="alert alert-primary-subtle border border-primary-subtle py-2 px-3 small rounded-3 mb-3 d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-arrows-alt text-primary fs-6"></i>
                            <span><strong>Drag & Drop to Rearrange:</strong> Drag any photo card to reposition. The order here directly determines thumbnail order on the store product page.</span>
                        </div>
                        <div id="reorderStatusToast" class="badge bg-success d-none py-1 px-2"><i class="fas fa-check me-1"></i> Saved!</div>
                    </div>

                    @if(isset($product) && $product->images->count())
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold small text-dark"><i class="fas fa-th me-1"></i> Current Photos ({{ $product->images->count() }})</span>
                                <small class="text-muted">Drag handle or cards to reorder</small>
                            </div>

                            <div id="existingGalleryGrid" class="d-flex flex-wrap gap-2 p-2 bg-light rounded-3 border min-gallery-zone"
                                 data-product-id="{{ $product->id }}"
                                 data-reorder-url="{{ route('admin.products.gallery.reorder', $product) }}"
                                 data-set-main-url="{{ route('admin.products.gallery.setMain', $product) }}"
                                 data-delete-url-base="{{ url('/admin/products/' . $product->id . '/gallery') }}">
                                @foreach($product->images as $index => $img)
                                    @php
                                        $isThisMain = ($product->image === $img->image);
                                    @endphp
                                    <div class="gallery-item-card border rounded-3 p-2 bg-white shadow-sm position-relative"
                                         draggable="true"
                                         data-image-id="{{ $img->id }}"
                                         data-image-url="{{ $img->image_url }}">
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="gallery-drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical text-muted"></i> Drag</span>
                                            <span class="badge bg-dark rounded-pill gallery-order-badge">#{{ $loop->iteration }}</span>
                                        </div>

                                        <div class="gallery-img-container rounded overflow-hidden border mb-2 text-center bg-light" style="width: 100%; height: 85px;">
                                            <img src="{{ $img->image_url }}" alt="{{ $product->name }} photo" class="w-100 h-100 rounded" style="object-fit: cover;">
                                        </div>

                                        <!-- Main Photo Action -->
                                        <div class="main-action-area mb-2 text-center">
                                            <span class="badge bg-success w-100 py-1 is-main-indicator {{ $isThisMain ? '' : 'd-none' }}">
                                                <i class="fas fa-star me-1"></i> Card Photo
                                            </span>
                                            <button type="button" class="btn btn-xs btn-outline-success w-100 py-1 btn-make-main {{ $isThisMain ? 'd-none' : '' }}" title="Set as product card image">
                                                <i class="far fa-star me-1"></i> Set Main
                                            </button>
                                        </div>

                                        <!-- Card controls -->
                                        <div class="d-flex justify-content-between align-items-center gap-1 border-top pt-1">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-xs btn-light border btn-move-left" title="Move left">‹</button>
                                                <button type="button" class="btn btn-xs btn-light border btn-move-right" title="Move right">›</button>
                                            </div>
                                            <button type="button" class="btn btn-xs btn-outline-danger btn-delete-existing" title="Remove photo">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>

                                        <input type="hidden" name="gallery_order[]" value="{{ $img->id }}" class="gallery-order-input">
                                        <input type="checkbox" name="remove_gallery[]" value="{{ $img->id }}" class="d-none remove-gallery-checkbox">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Upload new detail photos -->
                    <div class="card border border-dashed rounded-3 p-3 bg-white mt-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-label fw-semibold mb-0 small text-dark">
                                <i class="fas fa-plus-circle text-success me-1"></i> Add Detail Photos
                            </label>
                            <div id="galleryLimitMessage" class="small text-muted"></div>
                        </div>

                        <input
                            type="file"
                            name="gallery[]"
                            id="galleryImages"
                            class="form-control @error('gallery') is-invalid @enderror"
                            accept="image/*"
                            multiple
                            data-existing-gallery-count="{{ isset($product) ? $product->images->count() : 0 }}"
                            data-max-gallery="7"
                        >
                        @error('gallery')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @error('gallery.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                        <!-- Preview for newly picked photos with drag-reordering -->
                        <div id="newGalleryPreviewSection" class="mt-3 d-none">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-semibold text-secondary">New Photos (Drag to rearrange order):</span>
                            </div>
                            <div id="galleryPreview" class="d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gallery-item-card {
    width: 120px;
    cursor: grab;
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
    user-select: none;
}
.gallery-item-card:hover {
    box-shadow: 0 4px 10px rgba(0,0,0,0.12) !important;
    border-color: #2d5a27 !important;
}
.gallery-item-card.is-dragging {
    opacity: 0.45;
    transform: scale(0.95);
    background: #ecfdf5 !important;
    border-color: #059669 !important;
}
.gallery-item-card.drop-target {
    border-color: #2d5a27 !important;
    box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.3) !important;
    transform: translateY(-2px);
}
.gallery-drag-handle {
    cursor: grab;
    font-size: 0.72rem;
    font-weight: 600;
}
.new-gallery-card {
    width: 110px;
    cursor: grab;
    transition: transform 0.15s ease, opacity 0.15s ease;
}
.new-gallery-card.is-dragging {
    opacity: 0.45;
    transform: scale(0.95);
}
.new-gallery-card.drop-target {
    border-color: #2d5a27 !important;
    box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.3) !important;
}
.btn-xs {
    padding: 0.15rem 0.4rem;
    font-size: 0.72rem;
    line-height: 1.2;
    border-radius: 0.25rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const galleryInput = document.getElementById('galleryImages');
    const galleryPreview = document.getElementById('galleryPreview');
    const newGalleryPreviewSection = document.getElementById('newGalleryPreviewSection');
    const galleryLimitMessage = document.getElementById('galleryLimitMessage');
    const galleryCountBadge = document.getElementById('galleryCountBadge');
    const existingGalleryGrid = document.getElementById('existingGalleryGrid');
    const mainImageInput = document.getElementById('mainImageInput');
    const mainProductPhotoPreview = document.getElementById('mainProductPhotoPreview');
    const mainPhotoTag = document.getElementById('mainPhotoTag');
    const resetMainPhotoBtn = document.getElementById('resetMainPhotoBtn');
    const setMainGalleryId = document.getElementById('setMainGalleryId');
    const removeMainImage = document.getElementById('removeMainImage');
    const mainPhotoStatusNotice = document.getElementById('mainPhotoStatusNotice');
    const reorderStatusToast = document.getElementById('reorderStatusToast');
    const packageRows = document.getElementById('packageRows');
    const addPackageRow = document.getElementById('addPackageRow');

    const maxGalleryPhotos = Number(galleryInput?.dataset.maxGallery || 7);
    const selectedGalleryFiles = [];

    // --- CSRF helper ---
    function getCsrfToken() {
        return document.querySelector('input[name="_token"]')?.value || '';
    }

    // --- 1. Main Photo Handling ---
    const originalMainSrc = mainProductPhotoPreview?.dataset.originalSrc || '';

    mainImageInput?.addEventListener('change', function () {
        const file = this.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            if (mainProductPhotoPreview) {
                mainProductPhotoPreview.src = e.target.result;
                mainProductPhotoPreview.style.opacity = '1';
            }
            if (mainPhotoTag) {
                mainPhotoTag.style.display = 'inline-block';
                mainPhotoTag.textContent = 'New File Selected';
                mainPhotoTag.className = 'badge bg-warning text-dark border mt-2 small';
            }
            if (setMainGalleryId) setMainGalleryId.value = '';
            if (removeMainImage) removeMainImage.value = '0';
            if (mainPhotoStatusNotice) {
                mainPhotoStatusNotice.textContent = '✓ New image selected for product card';
                mainPhotoStatusNotice.style.display = 'block';
            }
            // Remove 'Card Photo' badge from existing gallery items
            document.querySelectorAll('.gallery-item-card .is-main-indicator').forEach(el => el.classList.add('d-none'));
            document.querySelectorAll('.gallery-item-card .btn-make-main').forEach(el => el.classList.remove('d-none'));
        };
        reader.readAsDataURL(file);
    });

    resetMainPhotoBtn?.addEventListener('click', function () {
        if (mainImageInput) mainImageInput.value = '';
        if (setMainGalleryId) setMainGalleryId.value = '';
        if (removeMainImage) removeMainImage.value = '0';
        if (mainProductPhotoPreview) {
            mainProductPhotoPreview.src = originalMainSrc || 'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23a0aec0\' stroke-width=\'1.5\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><path d=\'M21 15l-5-5L5 21\'/></svg>';
            mainProductPhotoPreview.style.opacity = originalMainSrc ? '1' : '0.5';
        }
        if (mainPhotoTag) {
            mainPhotoTag.style.display = originalMainSrc ? 'inline-block' : 'none';
            mainPhotoTag.textContent = 'Current Main';
            mainPhotoTag.className = 'badge bg-success-subtle text-success border border-success-subtle mt-2 small';
        }
        if (mainPhotoStatusNotice) {
            mainPhotoStatusNotice.style.display = 'none';
            mainPhotoStatusNotice.textContent = '';
        }
    });

    // --- 2. Existing Gallery Drag & Drop Reordering ---
    function updateExistingOrderBadges() {
        if (!existingGalleryGrid) return;
        const cards = existingGalleryGrid.querySelectorAll('.gallery-item-card:not(.to-remove)');
        cards.forEach((card, index) => {
            const badge = card.querySelector('.gallery-order-badge');
            if (badge) badge.textContent = `#${index + 1}`;
        });
        updateGalleryLimitMessage(selectedGalleryFiles.length);
    }

    let saveOrderTimeout = null;
    function syncExistingOrderToServer() {
        if (!existingGalleryGrid) return;
        const url = existingGalleryGrid.dataset.reorderUrl;
        if (!url) return;

        const order = Array.from(existingGalleryGrid.querySelectorAll('.gallery-item-card:not(.to-remove)'))
            .map(c => Number(c.dataset.imageId))
            .filter(Boolean);

        clearTimeout(saveOrderTimeout);
        saveOrderTimeout = setTimeout(() => {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ order })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && reorderStatusToast) {
                    reorderStatusToast.classList.remove('d-none');
                    setTimeout(() => reorderStatusToast.classList.add('d-none'), 2500);
                }
            })
            .catch(err => console.error('Order save error:', err));
        }, 300);
    }

    if (existingGalleryGrid) {
        let draggedCard = null;

        existingGalleryGrid.addEventListener('dragstart', function (e) {
            const card = e.target.closest('.gallery-item-card');
            if (!card) return;
            draggedCard = card;
            card.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.imageId);
        });

        existingGalleryGrid.addEventListener('dragend', function (e) {
            if (draggedCard) draggedCard.classList.remove('is-dragging');
            existingGalleryGrid.querySelectorAll('.drop-target').forEach(el => el.classList.remove('drop-target'));
            draggedCard = null;
            updateExistingOrderBadges();
            syncExistingOrderToServer();
        });

        existingGalleryGrid.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!draggedCard) return;

            const targetCard = e.target.closest('.gallery-item-card');
            if (!targetCard || targetCard === draggedCard) return;

            existingGalleryGrid.querySelectorAll('.drop-target').forEach(el => el.classList.remove('drop-target'));
            targetCard.classList.add('drop-target');

            const rect = targetCard.getBoundingClientRect();
            const isAfter = (e.clientX - rect.left) > (rect.width / 2);
            if (isAfter) {
                targetCard.after(draggedCard);
            } else {
                targetCard.before(draggedCard);
            }
        });

        existingGalleryGrid.addEventListener('dragleave', function (e) {
            const targetCard = e.target.closest('.gallery-item-card');
            if (targetCard) targetCard.classList.remove('drop-target');
        });

        // Click actions on existing gallery cards
        existingGalleryGrid.addEventListener('click', function (e) {
            // "Set Main"
            const makeMainBtn = e.target.closest('.btn-make-main');
            if (makeMainBtn) {
                const card = makeMainBtn.closest('.gallery-item-card');
                const imageId = card.dataset.imageId;
                const imageUrl = card.dataset.imageUrl;

                if (setMainGalleryId) setMainGalleryId.value = imageId;
                if (removeMainImage) removeMainImage.value = '0';
                if (mainImageInput) mainImageInput.value = '';

                if (mainProductPhotoPreview) {
                    mainProductPhotoPreview.src = imageUrl;
                    mainProductPhotoPreview.style.opacity = '1';
                }
                if (mainPhotoTag) {
                    mainPhotoTag.style.display = 'inline-block';
                    mainPhotoTag.textContent = 'Selected as Main';
                    mainPhotoTag.className = 'badge bg-success text-white border mt-2 small';
                }
                if (mainPhotoStatusNotice) {
                    mainPhotoStatusNotice.textContent = '✓ This gallery photo is set as the Main Card Photo!';
                    mainPhotoStatusNotice.style.display = 'block';
                }

                existingGalleryGrid.querySelectorAll('.is-main-indicator').forEach(el => el.classList.add('d-none'));
                existingGalleryGrid.querySelectorAll('.btn-make-main').forEach(el => el.classList.remove('d-none'));
                card.querySelector('.is-main-indicator')?.classList.remove('d-none');
                makeMainBtn.classList.add('d-none');

                // AJAX instant set main
                const setMainUrl = existingGalleryGrid.dataset.setMainUrl;
                if (setMainUrl) {
                    fetch(setMainUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ image_id: imageId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && reorderStatusToast) {
                            reorderStatusToast.textContent = 'Main Photo Set! ✓';
                            reorderStatusToast.classList.remove('d-none');
                            setTimeout(() => {
                                reorderStatusToast.classList.add('d-none');
                                reorderStatusToast.innerHTML = '<i class="fas fa-check me-1"></i> Saved!';
                            }, 2500);
                        }
                    })
                    .catch(err => console.error(err));
                }
                return;
            }

            // "Move Left"
            const moveLeftBtn = e.target.closest('.btn-move-left');
            if (moveLeftBtn) {
                const card = moveLeftBtn.closest('.gallery-item-card');
                const prev = card.previousElementSibling;
                if (prev && prev.classList.contains('gallery-item-card')) {
                    prev.before(card);
                    updateExistingOrderBadges();
                    syncExistingOrderToServer();
                }
                return;
            }

            // "Move Right"
            const moveRightBtn = e.target.closest('.btn-move-right');
            if (moveRightBtn) {
                const card = moveRightBtn.closest('.gallery-item-card');
                const next = card.nextElementSibling;
                if (next && next.classList.contains('gallery-item-card')) {
                    next.after(card);
                    updateExistingOrderBadges();
                    syncExistingOrderToServer();
                }
                return;
            }

            // "Delete Photo"
            const deleteBtn = e.target.closest('.btn-delete-existing');
            if (deleteBtn) {
                const card = deleteBtn.closest('.gallery-item-card');
                if (!confirm('Are you sure you want to remove this photo?')) return;

                const imageId = card.dataset.imageId;
                const checkbox = card.querySelector('.remove-gallery-checkbox');
                if (checkbox) checkbox.checked = true;

                card.classList.add('to-remove');
                card.style.display = 'none';
                updateExistingOrderBadges();

                // AJAX delete if available
                const deleteUrlBase = existingGalleryGrid.dataset.deleteUrlBase;
                if (deleteUrlBase) {
                    fetch(`${deleteUrlBase}/${imageId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            card.remove();
                            updateExistingOrderBadges();
                        }
                    })
                    .catch(err => console.error(err));
                }
                return;
            }
        });
    }

    // --- 3. Newly Picked Photos (Drag & Drop Reordering) ---
    function currentExistingGalleryCount() {
        if (!existingGalleryGrid) return 0;
        return existingGalleryGrid.querySelectorAll('.gallery-item-card:not(.to-remove)').length;
    }

    function updateGalleryLimitMessage(selectedFilesCount = 0) {
        if (!galleryLimitMessage) return;

        const currentCount = currentExistingGalleryCount();
        const totalCount = currentCount + selectedFilesCount;
        const remainingCount = Math.max(maxGalleryPhotos - currentCount, 0);

        galleryLimitMessage.textContent = `${totalCount}/${maxGalleryPhotos} photos active. (${remainingCount} slot${remainingCount === 1 ? '' : 's'} remaining)`;
        galleryLimitMessage.className = `small ${totalCount > maxGalleryPhotos || totalCount < 1 ? 'text-danger fw-bold' : 'text-muted'}`;

        if (galleryCountBadge) {
            galleryCountBadge.textContent = `${totalCount}/${maxGalleryPhotos} photos`;
            galleryCountBadge.className = `badge ${totalCount > 0 && totalCount <= maxGalleryPhotos ? 'bg-success' : 'bg-danger'}`;
        }
    }

    function syncGalleryInputFiles() {
        if (!galleryInput || typeof DataTransfer === 'undefined') return;

        const transfer = new DataTransfer();
        selectedGalleryFiles.forEach((file) => transfer.items.add(file));
        galleryInput.files = transfer.files;
    }

    function renderGalleryPreview() {
        if (!galleryPreview || !newGalleryPreviewSection) return;

        galleryPreview.innerHTML = '';

        if (selectedGalleryFiles.length === 0) {
            newGalleryPreviewSection.classList.add('d-none');
            return;
        }

        newGalleryPreviewSection.classList.remove('d-none');

        selectedGalleryFiles.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'new-gallery-card border rounded-3 p-2 bg-white position-relative shadow-sm';
            card.draggable = true;
            card.dataset.index = String(index);

            const topRow = document.createElement('div');
            topRow.className = 'd-flex justify-content-between align-items-center mb-1';
            topRow.innerHTML = `<span class="gallery-drag-handle text-muted" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span><span class="badge bg-secondary rounded-pill small">#${index + 1}</span>`;

            const imgContainer = document.createElement('div');
            imgContainer.className = 'rounded overflow-hidden border mb-1 bg-light';
            imgContainer.style.width = '100%';
            imgContainer.style.height = '75px';

            const img = document.createElement('img');
            img.alt = file.name;
            img.className = 'w-100 h-100 rounded';
            img.style.objectFit = 'cover';
            img.src = URL.createObjectURL(file);
            img.onload = () => URL.revokeObjectURL(img.src);
            imgContainer.appendChild(img);

            const name = document.createElement('div');
            name.className = 'small text-muted text-truncate mb-1';
            name.title = file.name;
            name.textContent = file.name;

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-xs btn-outline-danger w-100 remove-selected-gallery';
            removeButton.dataset.index = String(index);
            removeButton.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Remove';

            card.appendChild(topRow);
            card.appendChild(imgContainer);
            card.appendChild(name);
            card.appendChild(removeButton);
            galleryPreview.appendChild(card);
        });
    }

    // Drag and drop for newly selected files
    if (galleryPreview) {
        let draggedNewCard = null;

        galleryPreview.addEventListener('dragstart', function (e) {
            const card = e.target.closest('.new-gallery-card');
            if (!card) return;
            draggedNewCard = card;
            card.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.index);
        });

        galleryPreview.addEventListener('dragend', function (e) {
            if (draggedNewCard) draggedNewCard.classList.remove('is-dragging');
            galleryPreview.querySelectorAll('.drop-target').forEach(el => el.classList.remove('drop-target'));
            draggedNewCard = null;
        });

        galleryPreview.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!draggedNewCard) return;

            const targetCard = e.target.closest('.new-gallery-card');
            if (!targetCard || targetCard === draggedNewCard) return;

            const fromIdx = Number(draggedNewCard.dataset.index);
            const toIdx = Number(targetCard.dataset.index);

            // Reorder array
            const movedItem = selectedGalleryFiles.splice(fromIdx, 1)[0];
            selectedGalleryFiles.splice(toIdx, 0, movedItem);

            syncGalleryInputFiles();
            renderGalleryPreview();
        });

        galleryPreview.addEventListener('click', function (e) {
            const button = e.target.closest('.remove-selected-gallery');
            if (!button) return;

            selectedGalleryFiles.splice(Number(button.dataset.index), 1);
            syncGalleryInputFiles();
            renderGalleryPreview();
            updateGalleryLimitMessage(selectedGalleryFiles.length);
        });
    }

    galleryInput?.addEventListener('change', function () {
        const files = Array.from(this.files || []);
        const remainingCount = Math.max(maxGalleryPhotos - currentExistingGalleryCount(), 0);
        const currentKeys = new Set(selectedGalleryFiles.map(f => `${f.name}-${f.size}`));
        const newFiles = files.filter(f => !currentKeys.has(`${f.name}-${f.size}`));
        const nextFiles = [...selectedGalleryFiles, ...newFiles];

        if (nextFiles.length > remainingCount) {
            alert(`You can keep a maximum of ${maxGalleryPhotos} product detail photos. You can upload only ${remainingCount} more photo${remainingCount === 1 ? '' : 's'} right now.`);
            syncGalleryInputFiles();
            return;
        }

        selectedGalleryFiles.splice(0, selectedGalleryFiles.length, ...nextFiles);
        syncGalleryInputFiles();
        renderGalleryPreview();
        updateGalleryLimitMessage(selectedGalleryFiles.length);
    });

    // --- 4. Package Rows Management ---
    function packageRowTemplate(index) {
        return `
            <div class="package-row border rounded-3 bg-white p-3">
                <input type="hidden" name="packages[${index}][id]" value="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Package Size *</label>
                        <input type="text" name="packages[${index}][label]" class="form-control" placeholder="500g / 1kg" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Regular Price *</label>
                        <input type="number" step="0.01" name="packages[${index}][price]" class="form-control" placeholder="750" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sale Price</label>
                        <input type="number" step="0.01" name="packages[${index}][sale_price]" class="form-control" placeholder="675">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stock *</label>
                        <input type="number" name="packages[${index}][stock_qty]" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="packages[${index}][status]" class="form-select">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100 remove-package-row" title="Remove package">×</button>
                    </div>
                </div>
            </div>
        `;
    }

    function reindexPackageRows() {
        if (!packageRows) return;
        packageRows.querySelectorAll('.package-row').forEach((row, index) => {
            row.querySelectorAll('input, select').forEach((field) => {
                if (field.name) {
                    field.name = field.name.replace(/packages\[\d+\]/, `packages[${index}]`);
                }
            });
        });
    }

    addPackageRow?.addEventListener('click', function () {
        if (!packageRows) return;
        packageRows.insertAdjacentHTML('beforeend', packageRowTemplate(packageRows.querySelectorAll('.package-row').length));
    });

    packageRows?.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-package-row');
        if (!button) return;

        const rows = packageRows.querySelectorAll('.package-row');
        if (rows.length <= 1) {
            alert('At least one package size is required.');
            return;
        }

        button.closest('.package-row')?.remove();
        reindexPackageRows();
    });

    updateGalleryLimitMessage(selectedGalleryFiles.length);
});
</script>
