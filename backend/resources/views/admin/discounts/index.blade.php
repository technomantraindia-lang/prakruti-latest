@extends('admin.layouts.app')

@section('title', 'Discounts & Delivery')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="fas fa-tags text-success me-2"></i>Discounts &amp; Delivery</h2>
        <p class="text-muted mb-0">Control free delivery and automatic bill discounts from one page.</p>
    </div>
    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-key me-1"></i> Change Password
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(isset($errors) && $errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ route('admin.discounts.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-1"><i class="fas fa-truck text-success me-2"></i>Free Delivery Rule</h5>
                    <small class="text-muted">Example: set ₹1,000 to make delivery free for orders of ₹1,000 and above.</small>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Free delivery above order value (₹)</label>
                        <input type="number" name="free_shipping_min_amount" class="form-control form-control-lg" min="0" step="0.01" value="{{ old('free_shipping_min_amount', $settings['free_shipping_min_amount'] ?? 1000) }}" required>
                        <div class="form-text">This amount is changeable anytime. Set 0 to disable the threshold.</div>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Delivery charge below the threshold (₹)</label>
                        <input type="number" name="standard_shipping_charge" class="form-control" min="0" step="0.01" value="{{ old('standard_shipping_charge', $settings['standard_shipping_charge'] ?? 50) }}" required>
                        <div class="form-text">This charge applies only when the order is below the free-delivery amount.</div>
                    </div>
                    <div class="alert alert-success mt-4 mb-0 small">
                        <i class="fas fa-circle-check me-1"></i>
                        Current rule: orders of <strong>₹{{ number_format((float) old('free_shipping_min_amount', $settings['free_shipping_min_amount'] ?? 1000), 2) }}</strong> or more get free delivery.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="fas fa-percent text-success me-2"></i>Automatic Bill Discount</h5>
                        <small class="text-muted">The highest matching slab is applied at checkout.</small>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="bill_discount_enabled" value="1" class="form-check-input" id="billDiscountEnabled" @checked(old('bill_discount_enabled', ($settings['bill_discount_enabled'] ?? '0') === '1'))>
                        <label class="form-check-label small fw-semibold" for="billDiscountEnabled">Enable</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle" id="discountRulesTable">
                            <thead>
                                <tr>
                                    <th>Minimum order value (₹)</th>
                                    <th>Discount (%)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rules as $rule)
                                    <tr class="discount-rule-row">
                                        <td><input type="number" name="bill_discount_min_amount[]" class="form-control" min="0" step="0.01" value="{{ old('bill_discount_min_amount.' . $loop->index, $rule['min_amount'] ?? '') }}" placeholder="1000"></td>
                                        <td><input type="number" name="bill_discount_percent[]" class="form-control" min="0" max="100" step="0.01" value="{{ old('bill_discount_percent.' . $loop->index, $rule['percent'] ?? '') }}" placeholder="5"></td>
                                        <td><button type="button" class="btn btn-outline-danger remove-discount-rule" aria-label="Remove discount rule"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-success btn-sm" id="addDiscountRule"><i class="fas fa-plus me-1"></i>Add discount slab</button>
                    <p class="text-muted small mb-0 mt-3">Example: ₹1,000 = 3%, ₹2,500 = 5%, ₹5,000 = 8%.</p>
                </div>
            </div>
        </div>

        <!-- Product Promotional Offers & BOGO Deals -->
        <div class="col-12 mt-2">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1"><i class="fas fa-gift text-success me-2"></i>Product Promotional Offers &amp; BOGO Deals</h5>
                        <small class="text-muted">Create 20% discount offers on specific products, or Buy 1 Get 2 / Buy 1 Get 1 Free deals.</small>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="product_offers_enabled" value="1" class="form-check-input" id="productOffersEnabled" @checked(old('product_offers_enabled', ($settings['product_offers_enabled'] ?? '1') === '1'))>
                        <label class="form-check-label small fw-semibold" for="productOffersEnabled">Enable Product Offers</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border d-flex align-items-center gap-3 py-2 mb-3">
                        <i class="fas fa-lightbulb text-warning fa-lg"></i>
                        <div class="small">
                            <strong>How offers work:</strong>
                            <span class="ms-1"><strong>1. Product 20% Off:</strong> Select product &rarr; Choose <em>Percentage Discount</em> &rarr; enter <code>20</code>.</span>
                            <span class="ms-2"><strong>2. Buy 1 Get 2:</strong> Select product &rarr; Choose <em>Buy X Get Y (BOGO)</em> &rarr; enter Buy <code>1</code> Get <code>2</code> Free.</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle" id="productOffersTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 170px;">Offer Title / Campaign</th>
                                    <th style="min-width: 220px;">Target Product</th>
                                    <th style="min-width: 180px;">Offer Type</th>
                                    <th style="min-width: 230px;">Offer Value / Condition</th>
                                    <th style="min-width: 140px;">Badge / Label</th>
                                    <th style="min-width: 80px;" class="text-center">Active</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="productOffersBody">
                                @forelse($productOffers as $index => $offer)
                                    <tr class="product-offer-row" data-index="{{ $index }}">
                                        <input type="hidden" name="product_offers[{{ $index }}][id]" value="{{ $offer['id'] ?? uniqid('offer_') }}">
                                        <td>
                                            <input type="text" name="product_offers[{{ $index }}][title]" class="form-control form-control-sm" value="{{ $offer['title'] ?? '' }}" placeholder="e.g. 20% Special Discount">
                                        </td>
                                        <td>
                                            <select name="product_offers[{{ $index }}][product_id]" class="form-select form-select-sm" required>
                                                <option value="">-- Choose Product --</option>
                                                <option value="all" @selected(($offer['product_id'] ?? '') === 'all')>⭐ All Products (Storewide)</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" @selected((string)($offer['product_id'] ?? '') === (string)$p->id)>
                                                        {{ $p->name }} ({{ $p->sku ?: 'ID: '.$p->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="product_offers[{{ $index }}][offer_type]" class="form-select form-select-sm offer-type-select">
                                                <option value="percentage" @selected(($offer['offer_type'] ?? '') === 'percentage')>Percentage Discount (%)</option>
                                                <option value="flat" @selected(($offer['offer_type'] ?? '') === 'flat')>Flat Amount Off (₹)</option>
                                                <option value="bogo" @selected(($offer['offer_type'] ?? '') === 'bogo')>Buy X Get Y (BOGO)</option>
                                            </select>
                                        </td>
                                        <td>
                                            <!-- Percentage / Flat input -->
                                            <div class="offer-val-discount" style="{{ ($offer['offer_type'] ?? 'percentage') === 'bogo' ? 'display:none;' : '' }}">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text discount-val-prefix">{{ ($offer['offer_type'] ?? '') === 'flat' ? '₹' : '%' }}</span>
                                                    <input type="number" step="0.01" min="0" name="product_offers[{{ $index }}][discount_val]" class="form-control" value="{{ $offer['discount_val'] ?? 20 }}" placeholder="e.g. 20">
                                                </div>
                                            </div>
                                            <!-- BOGO input -->
                                            <div class="offer-val-bogo" style="{{ ($offer['offer_type'] ?? '') === 'bogo' ? '' : 'display:none;' }}">
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="small text-muted">Buy</span>
                                                    <input type="number" min="1" name="product_offers[{{ $index }}][buy_qty]" class="form-control form-control-sm" style="width: 65px;" value="{{ $offer['buy_qty'] ?? 1 }}">
                                                    <span class="small text-muted">Get</span>
                                                    <input type="number" min="1" name="product_offers[{{ $index }}][get_qty]" class="form-control form-control-sm" style="width: 65px;" value="{{ $offer['get_qty'] ?? 2 }}">
                                                    <span class="small text-muted">Free</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="product_offers[{{ $index }}][badge_text]" class="form-control form-control-sm" value="{{ $offer['badge_text'] ?? '' }}" placeholder="e.g. 20% OFF">
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" name="product_offers[{{ $index }}][is_active]" value="1" class="form-check-input" @checked(!empty($offer['is_active']))>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-offer-btn" title="Remove Offer"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="noOffersRow">
                                        <td colspan="7" class="text-center text-muted py-3">
                                            No product promotional offers created yet. Click <strong>"+ Add Product Offer"</strong> below to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-success btn-sm mt-2" id="addOfferBtn">
                        <i class="fas fa-plus me-1"></i> Add Product Offer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-success btn-lg px-4"><i class="fas fa-save me-2"></i>Save All Settings &amp; Offers</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('#discountRulesTable tbody');
    const addButton = document.getElementById('addDiscountRule');

    function bindRemoveButtons() {
        document.querySelectorAll('.remove-discount-rule').forEach(function (button) {
            button.onclick = function () {
                const rows = tableBody.querySelectorAll('.discount-rule-row');
                if (rows.length > 1) {
                    button.closest('tr').remove();
                } else {
                    button.closest('tr').querySelectorAll('input').forEach(function (input) { input.value = ''; });
                }
            };
        });
    }

    addButton.addEventListener('click', function () {
        const row = document.createElement('tr');
        row.className = 'discount-rule-row';
        row.innerHTML = '<td><input type="number" name="bill_discount_min_amount[]" class="form-control" min="0" step="0.01" placeholder="1000"></td>'
            + '<td><input type="number" name="bill_discount_percent[]" class="form-control" min="0" max="100" step="0.01" placeholder="5"></td>'
            + '<td><button type="button" class="btn btn-outline-danger remove-discount-rule" aria-label="Remove discount rule"><i class="fas fa-trash"></i></button></td>';
        tableBody.appendChild(row);
        bindRemoveButtons();
    });

    bindRemoveButtons();

    // Product Offers logic
    const productOptionsHtml = `@foreach($products as $p)<option value="{{ $p->id }}">{{ addslashes($p->name) }} ({{ $p->sku ?: 'ID: '.$p->id }})</option>@endforeach`;
    const offersBody = document.getElementById('productOffersBody');
    const addOfferBtn = document.getElementById('addOfferBtn');
    const noOffersRow = document.getElementById('noOffersRow');

    function bindOfferRowEvents(row) {
        const typeSelect = row.querySelector('.offer-type-select');
        const discountValBox = row.querySelector('.offer-val-discount');
        const bogoBox = row.querySelector('.offer-val-bogo');
        const prefix = row.querySelector('.discount-val-prefix');
        const removeBtn = row.querySelector('.remove-offer-btn');

        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                if (this.value === 'bogo') {
                    if (discountValBox) discountValBox.style.display = 'none';
                    if (bogoBox) bogoBox.style.display = 'block';
                } else {
                    if (bogoBox) bogoBox.style.display = 'none';
                    if (discountValBox) discountValBox.style.display = 'block';
                    if (prefix) prefix.textContent = (this.value === 'flat') ? '₹' : '%';
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                row.remove();
                if (offersBody && offersBody.querySelectorAll('.product-offer-row').length === 0 && noOffersRow) {
                    noOffersRow.style.display = '';
                }
            });
        }
    }

    document.querySelectorAll('.product-offer-row').forEach(bindOfferRowEvents);

    if (addOfferBtn && offersBody) {
        addOfferBtn.addEventListener('click', function () {
            if (noOffersRow) noOffersRow.style.display = 'none';
            const index = 'new_' + Date.now();
            const tr = document.createElement('tr');
            tr.className = 'product-offer-row';
            tr.dataset.index = index;
            tr.innerHTML = `
                <input type="hidden" name="product_offers[${index}][id]" value="offer_${Date.now()}">
                <td>
                    <input type="text" name="product_offers[${index}][title]" class="form-control form-control-sm" placeholder="e.g. 20% Special Discount">
                </td>
                <td>
                    <select name="product_offers[${index}][product_id]" class="form-select form-select-sm" required>
                        <option value="">-- Choose Product --</option>
                        <option value="all">⭐ All Products (Storewide)</option>
                        ${productOptionsHtml}
                    </select>
                </td>
                <td>
                    <select name="product_offers[${index}][offer_type]" class="form-select form-select-sm offer-type-select">
                        <option value="percentage" selected>Percentage Discount (%)</option>
                        <option value="flat">Flat Amount Off (₹)</option>
                        <option value="bogo">Buy X Get Y (BOGO)</option>
                    </select>
                </td>
                <td>
                    <div class="offer-val-discount">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text discount-val-prefix">%</span>
                            <input type="number" step="0.01" min="0" name="product_offers[${index}][discount_val]" class="form-control" value="20" placeholder="e.g. 20">
                        </div>
                    </div>
                    <div class="offer-val-bogo" style="display:none;">
                        <div class="d-flex align-items-center gap-1">
                            <span class="small text-muted">Buy</span>
                            <input type="number" min="1" name="product_offers[${index}][buy_qty]" class="form-control form-control-sm" style="width: 65px;" value="1">
                            <span class="small text-muted">Get</span>
                            <input type="number" min="1" name="product_offers[${index}][get_qty]" class="form-control form-control-sm" style="width: 65px;" value="2">
                            <span class="small text-muted">Free</span>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="text" name="product_offers[${index}][badge_text]" class="form-control form-control-sm" placeholder="e.g. 20% OFF">
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input type="checkbox" name="product_offers[${index}][is_active]" value="1" class="form-check-input" checked>
                    </div>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-offer-btn" title="Remove Offer"><i class="fas fa-trash"></i></button>
                </td>
            `;
            offersBody.appendChild(tr);
            bindOfferRowEvents(tr);
        });
    }
});
</script>
@endsection
