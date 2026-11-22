@php
    $oldPackages = old('packages');
    if (is_array($oldPackages)) {
        $packageRows = collect($oldPackages)->values();
    } elseif (isset($product) && $product->relationLoaded('variations') && $product->variations->count()) {
        $packageRows = $product->variations->filter(fn ($variation) => filled($variation->attr_val))->map(fn ($variation) => [
            'id' => $variation->id,
            'label' => $variation->attr_val,
            'sku' => $variation->sku,
            'price' => $variation->price,
            'sale_price' => $variation->sale_price,
            'stock_qty' => $variation->stock_qty,
            'status' => $variation->status,
        ])->values();
    } elseif (isset($product)) {
        // An existing product may contain only multi-attribute variations,
        // which are intentionally managed on the dedicated variations page.
        // Do not submit an empty required-looking package row during edit.
        $packageRows = collect();
    } else {
        $packageRows = collect([[
            'label' => '',
            'sku' => '',
            'price' => $product->price ?? '',
            'sale_price' => $product->sale_price ?? '',
            'stock_qty' => $product->stock_qty ?? 0,
            'status' => 'active',
        ]]);
    }

    $productInfo = old('product_information', $product->product_information ?? []);
    $removedPackageIds = old('removed_packages', []);
    $currentMainUrl = isset($product) && $product->image ? $product->image_url : null;
@endphp

@foreach($removedPackageIds as $removedPackageId)
    <input type="hidden" name="removed_packages[]" value="{{ $removedPackageId }}">
