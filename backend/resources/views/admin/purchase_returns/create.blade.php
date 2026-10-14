@extends('admin.layouts.app')

@section('title', 'Create Purchase Return (Return to Vendor)')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">New Purchase Return (RTV)</h1>
            <p class="text-muted small mb-0">Record returned stock to vendor and generate automated Vendor Debit Note for ITC reversal.</p>
        </div>
        <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Returns
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.purchase-returns.store') }}">
        @csrf
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Return Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Select Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" class="form-select" required>
                            <option value="">-- Choose Vendor --</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id', $selectedVendorId) == $v->id ? 'selected' : '' }}>
                                    {{ $v->name }} ({{ $v->company_name ?? 'GST: ' . $v->gstin }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Return Reason <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="Damaged in Transit">Damaged in Transit / Broken Seals</option>
                            <option value="Near Expiry / Quarantined Stock">Near Expiry / Quarantined Stock (Within 60 Days)</option>
                            <option value="Expired Stock">Expired Stock</option>
                            <option value="Quality Inspection Failed">Quality Inspection / Lab Test Failed</option>
                            <option value="Incorrect Specification Sent">Incorrect Specification / Formulation Sent</option>
                            <option value="Excess Supply">Excess Supply</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Returned Items</h6>
                <button type="button" class="btn btn-sm btn-outline-success" id="addRowBtn">
                    <i class="fas fa-plus me-1"></i> Add Another Item
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle" id="returnItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Product</th>
                                <th style="width: 25%;">Batch Number</th>
                                <th style="width: 12%;">Return Qty</th>
                                <th style="width: 15%;">Unit Cost (₹)</th>
                                <th style="width: 8%;">GST Rate (%)</th>
                                <th style="width: 5%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="items[0][product_id]" class="form-select product-select" required>
                                        <option value="">-- Select Product --</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-gst="{{ $p->gst_percentage ?? 12 }}">
                                                {{ $p->name }} (Stock: {{ $p->stock_qty }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[0][batch_number]" class="form-control" placeholder="e.g. BATCH-001 or select below" list="batchList">
                                    <datalist id="batchList">
                                        @foreach($batches as $b)
                                            <option value="{{ $b->batch_number }}">Product #{{ $b->product_id }} - Exp: {{ $b->expiry_date?->format('d/m/Y') }} (Qty: {{ $b->current_qty }}, Quarantined: {{ $b->quarantined_qty }})</option>
                                        @endforeach
                                    </datalist>
                                </td>
                                <td>
                                    <input type="number" name="items[0][return_qty]" class="form-control text-center" min="1" value="1" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="items[0][unit_cost]" class="form-control text-end" placeholder="100.00" value="100.00" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="items[0][tax_rate]" class="form-control text-center gst-input" value="12" required>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row" disabled>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-end py-3">
                <button type="submit" class="btn btn-danger btn-lg px-4">
                    <i class="fas fa-check-circle me-1"></i> Confirm Return & Issue Debit Note
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;
    const addRowBtn = document.getElementById('addRowBtn');
    const tableBody = document.querySelector('#returnItemsTable tbody');

    addRowBtn.addEventListener('click', function() {
        const firstRow = tableBody.querySelector('tr');
        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
            }
            if (input.tagName === 'INPUT' && input.type !== 'hidden') {
                if (input.classList.contains('gst-input')) {
                    input.value = '12';
                } else if (input.name.includes('return_qty')) {
                    input.value = '1';
                } else if (input.name.includes('unit_cost')) {
                    input.value = '100.00';
                } else {
                    input.value = '';
                }
            }
        });

        const removeBtn = newRow.querySelector('.remove-row');
        removeBtn.removeAttribute('disabled');
        removeBtn.addEventListener('click', function() {
            newRow.remove();
        });

        tableBody.appendChild(newRow);
        rowIndex++;
    });

    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('product-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const gst = selectedOpt.getAttribute('data-gst') || 12;
            const row = e.target.closest('tr');
            const gstInput = row.querySelector('.gst-input');
            if (gstInput) gstInput.value = gst;
        }
    });
});
</script>
@endsection
