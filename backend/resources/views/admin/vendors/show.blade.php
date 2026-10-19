@extends('admin.layouts.app')
@section('title', $vendor->display_name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left me-1"></i> Back to Vendors</a>
        <h2 class="mb-0">{{ $vendor->display_name }}</h2>
        <div class="text-muted">
            @if($vendor->gstin)<span class="badge bg-light text-dark border me-2 font-monospace">GSTIN: {{ $vendor->gstin }}</span>@endif
            @if($vendor->pan)<span class="badge bg-light text-dark border me-2 font-monospace">PAN: {{ $vendor->pan }}</span>@endif
            <span class="badge {{ $vendor->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.inward.create', ['vendor_id' => $vendor->id]) }}" class="btn btn-success"><i class="fas fa-boxes-packing me-1"></i> Inward Stock (GRN)</a>
        <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn btn-outline-primary"><i class="fas fa-edit me-1"></i> Edit Details</a>
        <form action="{{ route('admin.vendors.destroy', $vendor) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete vendor \'{{ addslashes($vendor->display_name) }}\'? This action cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Supplier Profile & Bank Details -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0"><i class="fas fa-id-card me-2 text-primary"></i> Supplier Profile</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Contact Person:</span>
                        <span class="fw-bold">{{ $vendor->name }}</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Phone:</span>
                        <span>{{ $vendor->phone ?: '-' }}</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Email:</span>
                        <span>{{ $vendor->email ?: '-' }}</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Credit Terms:</span>
                        <span class="badge bg-info text-dark">Net {{ $vendor->payment_terms_days }} Days</span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1">Address:</span>
                        <span class="small">{{ $vendor->address ?: '-' }}<br>{{ $vendor->city }} {{ $vendor->state }} {{ $vendor->pincode }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0"><i class="fas fa-university me-2 text-warning"></i> Bank Details</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Bank Name:</span>
                        <span class="fw-bold">{{ $vendor->bank_name ?: '-' }}</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Account No:</span>
                        <span class="font-monospace">{{ $vendor->bank_account_no ?: '-' }}</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">IFSC Code:</span>
                        <span class="font-monospace">{{ $vendor->bank_ifsc ?: '-' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Product Matrix & Stock Inwards -->
    <div class="col-lg-8">
        <!-- Product Mapping Matrix -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-boxes me-2 text-success"></i> Supplied Products & Pricing Matrix</h5>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#mapProductForm">
                    <i class="fas fa-plus me-1"></i> Map Product
                </button>
            </div>

            <!-- Inline Mapping Form -->
            <div class="collapse border-bottom bg-light p-3" id="mapProductForm">
                <form action="{{ route('admin.vendors.products.store', $vendor) }}" method="POST">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Select Product</label>
                            <select name="product_id" class="form-select form-select-sm" required>
                                <option value="">Choose product...</option>
                                @foreach($allProducts as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Purchase Rate (₹)</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Tax Slab (%)</label>
                            <select name="tax_rate" class="form-select form-select-sm">
                                <option value="0">0% (Nil)</option>
                                <option value="5">5%</option>
                                <option value="12">12%</option>
                                <option value="18">18%</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">MOQ</label>
                            <input type="number" name="moq" class="form-control form-control-sm" value="1" min="1">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <div class="form-check form-check-inline small mb-2">
                                <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="isPrimaryCheck" checked>
                                <label class="form-check-label small" for="isPrimaryCheck">Primary</label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary flex-fill">Map</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Purchase Cost</th>
                            <th>GST Slab</th>
                            <th>MOQ</th>
                            <th>Primary Supplier</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendor->vendorProducts as $vp)
                        <tr>
                            <td>
                                <strong>{{ $vp->product?->name }}</strong>
                                <div class="small text-muted">SKU: {{ $vp->product?->sku }} | Retail MRP: ₹{{ number_format($vp->product?->display_price, 2) }}</div>
                            </td>
                            <td>
                                <strong class="text-success">₹{{ number_format($vp->purchase_price, 2) }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $vp->tax_rate }}%</span>
                            </td>
                            <td>{{ $vp->moq }} units</td>
                            <td>
                                @if($vp->is_primary)
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Primary</span>
                                @else
                                    <span class="badge bg-secondary">Secondary</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.vendors.products.destroy', [$vendor, $vp]) }}" method="POST" onsubmit="return confirm('Remove this product mapping?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Unlink product"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No products mapped yet. Click <strong>"Map Product"</strong> above to assign products and purchasing rates.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Inward Stock (GRN) -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-boxes-packing me-2 text-primary"></i> Inward Stock Received (GRNs)</h5>
                <a href="{{ route('admin.inward.create', ['vendor_id' => $vendor->id]) }}" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-plus me-1"></i> Inward New Stock
                </a>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>GRN #</th>
                            <th>Date</th>
                            <th>Vendor Invoice</th>
                            <th>Items Received</th>
                            <th>Total Landed Cost</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendor->goodsReceivedNotes as $grn)
                        <tr>
                            <td>
                                <a href="{{ route('admin.inward.show', $grn) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                    {{ $grn->grn_number }}
                                </a>
                            </td>
                            <td>{{ $grn->received_date->format('d M, Y') }}</td>
                            <td>{{ $grn->vendor_invoice_no ?: '-' }}</td>
                            <td>{{ $grn->items->sum('accepted_qty') }} units ({{ $grn->items->count() }} items)</td>
                            <td><strong>₹{{ number_format($grn->total_landed_cost, 2) }}</strong></td>
                            <td class="text-end">
                                <a href="{{ route('admin.inward.show', $grn) }}" class="btn btn-sm btn-outline-info">View Details</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No stock has been received from this supplier yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