@endforeach

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
                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <label class="form-label fw-bold">Product Name *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name ?? '') }}" placeholder="e.g. A2 Gir Cow Ghee Bilona" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Product SKU (Code)</label>
                        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku ?? '') }}" placeholder="e.g. PRK-GHEE-001 (Auto if empty)">
                        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Unique inventory identifier. Leave empty to auto-generate.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Category *</label>
                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="card border-success-subtle bg-light mb-4">
                    <div class="card-header bg-white border-bottom pb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="mb-0 text-success fw-bold"><i class="fas fa-boxes me-2"></i>Package Size &amp; Regular Price</h5>
                                <small class="text-muted">Configure available pack sizes (e.g. 500g pack, 1kg pack, 5kg family pack, 1 Litre bottle) and regular prices.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-success px-3 shadow-sm" id="addPackageRow">
                                <i class="fas fa-plus me-1"></i> Add Package
                            </button>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 p-2 mt-2 bg-light rounded-2 border">
                            <span class="small text-dark fw-bold me-1">
                                <i class="fas fa-magic text-warning me-1"></i>Quick Presets:
                            </span>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 quick-add-size" data-size="500g pack" data-unit="g" style="font-size:0.8rem;">+ 500g Pack</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 quick-add-size" data-size="1kg pack" data-unit="kg" style="font-size:0.8rem;">+ 1kg Pack</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 quick-add-size" data-size="2kg pack" data-unit="kg" style="font-size:0.8rem;">+ 2kg Pack</button>
                                <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 quick-add-size fw-bold" data-size="5kg family pack" data-unit="family pack" style="font-size:0.8rem;">+ 5kg Family Pack ⭐</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 quick-add-size" data-size="10kg pack" data-unit="kg" style="font-size:0.8rem;">+ 10kg Pack</button>
                                <button type="button" class="btn btn-sm btn-outline-info py-1 px-2 quick-add-size" data-size="500ml bottle" data-unit="ml" style="font-size:0.8rem;">+ 500ml Bottle</button>
                                <button type="button" class="btn btn-sm btn-outline-info py-1 px-2 quick-add-size" data-size="1 Litre bottle" data-unit="Litre" style="font-size:0.8rem;">+ 1 Litre</button>
                                <button type="button" class="btn btn-sm btn-outline-info py-1 px-2 quick-add-size" data-size="5 Litre can" data-unit="Litre" style="font-size:0.8rem;">+ 5 Litre Can</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @error('packages')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        <div id="packageRows" class="vstack gap-3">
                            @foreach($packageRows as $index => $package)
                                @php
                                    $lbl = strtolower($package['label'] ?? '');
                                    $presetMatch = '';
                                    foreach (['500g pack', '1kg pack', '2kg pack', '5kg family pack', '10kg pack', '250g pack', '100g pack', '500ml bottle', '1 Litre bottle', '1 Litre', '2 Litre', '5 Litre can', '15 Litre tin', '1 Pack', '2 Pack Combo'] as $pOpt) {
                                        if (trim(strtolower($pOpt)) === trim($lbl) || str_contains($lbl, str_replace([' bottle', ' can', ' tin', ' pack'], '', strtolower($pOpt)))) {
                                            $presetMatch = $pOpt;
                                            break;
                                        }
                                    }
                                @endphp
                                <div class="package-row border border-2 rounded-3 bg-white p-3 shadow-sm mb-2 position-relative">
                                    <input type="hidden" name="packages[{{ $index }}][id]" value="{{ $package['id'] ?? '' }}">
                                    
                                    <!-- Header of package box -->
                                    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-6 fw-semibold">
                                                <i class="fas fa-box-open me-1"></i> Package Option #<span class="package-number">{{ $index + 1 }}</span>
                                            </span>
                                            <span class="badge bg-light text-dark border px-2 py-1 package-preview-label fw-bold">
                                                {{ $package['label'] ?? 'New Package' }}
                                            </span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-package-row py-1 px-3" title="Remove this package option">
                                            <i class="fas fa-trash-alt me-1"></i> Remove
                                        </button>
                                    </div>

                                    <!-- Tier 1: Size & Classification -->
                                    <div class="row g-3 mb-3">
                                        <div class="col-lg-4 col-md-6 col-12">
                                            <label class="form-label small fw-bold text-success mb-1">
                                                <i class="fas fa-magic me-1"></i>Size Preset (Quick Pick)
                                            </label>
                                            <select class="form-select package-preset-select">
                                                <option value="">-- Choose Preset Size --</option>
                                                <optgroup label="🌱 Solids (Grams &amp; Kilograms)">
                                                    <option value="100g pack" @selected($presetMatch === '100g pack')>100g pack</option>
                                                    <option value="250g pack" @selected($presetMatch === '250g pack')>250g pack</option>
                                                    <option value="500g pack" @selected($presetMatch === '500g pack' || $lbl === '500g')>500g pack</option>
                                                    <option value="1kg pack" @selected($presetMatch === '1kg pack' || $lbl === '1kg')>1kg pack</option>
                                                    <option value="2kg pack" @selected($presetMatch === '2kg pack' || $lbl === '2kg')>2kg pack</option>
                                                    <option value="5kg pack" @selected($presetMatch === '5kg pack' || $lbl === '5kg')>5kg pack</option>
                                                    <option value="5kg family pack" @selected(str_contains($lbl, 'family'))>5kg family pack ⭐</option>
                                                    <option value="10kg pack" @selected($presetMatch === '10kg pack' || $lbl === '10kg')>10kg pack</option>
                                                    <option value="25kg sack" @selected($presetMatch === '25kg sack')>25kg sack</option>
                                                </optgroup>
                                                <optgroup label="💧 Liquids (Litres &amp; Millilitres)">
                                                    <option value="100ml bottle" @selected($presetMatch === '100ml bottle')>100ml bottle</option>
                                                    <option value="250ml bottle" @selected($presetMatch === '250ml bottle')>250ml bottle</option>
                                                    <option value="500ml bottle" @selected($presetMatch === '500ml bottle' || $lbl === '500ml')>500ml bottle</option>
                                                    <option value="1 Litre bottle" @selected($presetMatch === '1 Litre bottle' || $lbl === '1 litre' || $lbl === '1l')>1 Litre bottle</option>
                                                    <option value="2 Litre" @selected($presetMatch === '2 Litre')>2 Litre</option>
                                                    <option value="5 Litre can" @selected($presetMatch === '5 Litre can')>5 Litre can</option>
                                                    <option value="15 Litre tin" @selected($presetMatch === '15 Litre tin')>15 Litre tin</option>
                                                </optgroup>
                                                <optgroup label="📦 Counts &amp; Combos">
                                                    <option value="1 Pack" @selected($presetMatch === '1 Pack')>1 Pack</option>
                                                    <option value="2 Pack Combo" @selected($presetMatch === '2 Pack Combo')>2 Pack Combo</option>
                                                </optgroup>
                                            </select>
                                        </div>

                                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                            <label class="form-label small fw-bold mb-1">
                                                Package Size Display <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="packages[{{ $index }}][label]" class="form-control package-label-input @error("packages.$index.label") is-invalid @enderror" value="{{ $package['label'] ?? '' }}" placeholder="e.g. 500g pack / 1kg" required>
                                            @error("packages.$index.label")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                                            <label class="form-label small fw-bold mb-1">Packaging Unit</label>
                                            <select class="form-select package-unit-select" title="Packaging Weight / Volume Type">
                                                <option value="g" @selected(preg_match('/(?:\b|\d)g(?:m|s)?\b/i', $package['label'] ?? '') && !preg_match('/kg/i', $package['label'] ?? ''))>Grams (g)</option>
                                                <option value="kg" @selected(preg_match('/(?:\b|\d)kg\b/i', $package['label'] ?? '') && !preg_match('/family/i', $package['label'] ?? ''))>Kilograms (kg)</option>
                                                <option value="ml" @selected(preg_match('/(?:\b|\d)ml\b/i', $package['label'] ?? ''))>Millilitres (ml)</option>
                                                <option value="Litre" @selected(preg_match('/(?:litre|ltr|\bl\b)/i', $package['label'] ?? ''))>Litres (Litre)</option>
                                                <option value="family pack" @selected(preg_match('/family\s*pack/i', $package['label'] ?? ''))>Family Pack</option>
                                                <option value="pack" @selected(preg_match('/pack\b/i', $package['label'] ?? '') && !preg_match('/family/i', $package['label'] ?? ''))>Pack</option>
                                                <option value="pcs">Pieces (Pcs)</option>
                                            </select>
                                        </div>

                                        <div class="col-lg-3 col-md-6 col-12">
                                            <label class="form-label small fw-bold mb-1">Package SKU</label>
                                            <input type="text" name="packages[{{ $index }}][sku]" class="form-control @error("packages.$index.sku") is-invalid @enderror" value="{{ $package['sku'] ?? '' }}" placeholder="Auto if empty">
                                            @error("packages.$index.sku")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <!-- Tier 2: Pricing, Inventory & Status -->
                                    <div class="row g-3 p-3 rounded-2 bg-light border">
                                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                            <label class="form-label small fw-bold mb-1 text-dark">Regular Price <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white fw-bold">₹</span>
                                                <input type="number" step="0.01" name="packages[{{ $index }}][price]" class="form-control @error("packages.$index.price") is-invalid @enderror" value="{{ $package['price'] ?? '' }}" placeholder="750" required>
                                            </div>
                                            @error("packages.$index.price")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                            <label class="form-label small fw-bold mb-1 text-dark">Sale Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white fw-bold">₹</span>
                                                <input type="number" step="0.01" name="packages[{{ $index }}][sale_price]" class="form-control @error("packages.$index.sale_price") is-invalid @enderror" value="{{ $package['sale_price'] ?? '' }}" placeholder="675">
                                            </div>
                                            @error("packages.$index.sale_price")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                            <label class="form-label small fw-bold mb-1 text-dark">Stock Quantity <span class="text-danger">*</span></label>
                                            <input type="number" name="packages[{{ $index }}][stock_qty]" class="form-control @error("packages.$index.stock_qty") is-invalid @enderror" value="{{ $package['stock_qty'] ?? 0 }}" min="0" required>
                                            @error("packages.$index.stock_qty")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                            <label class="form-label small fw-bold mb-1 text-dark">Status</label>
                                            <select name="packages[{{ $index }}][status]" class="form-select">
                                                <option value="active" @selected(($package['status'] ?? 'active') === 'active')>Active</option>
                                                <option value="inactive" @selected(($package['status'] ?? '') === 'inactive')>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-3 p-2 bg-success-subtle rounded-2 text-success small border border-success-subtle">
                            <i class="fas fa-info-circle fs-5"></i>
                            <span>The first active package becomes the primary product price. Total package stock is synced automatically.</span>
                        </div>
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

                <div class="card border-success-subtle bg-light mb-4 mt-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><i class="fas fa-apple-alt text-success me-2"></i>Nutritional Facts &amp; Verification</h5>
                            <small class="text-muted">Powers Family Pack nutrition engine and product detail nutrition tab.</small>
                        </div>
                        @php
                            $currentNutri = old('nutritional_info', $product->nutritional_info ?? null);
                            $source = is_array($currentNutri) ? ($currentNutri['source'] ?? 'unverified') : 'unverified';
                            $verifiedLabel = is_array($currentNutri) ? ($currentNutri['verified_label'] ?? '') : '';
                            $proofImage = is_array($currentNutri) ? ($currentNutri['packaging_proof'] ?? null) : null;
                        @endphp
                        <span class="badge {{ $source === 'packaging_verified' ? 'bg-success' : ($source === 'label_unreviewed' ? 'bg-info text-dark' : ($source === 'ifct_reference_estimate' ? 'bg-primary' : 'bg-warning text-dark')) }}">
                            {{ $source === 'packaging_verified' ? 'Verified against packaging' : ($source === 'label_unreviewed' ? 'From label (not reviewed)' : ($source === 'ifct_reference_estimate' ? 'Reference estimate' : 'Incomplete / missing')) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Status of these values</label>
                                <select name="nutrition_source" class="form-select form-select-sm">
                                    <option value="label_unreviewed" @selected(in_array(old('nutrition_source', $source), ['label_unreviewed', 'needs_review', 'unverified'], true))>Automatic: entered or read from the label (not reviewed)</option>
                                    <option value="packaging_verified" @selected(old('nutrition_source', $source) === 'packaging_verified')>I have checked these values against the pack</option>
                                    <option value="ifct_reference_estimate" @selected(old('nutrition_source', $source) === 'ifct_reference_estimate')>These are reference-table estimates, not label values</option>
                                </select>
                                <small class="text-muted">Values are never shown as "verified" unless you choose it here.</small>
                                @php $currentGroup = is_array($currentNutri) && ! empty($currentNutri['manual_group']) ? ($currentNutri['food_group'] ?? '') : ''; @endphp
                                <label class="form-label small fw-bold mt-2">Food group for Family Pack</label>
                                <select name="food_group" class="form-select form-select-sm">
                                    <option value="">Automatic (from product name)</option>
                                    @foreach (\App\Services\ProductNutritionService::FOOD_GROUPS as $gKey => $gLabel)
                                        <option value="{{ $gKey }}" @selected(old('food_group', $currentGroup) === $gKey)>{{ $gLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Packaging Image (OCR / Proof)</label>
                                <input type="file" name="nutrition_image" class="form-control form-control-sm" accept="image/*">
                                @if($proofImage)
                                    <small class="text-success"><i class="fas fa-check-circle"></i> Packaging proof attached (<a href="{{ asset('storage/' . ltrim($proofImage, '/')) }}" target="_blank">View</a>)</small>
                                @endif
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Nutritional Values (Paste table, enter text, or JSON)</label>
                                @php
                                    $nutriText = '';
                                    if (is_array($currentNutri)) {
                                        if (isset($currentNutri['per_100g']) && is_array($currentNutri['per_100g'])) {
                                            $p = $currentNutri['per_100g'];
                                            $nutriText = "Energy: " . ($p['energy_kcal'] ?? 0) . " kcal\n"
                                                       . "Protein: " . ($p['protein_g'] ?? 0) . "g\n"
                                                       . "Carbohydrates: " . ($p['carbs_g'] ?? 0) . "g\n"
                                                       . "Total Fat: " . ($p['fat_g'] ?? 0) . "g\n"
                                                       . "Dietary Fibre: " . ($p['fiber_g'] ?? 0) . "g\n"
                                                       . "Sodium: " . ($p['sodium_mg'] ?? 0) . "mg\n"
                                                       . "Serving Size: " . ($currentNutri['serving_size'] ?? '100g');
                                        } else {
                                            $nutriText = json_encode($currentNutri, JSON_PRETTY_PRINT);
                                        }
                                    } elseif (is_string($currentNutri)) {
                                        $nutriText = $currentNutri;
                                    }
                                @endphp
                                <textarea name="nutritional_info" class="form-control @error('nutritional_info') is-invalid @enderror" rows="7" placeholder="Example:&#10;Energy: 345 kcal&#10;Protein: 22g&#10;Carbohydrates: 63g&#10;Total Fat: 1.0g&#10;Dietary Fibre: 15g&#10;Sodium: 17mg&#10;Serving Size: 100g">{{ old('nutritional_info', $nutriText) }}</textarea>
                                @error('nutritional_info')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Values are automatically normalized to per 100g and validated before saving. Uncertain or incomplete information is flagged for review.</small>
                            </div>
                        </div>
                    </div>
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
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Packaging Type</span>
                                    <small class="text-muted">Choose packaging</small>
                                </label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-white"><i class="fas fa-box text-success"></i></span>
                                    <select class="form-select" id="packaging_preset_select">
                                        <option value="">-- Choose Packaging --</option>
                                        <option value="Glass Jar">🏺 Glass Jar</option>
                                        <option value="PET Jar">🫙 PET Jar</option>
                                        <option value="Glass Bottle">🍾 Glass Bottle</option>
                                        <option value="PET Bottle">🍼 PET Bottle</option>
                                        <option value="Pouch">📦 Pouch</option>
                                        <option value="Vacuum Pack">🔒 Vacuum Pack</option>
                                        <option value="Tin / Can">🥫 Tin / Can</option>
                                        <option value="Paper Box">📄 Paper Box</option>
                                        <option value="__custom__">Custom / Other</option>
                                    </select>
                                </div>
                                <input type="text" name="product_information[packaging_type]" id="packaging_type_input" class="form-control" value="{{ $productInfo['packaging_type'] ?? '' }}" placeholder="Default: Pouch">
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <span class="badge bg-light text-dark border packaging-pill" role="button" style="cursor:pointer;" data-val="Glass Jar">🏺 Glass Jar</span>
                                    <span class="badge bg-light text-dark border packaging-pill" role="button" style="cursor:pointer;" data-val="PET Jar">🫙 PET Jar</span>
                                    <span class="badge bg-light text-dark border packaging-pill" role="button" style="cursor:pointer;" data-val="Glass Bottle">🍾 Glass Bottle</span>
                                    <span class="badge bg-light text-dark border packaging-pill" role="button" style="cursor:pointer;" data-val="PET Bottle">🍼 PET Bottle</span>
                                    <span class="badge bg-light text-dark border packaging-pill" role="button" style="cursor:pointer;" data-val="Pouch">📦 Pouch</span>
                                    <span class="badge bg-light text-dark border packaging-pill" role="button" style="cursor:pointer;" data-val="Vacuum Pack">🔒 Vacuum Pack</span>
                                    <span class="badge bg-light text-dark border packaging-pill" role="button" style="cursor:pointer;" data-val="Tin / Can">🥫 Tin / Can</span>
                                    <span class="badge bg-light text-dark border packaging-pill" role="button" style="cursor:pointer;" data-val="Paper Box">📄 Paper Box</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Storage</label>
                                <input type="text" name="product_information[storage]" class="form-control" value="{{ $productInfo['storage'] ?? '' }}" placeholder="Default: Store in dry, airtight container">
                            </div>
                            <div class="col-12 mt-3">
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 text-success fw-bold">
                                            <i class="fas fa-layer-group me-1"></i> Storefront Detail Accordions (Hyphen Style)
                                        </h6>
                                        <span class="badge bg-secondary-subtle text-secondary small">Conditional Display</span>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Only sections filled below will appear on the storefront. If a section is left empty, it will be hidden automatically.
                                    </p>

                                    <!-- 1. Product Info (What is it? / How it works?) -->
                                    <div class="card p-3 mb-3 border-0 shadow-sm bg-white rounded-3">
                                        <h6 class="fw-semibold text-dark mb-1 small text-uppercase">1. Product Info</h6>
                                        <small class="text-muted mb-2 d-block">Shown in "PRODUCT INFO" tab with toggle pills [What is it?] and [How it does?]</small>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">What is it? (Description / Overview)</label>
                                                <textarea name="product_information[what_is_it]" class="form-control form-control-sm" rows="3" placeholder="e.g. 100% natural and unpolished grains sourced directly from organic cooperatives...">{{ $productInfo['what_is_it'] ?? '' }}</textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">How it does? (How it works / Key Benefits)</label>
                                                <textarea name="product_information[how_it_works]" class="form-control form-control-sm" rows="3" placeholder="e.g. Rich in natural dietary fiber, plant protein, and micro-nutrients...">{{ $productInfo['how_it_works'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Key Ingredients -->
                                    <div class="card p-3 mb-3 border-0 shadow-sm bg-white rounded-3">
                                        <h6 class="fw-semibold text-dark mb-1 small text-uppercase">2. Key Ingredients</h6>
                                        <small class="text-muted mb-2 d-block">Shown in "KEY INGREDIENTS" accordion. Separate ingredients with commas or newlines.</small>
                                        <textarea name="product_information[key_ingredients]" class="form-control form-control-sm" rows="2" placeholder="e.g. Single-Origin Emmer Wheat, Cold Pressed Virgin Mustard Seeds">{{ $productInfo['key_ingredients'] ?? '' }}</textarea>
                                    </div>

                                    <!-- 3. How to Use -->
                                    <div class="card p-3 mb-3 border-0 shadow-sm bg-white rounded-3">
                                        <h6 class="fw-semibold text-dark mb-1 small text-uppercase">3. How to Use</h6>
                                        <small class="text-muted mb-2 d-block">Shown in "HOW TO USE" accordion.</small>
                                        <textarea name="product_information[how_to_use]" class="form-control form-control-sm" rows="2" placeholder="e.g. Soak for 30 minutes before boiling. Cook on medium flame until tender...">{{ $productInfo['how_to_use'] ?? '' }}</textarea>
                                    </div>

                                    <!-- 4. Full Ingredients List -->
                                    <div class="card p-3 mb-3 border-0 shadow-sm bg-white rounded-3">
                                        <h6 class="fw-semibold text-dark mb-1 small text-uppercase">4. Full Ingredients List</h6>
                                        <small class="text-muted mb-2 d-block">Shown in "FULL INGREDIENTS LIST" accordion.</small>
                                        <textarea name="product_information[full_ingredients_list]" class="form-control form-control-sm" rows="2" placeholder="e.g. 100% Raw Unpolished Moong Dal (Vigna radiata). Zero added coloring agents or preservatives.">{{ $productInfo['full_ingredients_list'] ?? '' }}</textarea>
                                    </div>

                                    <!-- 5. Certificate & Lab Reports (Multiple Boxes) -->
                                    @php
                                        $clinicalReportPath = $productInfo['clinical_report_image'] ?? ($product->product_information['clinical_report_image'] ?? null);
                                        $certificateReports = old('certificate_reports', $productInfo['certificate_reports'] ?? []);
                                        if (empty($certificateReports) && !empty($clinicalReportPath)) {
                                            $certificateReports = [
                                                [
                                                    'title' => 'Clinically Tested Claims',
                                                    'short_desc' => !empty($productInfo['clinically_tested']) ? \Illuminate\Support\Str::limit($productInfo['clinically_tested'], 35) : 'Certified',
                                                    'image' => $clinicalReportPath,
                                                ]
                                            ];
                                        }
                                    @endphp
                                    <div class="card p-3 mb-2 border-0 shadow-sm bg-white rounded-3">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                            <div>
                                                <h6 class="fw-semibold text-dark mb-0 small text-uppercase">
                                                    <i class="fas fa-certificate text-warning me-1"></i> 5. Certificate & Lab Reports
                                                </h6>
                                                <small class="text-muted">Displays multiple green cards on storefront. Clicking "Check Reports" opens the image in a scrollable popup.</small>
                                            </div>
                                            <button type="button" class="btn btn-xs btn-outline-success fw-semibold" id="btnAddCertificateReport">
                                                <i class="fas fa-plus me-1"></i> Add Report Box
                                            </button>
                                        </div>

                                        <div id="certificateReportsList" class="d-flex flex-column gap-3 mt-2">
                                            @forelse($certificateReports as $cIndex => $cReport)
                                                <div class="certificate-report-row border rounded-3 bg-light p-3 shadow-sm position-relative">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                                            <i class="fas fa-file-medical-alt me-1"></i> Report Box #<span class="report-box-num">{{ $loop->iteration }}</span>
                                                        </span>
                                                        <button type="button" class="btn btn-xs btn-outline-danger remove-certificate-report-btn" title="Remove this report box">
                                                            <i class="fas fa-trash-alt me-1"></i> Remove Box
                                                        </button>
                                                    </div>
                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <label class="form-label small fw-bold text-dark">Report Title *</label>
                                                            <input type="text" name="certificate_reports[{{ $cIndex }}][title]" class="form-control form-control-sm" placeholder="e.g. In-Vivo & In-Vitro / 100% Pesticide Free" value="{{ $cReport['title'] ?? '' }}" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small fw-bold text-dark">Short Description / Badge</label>
                                                            <input type="text" name="certificate_reports[{{ $cIndex }}][short_desc]" class="form-control form-control-sm" placeholder="e.g. Certified / NABL Lab Tested" value="{{ $cReport['short_desc'] ?? 'Certified' }}">
                                                        </div>
                                                        <div class="col-12 mt-2">
                                                            <label class="form-label small fw-bold text-dark">Certificate Report Image (Opens in Popup)</label>
                                                            @if(!empty($cReport['image']))
                                                                <div class="d-flex align-items-center gap-3 p-2 border rounded bg-white mb-2">
                                                                    <img src="{{ route('media.file', ['path' => $cReport['image']]) }}" alt="Report Preview" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                                                    <div class="flex-grow-1">
                                                                        <a href="{{ route('media.file', ['path' => $cReport['image']]) }}" target="_blank" class="small fw-semibold text-primary">View Current Certificate</a>
                                                                        <input type="hidden" name="certificate_reports[{{ $cIndex }}][image]" value="{{ $cReport['image'] }}">
                                                                    </div>
                                                                    <label class="form-check-label small text-danger">
                                                                        <input type="checkbox" name="certificate_reports[{{ $cIndex }}][remove]" value="1" class="form-check-input me-1">
                                                                        Remove Image
                                                                    </label>
                                                                </div>
                                                            @endif
                                                            <input type="file" name="certificate_reports_file[{{ $cIndex }}]" class="form-control form-control-sm" accept="image/*">
                                                            <small class="text-muted">Upload certificate or lab test document image (JPEG, PNG, WebP). Max 12MB.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div id="noCertificateReportsNotice" class="text-muted small py-2 text-center bg-light rounded border">
                                                    No certificate reports added yet. Click <strong>"Add Report Box"</strong> to add one.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <!-- 6. FAQs (Optional) -->
                                    <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                                        <h6 class="fw-semibold text-dark mb-1 small text-uppercase">6. FAQs (Optional)</h6>
                                        <small class="text-muted mb-2 d-block">Shown in "FAQS" accordion if entered.</small>
                                        <textarea name="product_information[faqs]" class="form-control form-control-sm" rows="2" placeholder="e.g. Q: Is this certified organic? A: Yes, certified by NABL accredited lab.">{{ $productInfo['faqs'] ?? '' }}</textarea>
                                    </div>
                                </div>
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

                    @if (isset($product) && $product->images->count())
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
                                @foreach ($product->images as $index => $img)
                                    @php
                                        $isThisMain = ($product->image === $img->image);
                                    @endphp
                                    <div class="gallery-item-card border rounded-3 p-2 bg-white shadow-sm position-relative"
                                         draggable="true"
                                         data-image-id="{{ $img->id }}"
                                         data-image-url="{{ $img->image_url }}">
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="gallery-drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical text-muted"></i> Drag</span>
                                            <span class="badge bg-dark rounded-pill gallery-order-badge">#{{ $index + 1 }}</span>
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
    function detectUnitFromText(text) {
        text = String(text || '').toLowerCase().trim();
        if (text.includes('family')) return 'family pack';
        if (/\b(?:kg|kgs|kilo|kilogram)\b/.test(text)) return 'kg';
        if (/\b(?:ml)\b/.test(text)) return 'ml';
        if (/\b(?:litre|liter|ltr|\bl\b)/.test(text)) return 'Litre';
        if (/\b(?:g|gm|gms|gram|grams)\b/.test(text)) return 'g';
        if (/\b(?:pcs|piece|pieces)\b/.test(text)) return 'pcs';
        if (/\b(?:pack|combo|box)\b/.test(text)) return 'pack';
        return 'g';
    }

    function packageRowTemplate(index, defaultSize = '', defaultUnit = '') {
        const pSize = defaultSize || '';
        const pUnit = defaultUnit || (defaultSize ? detectUnitFromText(defaultSize) : 'g');
        return `
            <div class="package-row border border-2 rounded-3 bg-white p-3 shadow-sm mb-2 position-relative">
                <input type="hidden" name="packages[${index}][id]" value="">
                
                <!-- Header of package box -->
                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-6 fw-semibold">
                            <i class="fas fa-box-open me-1"></i> Package Option #<span class="package-number">${index + 1}</span>
                        </span>
                        <span class="badge bg-light text-dark border px-2 py-1 package-preview-label fw-bold">
                            ${pSize || 'New Package'}
                        </span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-package-row py-1 px-3" title="Remove this package option">
                        <i class="fas fa-trash-alt me-1"></i> Remove
                    </button>
                </div>

                <!-- Tier 1: Size & Classification -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label small fw-bold text-success mb-1">
                            <i class="fas fa-magic me-1"></i>Size Preset (Quick Pick)
                        </label>
                        <select class="form-select package-preset-select">
                            <option value="">-- Choose Preset Size --</option>
                            <optgroup label="🌱 Solids (Grams &amp; Kilograms)">
                                <option value="100g pack" ${pSize === '100g pack' ? 'selected' : ''}>100g pack</option>
                                <option value="250g pack" ${pSize === '250g pack' ? 'selected' : ''}>250g pack</option>
                                <option value="500g pack" ${pSize === '500g pack' ? 'selected' : ''}>500g pack</option>
                                <option value="1kg pack" ${pSize === '1kg pack' ? 'selected' : ''}>1kg pack</option>
                                <option value="2kg pack" ${pSize === '2kg pack' ? 'selected' : ''}>2kg pack</option>
                                <option value="5kg pack" ${pSize === '5kg pack' ? 'selected' : ''}>5kg pack</option>
                                <option value="5kg family pack" ${pSize === '5kg family pack' ? 'selected' : ''}>5kg family pack ⭐</option>
                                <option value="10kg pack" ${pSize === '10kg pack' ? 'selected' : ''}>10kg pack</option>
                                <option value="25kg sack" ${pSize === '25kg sack' ? 'selected' : ''}>25kg sack</option>
                            </optgroup>
                            <optgroup label="💧 Liquids (Litres &amp; Millilitres)">
                                <option value="100ml bottle" ${pSize === '100ml bottle' ? 'selected' : ''}>100ml bottle</option>
                                <option value="250ml bottle" ${pSize === '250ml bottle' ? 'selected' : ''}>250ml bottle</option>
                                <option value="500ml bottle" ${pSize === '500ml bottle' ? 'selected' : ''}>500ml bottle</option>
                                <option value="1 Litre bottle" ${pSize === '1 Litre bottle' ? 'selected' : ''}>1 Litre bottle</option>
                                <option value="2 Litre" ${pSize === '2 Litre' ? 'selected' : ''}>2 Litre</option>
                                <option value="5 Litre can" ${pSize === '5 Litre can' ? 'selected' : ''}>5 Litre can</option>
                                <option value="15 Litre tin" ${pSize === '15 Litre tin' ? 'selected' : ''}>15 Litre tin</option>
                            </optgroup>
                            <optgroup label="📦 Counts &amp; Combos">
                                <option value="1 Pack" ${pSize === '1 Pack' ? 'selected' : ''}>1 Pack</option>
                                <option value="2 Pack Combo" ${pSize === '2 Pack Combo' ? 'selected' : ''}>2 Pack Combo</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <label class="form-label small fw-bold mb-1">
                            Package Size Display <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="packages[${index}][label]" class="form-control package-label-input" value="${pSize}" placeholder="e.g. 500g pack / 1kg" required>
                    </div>

                    <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                        <label class="form-label small fw-bold mb-1">Packaging Unit</label>
                        <select class="form-select package-unit-select" title="Packaging Weight / Volume Type">
                            <option value="g" ${pUnit === 'g' ? 'selected' : ''}>Grams (g)</option>
                            <option value="kg" ${pUnit === 'kg' ? 'selected' : ''}>Kilograms (kg)</option>
                            <option value="ml" ${pUnit === 'ml' ? 'selected' : ''}>Millilitres (ml)</option>
                            <option value="Litre" ${pUnit === 'Litre' ? 'selected' : ''}>Litres (Litre)</option>
                            <option value="family pack" ${pUnit === 'family pack' ? 'selected' : ''}>Family Pack</option>
                            <option value="pack" ${pUnit === 'pack' ? 'selected' : ''}>Pack</option>
                            <option value="pcs">Pieces (Pcs)</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <label class="form-label small fw-bold mb-1">Package SKU</label>
                        <input type="text" name="packages[${index}][sku]" class="form-control" placeholder="Auto if empty">
                    </div>
                </div>

                <!-- Tier 2: Pricing, Inventory & Status -->
                <div class="row g-3 p-3 rounded-2 bg-light border">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                        <label class="form-label small fw-bold mb-1 text-dark">Regular Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white fw-bold">₹</span>
                            <input type="number" step="0.01" name="packages[${index}][price]" class="form-control" placeholder="750" required>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                        <label class="form-label small fw-bold mb-1 text-dark">Sale Price</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white fw-bold">₹</span>
                            <input type="number" step="0.01" name="packages[${index}][sale_price]" class="form-control" placeholder="675">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                        <label class="form-label small fw-bold mb-1 text-dark">Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="packages[${index}][stock_qty]" class="form-control" value="0" min="0" required>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                        <label class="form-label small fw-bold mb-1 text-dark">Status</label>
                        <select name="packages[${index}][status]" class="form-select">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    }

    function reindexPackageRows() {
        if (!packageRows) return;
        packageRows.querySelectorAll('.package-row').forEach((row, index) => {
            const numSpan = row.querySelector('.package-number');
            if (numSpan) numSpan.textContent = index + 1;
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

    // Preset select or Unit select changed
    packageRows?.addEventListener('change', function (event) {
        const presetSelect = event.target.closest('.package-preset-select');
        if (presetSelect) {
            const row = presetSelect.closest('.package-row');
            const labelInput = row?.querySelector('.package-label-input');
            const unitSelect = row?.querySelector('.package-unit-select');
            const previewLabel = row?.querySelector('.package-preview-label');
            const val = presetSelect.value;
            if (val && labelInput) {
                labelInput.value = val;
                if (previewLabel) previewLabel.textContent = val;
                if (unitSelect) {
                    unitSelect.value = detectUnitFromText(val);
                }
            }
            return;
        }

        const unitSelect = event.target.closest('.package-unit-select');
        if (unitSelect) {
            const row = unitSelect.closest('.package-row');
            const labelInput = row?.querySelector('.package-label-input');
            const previewLabel = row?.querySelector('.package-preview-label');
            if (labelInput) {
                const currentVal = labelInput.value.trim();
                const selectedUnit = unitSelect.value;
                const match = currentVal.match(/^(\d+(?:\.\d+)?)\s*(?:g|gm|gms|kg|kgs|ml|l|ltr|litre|liter|pack|pcs)?\s*(.*)$/i);
                if (match) {
                    const num = match[1];
                    const extra = match[2].trim();
                    let suffix = selectedUnit;
                    if (selectedUnit === 'family pack') {
                        suffix = num + 'kg family pack';
                    } else if (selectedUnit === 'Litre') {
                        suffix = num + ' Litre' + (extra ? ' ' + extra : ' bottle');
                    } else if (selectedUnit === 'ml') {
                        suffix = num + 'ml' + (extra ? ' ' + extra : ' bottle');
                    } else if (selectedUnit === 'kg') {
                        suffix = num + 'kg' + (extra ? ' ' + extra : ' pack');
                    } else if (selectedUnit === 'g') {
                        suffix = num + 'g' + (extra ? ' ' + extra : ' pack');
                    } else {
                        suffix = num + ' ' + selectedUnit;
                    }
                    labelInput.value = suffix;
                    if (previewLabel) previewLabel.textContent = suffix;
                }
            }
            return;
        }
    });

    // Auto-detect unit and update live badge when user types in Package Size input
    packageRows?.addEventListener('input', function (event) {
        const labelInput = event.target.closest('.package-label-input');
        if (labelInput) {
            const row = labelInput.closest('.package-row');
            const unitSelect = row?.querySelector('.package-unit-select');
            const previewLabel = row?.querySelector('.package-preview-label');
            if (previewLabel) previewLabel.textContent = labelInput.value.trim() || 'New Package';
            if (unitSelect && labelInput.value.trim().length >= 2) {
                const detected = detectUnitFromText(labelInput.value);
                if (detected) unitSelect.value = detected;
            }
        }
    });

    // Quick Add Preset Buttons in card header
    document.querySelectorAll('.quick-add-size').forEach((btn) => {
        btn.addEventListener('click', function () {
            const size = this.dataset.size || '';
            const unit = this.dataset.unit || detectUnitFromText(size);
            if (!packageRows) return;

            const rows = packageRows.querySelectorAll('.package-row');
            if (rows.length === 1) {
                const firstLabel = rows[0].querySelector('.package-label-input');
                if (firstLabel && !firstLabel.value.trim()) {
                    firstLabel.value = size;
                    const uSel = rows[0].querySelector('.package-unit-select');
                    if (uSel) uSel.value = unit;
                    const pSel = rows[0].querySelector('.package-preset-select');
                    if (pSel) pSel.value = size;
                    const prevBadge = rows[0].querySelector('.package-preview-label');
                    if (prevBadge) prevBadge.textContent = size;
                    return;
                }
            }

            const newIndex = rows.length;
            packageRows.insertAdjacentHTML('beforeend', packageRowTemplate(newIndex, size, unit));
        });
    });

    packageRows?.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-package-row');
        if (!button) return;

        const rows = packageRows.querySelectorAll('.package-row');
        if (rows.length <= 1) {
            alert('At least one package size is required.');
            return;
        }

        const row = button.closest('.package-row');
        const variationId = row?.querySelector('input[name$="[id]"]')?.value;
        const form = packageRows.closest('form');

        // A missing row alone is not enough because multi-attribute
        // variations are deliberately not rendered in this package editor.
        // Tell the server only when the admin explicitly removes a saved row.
        if (variationId && form && !form.querySelector(`input[name="removed_packages[]"][value="${variationId}"]`)) {
            const removedInput = document.createElement('input');
            removedInput.type = 'hidden';
            removedInput.name = 'removed_packages[]';
            removedInput.value = variationId;
            form.appendChild(removedInput);
        }

        row?.remove();
        reindexPackageRows();
    });

    // --- 5. Certificate Reports Management ---
    const certificateReportsList = document.getElementById('certificateReportsList');
    const btnAddCertificateReport = document.getElementById('btnAddCertificateReport');
    const noCertificateReportsNotice = document.getElementById('noCertificateReportsNotice');

    function certificateReportTemplate(index) {
        return `
            <div class="certificate-report-row border rounded-3 bg-light p-3 shadow-sm position-relative">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                        <i class="fas fa-file-medical-alt me-1"></i> Report Box #<span class="report-box-num">${index + 1}</span>
                    </span>
                    <button type="button" class="btn btn-xs btn-outline-danger remove-certificate-report-btn" title="Remove this report box">
                        <i class="fas fa-trash-alt me-1"></i> Remove Box
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-dark">Report Title *</label>
                        <input type="text" name="certificate_reports[${index}][title]" class="form-control form-control-sm" placeholder="e.g. In-Vivo & In-Vitro / 100% Pesticide Free" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-dark">Short Description / Badge</label>
                        <input type="text" name="certificate_reports[${index}][short_desc]" class="form-control form-control-sm" placeholder="e.g. Certified / NABL Lab Tested" value="Certified">
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label small fw-bold text-dark">Certificate Report Image (Opens in Popup)</label>
                        <input type="file" name="certificate_reports_file[${index}]" class="form-control form-control-sm" accept="image/*">
                        <small class="text-muted">Upload certificate or lab test document image (JPEG, PNG, WebP). Max 12MB.</small>
                    </div>
                </div>
            </div>
        `;
    }

    function reindexCertificateReports() {
        if (!certificateReportsList) return;
        const rows = certificateReportsList.querySelectorAll('.certificate-report-row');
        if (noCertificateReportsNotice) {
            noCertificateReportsNotice.style.display = rows.length === 0 ? 'block' : 'none';
        }
        rows.forEach((row, index) => {
            const numSpan = row.querySelector('.report-box-num');
            if (numSpan) numSpan.textContent = index + 1;
            row.querySelectorAll('input, select, textarea').forEach((field) => {
                if (field.name) {
                    field.name = field.name.replace(/certificate_reports(?:_file)?\[\d+\]/, function(match) {
                        return match.startsWith('certificate_reports_file') 
                            ? `certificate_reports_file[${index}]` 
                            : `certificate_reports[${index}]`;
                    });
                }
            });
        });
    }

    btnAddCertificateReport?.addEventListener('click', function () {
        if (!certificateReportsList) return;
        if (noCertificateReportsNotice) noCertificateReportsNotice.style.display = 'none';
        const currentCount = certificateReportsList.querySelectorAll('.certificate-report-row').length;
        certificateReportsList.insertAdjacentHTML('beforeend', certificateReportTemplate(currentCount));
        reindexCertificateReports();
    });

    certificateReportsList?.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-certificate-report-btn');
        if (!button) return;
        const row = button.closest('.certificate-report-row');
        row?.remove();
        reindexCertificateReports();
    });

    // Packaging Type & Sub-category sync
    const pkgInput = document.getElementById('packaging_type_input');
    const pkgSelect = document.getElementById('packaging_preset_select');
    const pkgPills = document.querySelectorAll('.packaging-pill');

    function highlightActivePill(val) {
        pkgPills.forEach(pill => {
            if (pill.dataset.val.toLowerCase() === (val || '').trim().toLowerCase()) {
                pill.classList.remove('bg-light', 'text-dark');
                pill.classList.add('bg-success', 'text-white');
            } else {
                pill.classList.remove('bg-success', 'text-white');
                if (!pill.classList.contains('bg-light')) pill.classList.add('bg-light', 'text-dark');
            }
        });
    }

    if (pkgInput && pkgSelect) {
        if (pkgInput.value) {
            let matched = false;
            for (let i = 0; i < pkgSelect.options.length; i++) {
                const opt = pkgSelect.options[i];
                if (opt.value && opt.value !== '__custom__' && opt.value.toLowerCase() === pkgInput.value.trim().toLowerCase()) {
                    pkgSelect.value = opt.value;
                    matched = true;
                    break;
                }
            }
            if (!matched && pkgInput.value.trim() !== '') {
                pkgSelect.value = '__custom__';
            }
            highlightActivePill(pkgInput.value);
        }

        pkgSelect.addEventListener('change', function () {
            if (this.value && this.value !== '__custom__') {
                pkgInput.value = this.value;
                highlightActivePill(this.value);
            } else if (this.value === '__custom__') {
                pkgInput.focus();
                highlightActivePill('');
            }
        });

        pkgPills.forEach(pill => {
            pill.addEventListener('click', function () {
                const val = this.dataset.val;
                if (val) {
                    pkgInput.value = val;
                    pkgSelect.value = val;
                    highlightActivePill(val);
                }
            });
        });

        pkgInput.addEventListener('input', function () {
            highlightActivePill(this.value);
        });
    }

    updateGalleryLimitMessage(selectedGalleryFiles.length);
});
</script>
