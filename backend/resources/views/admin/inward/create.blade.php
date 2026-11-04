@extends('admin.layouts.app')
@section('title', 'New Inward Stock (GRN)')
@push('styles')
<style>
    .inward-line-card { border: 1px solid #dce5df; border-left: 4px solid #2d7a46; border-radius: 12px; background: #fff; box-shadow: 0 2px 8px rgba(33, 65, 45, .06); }
    .inward-line-header { background: #f5faf6; border-bottom: 1px solid #e1ebe4; border-radius: 9px 9px 0 0; }
    .inward-line-card .form-label { color: #33443a; font-size: .82rem; margin-bottom: .35rem; }
    .inward-line-card .form-control, .inward-line-card .form-select { min-height: 42px; }
    .line-total-box { min-height: 42px; display: flex; align-items: center; justify-content: flex-end; padding: .5rem .75rem; border: 1px solid #dbe7dd; border-radius: .375rem; background: #f7fbf8; }
    .inward-help { border: 1px dashed #a9cdb2; background: #f2faf4; color: #356344; }
    #itemsTable { border: 0; }
    #itemsTable thead { display: none; }
    #itemsTable tbody { display: flex; flex-direction: column; gap: 1rem; }
    #itemsTable tr.item-row { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 1rem; padding: 1rem; border: 1px solid #dce5df; border-left: 4px solid #2d7a46; border-radius: 12px; background: #fff; box-shadow: 0 2px 8px rgba(33, 65, 45, .06); }
    #itemsTable tr.item-row::before { content: 'Stock line'; grid-column: 1 / -1; color: #2d7a46; font-size: .9rem; font-weight: 700; border-bottom: 1px solid #e1ebe4; padding-bottom: .65rem; }
    #itemsTable td { border: 0; padding: 0; display: flex; flex-direction: column; justify-content: center; min-width: 0; }
    #itemsTable td::before { color: #33443a; font-size: .78rem; font-weight: 700; margin-bottom: .35rem; }
    #itemsTable td:nth-child(1) { grid-column: span 4; } #itemsTable td:nth-child(1)::before { content: 'Product *'; }
    #itemsTable td:nth-child(2) { grid-column: span 4; } #itemsTable td:nth-child(2)::before { content: 'Packaging / Size'; }
    #itemsTable td:nth-child(3) { grid-column: span 4; } #itemsTable td:nth-child(3)::before { content: 'Batch Number *'; }
    #itemsTable td:nth-child(4) { grid-column: span 3; } #itemsTable td:nth-child(4)::before { content: 'Manufacturing Date'; }
    #itemsTable td:nth-child(5) { grid-column: span 3; } #itemsTable td:nth-child(5)::before { content: 'Expiry Date'; }
    #itemsTable td:nth-child(6) { grid-column: span 2; } #itemsTable td:nth-child(6)::before { content: 'Received Qty *'; }
    #itemsTable td:nth-child(7) { grid-column: span 2; } #itemsTable td:nth-child(7)::before { content: 'Accepted Qty *'; }
    #itemsTable td:nth-child(8) { grid-column: span 3; } #itemsTable td:nth-child(8)::before { content: 'Purchase Rate (₹) *'; }
    #itemsTable td:nth-child(9) { grid-column: span 3; } #itemsTable td:nth-child(9)::before { content: 'MRP (₹) *'; }
    #itemsTable td:nth-child(10) { grid-column: span 2; } #itemsTable td:nth-child(10)::before { content: 'GST Rate'; }
    #itemsTable td:nth-child(11) { grid-column: span 3; } #itemsTable td:nth-child(11)::before { content: 'Line Total'; }
    #itemsTable td:nth-child(12) { grid-column: span 1; align-items: flex-end; justify-content: flex-end; } #itemsTable td:nth-child(12)::before { content: ''; }
    #itemsTable td:nth-child(11) .line-total { display: block; width: 100%; padding: .65rem .75rem; border: 1px solid #dbe7dd; border-radius: .375rem; background: #f7fbf8; text-align: right; }
    @media (max-width: 991.98px) { #itemsTable tr.item-row { grid-template-columns: repeat(6, minmax(0, 1fr)); } #itemsTable td:nth-child(1), #itemsTable td:nth-child(2), #itemsTable td:nth-child(3) { grid-column: span 6; } #itemsTable td:nth-child(4), #itemsTable td:nth-child(5) { grid-column: span 3; } #itemsTable td:nth-child(6), #itemsTable td:nth-child(7), #itemsTable td:nth-child(8), #itemsTable td:nth-child(9), #itemsTable td:nth-child(10), #itemsTable td:nth-child(11), #itemsTable td:nth-child(12) { grid-column: span 3; } #itemsTable td:nth-child(12) { align-items: stretch; } }
    @media (max-width: 575.98px) { #itemsTable tr.item-row { display: flex; flex-direction: column; } #itemsTable td, #itemsTable td:nth-child(n) { width: 100%; } #itemsTable td:nth-child(12) { align-items: stretch; } .inward-line-header { align-items: flex-start !important; } .inward-line-header .remove-row-btn { white-space: nowrap; } }
</style>
@endpush
@section('content')
<div class="mb-4">
    <a href="{{ route('admin.inward.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left me-1"></i> Back to Inward List</a>
    <h2 class="mb-1"><i class="fas fa-boxes-packing me-2 text-success"></i> New Inward Stock Entry (GRN)</h2>
    <p class="text-muted mb-0">Record physical stock received from a supplier. Inventory levels and batches will update automatically upon saving.</p>
</div>

<form action="{{ route('admin.inward.store') }}" method="POST" id="inwardForm">
    @csrf

    <!-- Vendor & Invoice Info Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0"><i class="fas fa-file-invoice me-2 text-primary"></i> Supplier & Invoice Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Select Vendor / Supplier <span class="text-danger">*</span></label>
                    <select name="vendor_id" id="vendorSelect" class="form-select @error('vendor_id') is-invalid @enderror" required>
                        <option value="">-- Choose Vendor --</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" @selected(old('vendor_id', $selectedVendorId) == $v->id) data-gstin="{{ $v->gstin }}" data-state="{{ $v->state }}">
                                {{ $v->display_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Vendor Invoice / Bill No</label>
                    <input type="text" name="vendor_invoice_no" class="form-control" value="{{ old('vendor_invoice_no') }}" placeholder="e.g. INV-9842">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Invoice Date</label>
                    <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Stock Received Date <span class="text-danger">*</span></label>
                    <input type="date" name="received_date" class="form-control" value="{{ old('received_date', date('Y-m-d')) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Inward Freight / Transport (₹)</label>
                    <input type="number" step="0.01" name="inward_freight_charge" id="freightInput" class="form-control" value="{{ old('inward_freight_charge', '0.00') }}" placeholder="0.00">
                    <small class="text-muted">Will be apportioned into landed cost per unit.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Handling / Unloading Charge (₹)</label>
                    <input type="number" step="0.01" name="inward_handling_charge" id="handlingInput" class="form-control" value="{{ old('inward_handling_charge', '0.00') }}" placeholder="0.00">
                    <small class="text-muted">Included in total inventory valuation.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Payment Status <span class="text-danger">*</span></label>
                    <select name="payment_status" id="paymentStatusSelect" class="form-select fw-semibold" required>
                        <option value="unpaid" @selected(old('payment_status', 'unpaid') == 'unpaid')>
                            🔴 Not Paid (Unpaid)
                        </option>
                        <option value="paid" @selected(old('payment_status') == 'paid')>
                            🟢 Paid (Payment Settled)
                        </option>
                    </select>
                    <small class="text-muted">Will reflect on GRN &amp; vendor bill.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Notes / Delivery Remarks</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="e.g. Received in good condition">
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items & Batch Breakdown Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-boxes me-2 text-success"></i> Stock Line Items, Batches & Expiry Dates</h5>
            <button type="button" class="btn btn-sm btn-outline-success" id="addRowBtn">
                <i class="fas fa-plus me-1"></i> Add Package / Batch Line
            </button>
        </div>
        <div class="card-body">
            <div class="inward-help rounded-3 p-3 mb-3 small">
                <i class="fas fa-circle-info me-1"></i>
                Add one line for each product packaging and batch. For example, enter 250g, 500g, and 1kg as three separate lines so each package can have its own quantity, batch, purchase rate, MRP, and expiry date.
            </div>
            <table class="table table-bordered align-middle mb-0" id="itemsTable">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 220px;">Product <span class="text-danger">*</span></th>
                        <th style="min-width: 160px;">Packaging</th>
                        <th style="min-width: 140px;">Batch No <span class="text-danger">*</span></th>
                        <th style="min-width: 130px;">Mfg Date</th>
                        <th style="min-width: 130px;">Expiry Date</th>
                        <th style="width: 100px;">Recv Qty</th>
                        <th style="width: 100px;">Accept Qty <span class="text-danger">*</span></th>
                        <th style="width: 110px;">Purchase Rate (₹)</th>
                        <th style="width: 110px;">MRP (₹)</th>
                        <th style="width: 100px;">GST %</th>
                        <th style="width: 120px;" class="text-end">Line Total (₹)</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <!-- Default Initial Row -->
                    <tr class="item-row">
                        <td>
                            <select name="items[0][product_id]" class="form-select form-select-sm product-select" required>
                                <option value="">Select product...</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->name }} ({{ $p->sku }})</option>
                                @endforeach
                                <option value="__add_new_product__">+ Add New Product</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm package-select" multiple size="1" aria-label="Select one or more packaging sizes">
                                <option value="">Choose product first...</option>
                            </select>
                            <input type="hidden" name="items[0][product_variation_id]" class="package-id-input" value="">
                            <small class="text-muted package-help">Choose one or more sizes; use Ctrl/Command for multiple.</small>
                        </td>
                        <td>
                            <input type="text" name="items[0][batch_number]" class="form-control form-control-sm font-monospace batch-input" placeholder="e.g. B-{{ date('ymd') }}-01" value="B-{{ date('ymd') }}-01" required>
                        </td>
                        <td>
                            <input type="date" name="items[0][mfg_date]" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </td>
                        <td>
                            <input type="date" name="items[0][expiry_date]" class="form-control form-control-sm expiry-input" value="{{ date('Y-m-d', strtotime('+1 year')) }}">
                        </td>
                        <td>
                            <input type="number" name="items[0][received_qty]" class="form-control form-control-sm qty-recv" min="1" value="10" required>
                        </td>
                        <td>
                            <input type="number" name="items[0][accepted_qty]" class="form-control form-control-sm qty-accept" min="0" value="10" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="items[0][unit_purchase_price]" class="form-control form-control-sm unit-price" min="0" value="100.00" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="items[0][mrp]" class="form-control form-control-sm mrp-input" min="0" value="0.00" required>
                        </td>
                        <td>
                            <select name="items[0][tax_rate]" class="form-select form-select-sm tax-rate">
                                <option value="0">0%</option>
                                <option value="5" selected>5%</option>
                                <option value="12">12%</option>
                                <option value="18">18%</option>
                            </select>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold line-total">₹1,050.00</span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" disabled><i class="fas fa-times"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Box & Submission -->
    <div class="row justify-content-end">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4 bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Inward Summary & Landed Cost</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Taxable Value:</span>
                        <strong id="summaryTaxable">₹1,000.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total GST Tax:</span>
                        <strong id="summaryTax">₹50.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Freight & Handling Expenses:</span>
                        <strong id="summaryExpenses" class="text-primary">₹0.00</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5">
                        <span class="fw-bold">Total Landed Inventory Cost:</span>
                        <strong id="summaryLanded" class="text-success">₹1,050.00</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.inward.index') }}" class="btn btn-outline-secondary w-50 py-2">Cancel</a>
                <button type="submit" class="btn btn-success w-50 py-2 fw-bold" id="submitBtn">
                    <i class="fas fa-check-circle me-1"></i> Verify & Inward Stock
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = 1;
    const itemsBody = document.getElementById('itemsBody');
    const addRowBtn = document.getElementById('addRowBtn');
    const freightInput = document.getElementById('freightInput');
    const handlingInput = document.getElementById('handlingInput');
    const productCatalog = @json($productOptions);
    const addProductUrl = @json(route('admin.products.create'));

    function syncPackaging(row, updateMrp = true) {
        const productSelect = row.querySelector('.product-select');
        const packageSelect = row.querySelector('.package-select');
        const packageIdInput = row.querySelector('.package-id-input');
        const mrpInput = row.querySelector('.mrp-input');
        const productId = productSelect.value;

        if (productId === '__add_new_product__') {
            window.location.href = addProductUrl;
            return;
        }

        const product = productCatalog[productId];
        const variations = product?.variations || [];
        packageSelect.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = variations.length ? 'Select one or more packaging sizes...' : 'Default product package';
        packageSelect.appendChild(defaultOption);

        variations.forEach((variation) => {
            const option = document.createElement('option');
            option.value = variation.id;
            option.dataset.mrp = variation.mrp;
            option.textContent = `${variation.label || 'Package'}${variation.sku ? ` (${variation.sku})` : ''}`;
            packageSelect.appendChild(option);
        });

        packageSelect.disabled = variations.length === 0;
        packageSelect.multiple = variations.length > 0;
        packageSelect.size = variations.length > 0 ? Math.min(Math.max(variations.length + 1, 3), 5) : 1;
        packageSelect.required = false;
        if (packageIdInput) packageIdInput.value = '';

        if (updateMrp && mrpInput) {
            mrpInput.value = variations.length ? '0.00' : Number(product?.mrp ?? 0).toFixed(2);
        }
    }

    function setPackageSelection(row, variationId) {
        const packageSelect = row.querySelector('.package-select');
        const packageIdInput = row.querySelector('.package-id-input');
        const mrpInput = row.querySelector('.mrp-input');
        const selectedOption = Array.from(packageSelect.options).find((option) => String(option.value) === String(variationId));

        packageSelect.multiple = false;
        packageSelect.size = 1;
        packageSelect.value = selectedOption ? String(variationId) : '';
        if (packageIdInput) packageIdInput.value = selectedOption ? String(variationId) : '';
        if (mrpInput) mrpInput.value = Number(selectedOption?.dataset.mrp || 0).toFixed(2);
    }

    function splitPackageSelections(row) {
        const packageSelect = row.querySelector('.package-select');
        const selectedOptions = Array.from(packageSelect.selectedOptions).filter((option) => option.value);
        if (!selectedOptions.length) {
            row.querySelector('.package-id-input').value = '';
            row.querySelector('.mrp-input').value = '0.00';
            return;
        }

        const productId = row.querySelector('.product-select').value;
        setPackageSelection(row, selectedOptions[0].value);
        selectedOptions.slice(1).forEach((option) => addRow(productId, option.value));
        updateRemoveButtons();
    }

    function syncMrpFromPackaging(row) {
        const product = productCatalog[row.querySelector('.product-select').value];
        const packageSelect = row.querySelector('.package-select');
        const selectedOption = packageSelect.options[packageSelect.selectedIndex];
        const packageIdInput = row.querySelector('.package-id-input');
        const mrpInput = row.querySelector('.mrp-input');
        if (!mrpInput) return;

        if (packageIdInput) packageIdInput.value = selectedOption?.value || '';
        mrpInput.value = Number(selectedOption?.dataset.mrp ?? product?.mrp ?? 0).toFixed(2);
    }

    function updateRowTotals() {
        let totalTaxable = 0;
        let totalTax = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const acceptQty = parseFloat(row.querySelector('.qty-accept').value) || 0;
            const price = parseFloat(row.querySelector('.unit-price').value) || 0;
            const taxRate = parseFloat(row.querySelector('.tax-rate').value) || 0;

            const taxable = acceptQty * price;
            const tax = taxable * (taxRate / 100);
            const lineTotal = taxable + tax;

            row.querySelector('.line-total').textContent = '₹' + lineTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            totalTaxable += taxable;
            totalTax += tax;
        });

        const freight = parseFloat(freightInput.value) || 0;
        const handling = parseFloat(handlingInput.value) || 0;
        const totalExpenses = freight + handling;
        const totalLanded = totalTaxable + totalTax + totalExpenses;

        document.getElementById('summaryTaxable').textContent = '₹' + totalTaxable.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('summaryTax').textContent = '₹' + totalTax.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('summaryExpenses').textContent = '₹' + totalExpenses.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('summaryLanded').textContent = '₹' + totalLanded.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function addRow(initialProductId = '', initialVariationId = '') {
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-select form-select-sm product-select" required>
                    <option value="">Select product...</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->name }} ({{ $p->sku }})</option>
                    @endforeach
                    <option value="__add_new_product__">+ Add New Product</option>
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm package-select" multiple size="1" aria-label="Select one or more packaging sizes">
                    <option value="">Choose product first...</option>
                </select>
                <input type="hidden" name="items[${rowIndex}][product_variation_id]" class="package-id-input" value="">
                <small class="text-muted package-help">Choose one or more sizes; use Ctrl/Command for multiple.</small>
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][batch_number]" class="form-control form-control-sm font-monospace batch-input" placeholder="Batch No" value="B-{{ date('ymd') }}-0${rowIndex + 1}" required>
            </td>
            <td>
                <input type="date" name="items[${rowIndex}][mfg_date]" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
            </td>
            <td>
                <input type="date" name="items[${rowIndex}][expiry_date]" class="form-control form-control-sm expiry-input" value="{{ date('Y-m-d', strtotime('+1 year')) }}">
            </td>
            <td>
                <input type="number" name="items[${rowIndex}][received_qty]" class="form-control form-control-sm qty-recv" min="1" value="10" required>
            </td>
            <td>
                <input type="number" name="items[${rowIndex}][accepted_qty]" class="form-control form-control-sm qty-accept" min="0" value="10" required>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${rowIndex}][unit_purchase_price]" class="form-control form-control-sm unit-price" min="0" value="100.00" required>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${rowIndex}][mrp]" class="form-control form-control-sm mrp-input" min="0" value="0.00" required>
            </td>
            <td>
                <select name="items[${rowIndex}][tax_rate]" class="form-select form-select-sm tax-rate">
                    <option value="0">0%</option>
                    <option value="5" selected>5%</option>
                    <option value="12">12%</option>
                    <option value="18">18%</option>
                </select>
            </td>
            <td class="text-end">
                <span class="fw-bold line-total">₹0.00</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="fas fa-times"></i></button>
            </td>
        `;

        itemsBody.appendChild(tr);
        rowIndex++;
        attachRowEvents(tr);
        if (initialProductId) {
            tr.querySelector('.product-select').value = String(initialProductId);
            syncPackaging(tr);
            if (initialVariationId) setPackageSelection(tr, initialVariationId);
        }
        updateRowTotals();
        updateRemoveButtons();
    }

    addRowBtn.addEventListener('click', function () {
        addRow();
    });

    document.getElementById('inwardForm').addEventListener('submit', function (event) {
        const incompletePackage = Array.from(document.querySelectorAll('.item-row')).some((row) => {
            const packageSelect = row.querySelector('.package-select');
            const packageId = row.querySelector('.package-id-input')?.value;
            return !packageSelect.disabled && !packageId;
        });

        if (incompletePackage) {
            event.preventDefault();
            alert('Please select at least one packaging size for every product line.');
        }
    });

    function attachRowEvents(row) {
        row.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', updateRowTotals);
            input.addEventListener('change', updateRowTotals);
        });

        row.querySelector('.product-select').addEventListener('change', function () {
            syncPackaging(row);
        });
        row.querySelector('.package-select').addEventListener('change', function () {
            if (this.multiple) {
                splitPackageSelections(row);
            } else {
                syncMrpFromPackaging(row);
            }
        });

        const qtyRecv = row.querySelector('.qty-recv');
        const qtyAccept = row.querySelector('.qty-accept');
        qtyRecv.addEventListener('input', function() {
            qtyAccept.value = this.value;
            updateRowTotals();
        });

        const removeBtn = row.querySelector('.remove-row-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                row.remove();
                updateRowTotals();
                updateRemoveButtons();
            });
        }
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.item-row');
        rows.forEach(r => {
            const btn = r.querySelector('.remove-row-btn');
            if (btn) btn.disabled = (rows.length <= 1);
        });
    }

    document.querySelectorAll('.item-row').forEach((row) => {
        attachRowEvents(row);
        syncPackaging(row, false);
    });
    freightInput.addEventListener('input', updateRowTotals);
    handlingInput.addEventListener('input', updateRowTotals);
    updateRowTotals();
});
</script>
@endpush
@endsection
