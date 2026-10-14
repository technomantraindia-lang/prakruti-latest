@extends('admin.layouts.app')
@section('title', 'GRN #' . $grn->grn_number)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.inward.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left me-1"></i> Back to Inward List</a>
        <h2 class="mb-0 font-monospace">GRN: {{ $grn->grn_number }}</h2>
        <div class="text-muted">
            Received on: <strong>{{ $grn->received_date->format('d M, Y') }}</strong> | 
            Status: <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Verified & Inwarded</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.inward.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Inward Entry</a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i> Print GRN</button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Supplier Card -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 text-muted fw-bold"><i class="fas fa-truck me-2 text-primary"></i> SUPPLIER / VENDOR</h6>
            </div>
            <div class="card-body">
                <h5 class="fw-bold mb-1">{{ $grn->vendor?->display_name }}</h5>
                @if($grn->vendor?->gstin)
                    <div class="small text-muted font-monospace mb-2">GSTIN: {{ $grn->vendor->gstin }}</div>
                @endif
                <div class="small text-muted">
                    @if($grn->vendor?->phone)<div><i class="fas fa-phone me-1"></i>{{ $grn->vendor->phone }}</div>@endif
                    @if($grn->vendor?->email)<div><i class="fas fa-envelope me-1"></i>{{ $grn->vendor->email }}</div>@endif
                    <div>{{ $grn->vendor?->address }}, {{ $grn->vendor?->city }} {{ $grn->vendor?->state }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Reference Card -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 text-muted fw-bold"><i class="fas fa-receipt me-2 text-info"></i> INVOICE & RECEIVING</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Vendor Invoice No:</span>
                        <strong class="font-monospace">{{ $grn->vendor_invoice_no ?: '-' }}</strong>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Invoice Date:</span>
                        <span>{{ $grn->invoice_date ? $grn->invoice_date->format('d M, Y') : '-' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Verified & Received By:</span>
                        <span>{{ $grn->receiver?->name ?? 'Store Admin' }}</span>
                    </li>
                    @if($grn->notes)
                    <li class="mt-2 pt-2 border-top">
                        <span class="text-muted small d-block">Notes:</span>
                        <span class="small">{{ $grn->notes }}</span>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <!-- Financial & Landed Valuation Card -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100 bg-light">
            <div class="card-header bg-transparent py-3">
                <h6 class="card-title mb-0 text-muted fw-bold"><i class="fas fa-calculator me-2 text-success"></i> INVENTORY LANDED VALUATION</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Items Taxable Value:</span>
                    <span>₹{{ number_format($grn->total_items_taxable, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Total GST Tax:</span>
                    <span>₹{{ number_format($grn->total_tax_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Inward Freight / Cartage:</span>
                    <span class="text-primary">+₹{{ number_format($grn->inward_freight_charge, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Handling / Unloading:</span>
                    <span class="text-primary">+₹{{ number_format($grn->inward_handling_charge, 2) }}</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fs-5">
                    <span class="fw-bold">Total Landed Cost:</span>
                    <strong class="text-success">₹{{ number_format($grn->total_landed_cost, 2) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Received Items & Batches Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0"><i class="fas fa-boxes me-2 text-success"></i> Received Line Items & Batch Breakdown</h5>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Batch Number</th>
                    <th>Mfg Date</th>
                    <th>Expiry Date</th>
                    <th class="text-center">Accepted / Recv</th>
                    <th>Purchase Rate</th>
                    <th>Allocated Landed Expense</th>
                    <th>Effective Landed Cost/Unit</th>
                    <th>GST Slab</th>
                    <th class="text-end">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grn->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product?->name }}</strong>
                        <div class="small text-muted">SKU: {{ $item->product?->sku }}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border font-monospace fs-6">{{ $item->batch_number }}</span>
                    </td>
                    <td>{{ $item->mfg_date ? $item->mfg_date->format('d M, Y') : '-' }}</td>
                    <td>
                        @if($item->expiry_date)
                            @php $daysLeft = (int) now()->startOfDay()->diffInDays($item->expiry_date->startOfDay(), false); @endphp
                            <span>{{ $item->expiry_date->format('d M, Y') }}</span>
                            @if($daysLeft <= 60 && $daysLeft >= 0)
                                <span class="badge bg-warning text-dark ms-1">Near Expiry ({{ $daysLeft }}d)</span>
                            @elseif($daysLeft < 0)
                                <span class="badge bg-danger ms-1">Expired</span>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success fs-6">{{ $item->accepted_qty }} units</span>
                        @if($item->rejected_qty > 0)
                            <div class="small text-danger">{{ $item->rejected_qty }} rejected</div>
                        @endif
                    </td>
                    <td>₹{{ number_format($item->unit_purchase_price, 2) }}</td>
                    <td>+₹{{ number_format($item->allocated_landed_cost, 2) }}</td>
                    <td>
                        <strong class="text-primary fs-6">₹{{ number_format($item->effective_unit_cost, 2) }}</strong>
                        <div class="small text-muted">per unit</div>
                    </td>
                    <td>{{ $item->tax_rate }}%</td>
                    <td class="text-end">
                        <strong>₹{{ number_format($item->total_amount, 2) }}</strong>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Generated Purchase Bill (Accounts Payable) -->
@if($grn->purchaseBill)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-file-invoice-dollar me-2 text-warning"></i> Associated Purchase Bill (Accounts Payable)</h5>
        <span class="badge {{ $grn->purchaseBill->payment_status === 'paid' ? 'bg-success' : 'bg-danger' }}">
            {{ ucfirst($grn->purchaseBill->payment_status) }}
        </span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <span class="text-muted d-block small">Bill Number:</span>
                <strong class="font-monospace fs-6">{{ $grn->purchaseBill->bill_number }}</strong>
            </div>
            <div class="col-md-3">
                <span class="text-muted d-block small">Due Date:</span>
                <strong>{{ $grn->purchaseBill->due_date ? $grn->purchaseBill->due_date->format('d M, Y') : '-' }}</strong>
            </div>
            <div class="col-md-2">
                <span class="text-muted d-block small">CGST + SGST:</span>
                <strong>₹{{ number_format($grn->purchaseBill->cgst + $grn->purchaseBill->sgst, 2) }}</strong>
            </div>
            <div class="col-md-2">
                <span class="text-muted d-block small">IGST:</span>
                <strong>₹{{ number_format($grn->purchaseBill->igst, 2) }}</strong>
            </div>
            <div class="col-md-2 text-end">
                <span class="text-muted d-block small">Payable Amount:</span>
                <strong class="fs-5 text-dark">₹{{ number_format($grn->purchaseBill->total_amount, 2) }}</strong>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
