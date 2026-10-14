@extends('admin.layouts.app')
@section('title', 'Inward Stock (Goods Received Notes)')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="fas fa-boxes-packing me-2 text-success"></i> Inward Stock Management (GRN)</h2>
        <p class="text-muted mb-0">Record incoming stock from vendors, create batches with expiry dates, calculate landed costs, and update live store inventory.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.inventory.batches') }}" class="btn btn-outline-info"><i class="fas fa-clock-rotate-left me-1"></i> Batches & Expiry</a>
        <a href="{{ route('admin.inward.create') }}" class="btn btn-success"><i class="fas fa-plus me-1"></i> New Inward Entry</a>
    </div>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by GRN #, Invoice #, or Vendor..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="vendor_id" class="form-select">
                    <option value="">All Vendors</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="Received from date">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-3"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="{{ route('admin.inward.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>GRN Number</th>
                    <th>Vendor</th>
                    <th>Vendor Invoice</th>
                    <th>Received Date</th>
                    <th class="text-center">Accepted Items</th>
                    <th>Taxable Value</th>
                    <th>GST Amount</th>
                    <th>Freight / Landed</th>
                    <th>Total Landed Cost</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grns as $grn)
                <tr>
                    <td>
                        <a href="{{ route('admin.inward.show', $grn) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                            {{ $grn->grn_number }}
                        </a>
                        @if($grn->receiver)
                            <div class="small text-muted">By: {{ $grn->receiver->name }}</div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $grn->vendor?->display_name }}</strong>
                        @if($grn->vendor?->gstin)
                            <div class="small text-muted font-monospace">{{ $grn->vendor->gstin }}</div>
                        @endif
                    </td>
                    <td>
                        @if($grn->vendor_invoice_no)
                            <span class="badge bg-light text-dark border font-monospace">{{ $grn->vendor_invoice_no }}</span>
                            @if($grn->invoice_date)<div class="small text-muted">{{ $grn->invoice_date->format('d M, Y') }}</div>@endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $grn->received_date->format('d M, Y') }}</td>
                    <td class="text-center">
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">
                            {{ $grn->items->sum('accepted_qty') }} units
                        </span>
                        <div class="small text-muted">{{ $grn->items->count() }} line item(s)</div>
                    </td>
                    <td>₹{{ number_format($grn->total_items_taxable, 2) }}</td>
                    <td>₹{{ number_format($grn->total_tax_amount, 2) }}</td>
                    <td>
                        @php $expenses = $grn->inward_freight_charge + $grn->inward_handling_charge; @endphp
                        @if($expenses > 0)
                            <span class="text-primary">+₹{{ number_format($expenses, 2) }}</span>
                        @else
                            <span class="text-muted">₹0</span>
                        @endif
                    </td>
                    <td>
                        <strong class="text-success fs-6">₹{{ number_format($grn->total_landed_cost, 2) }}</strong>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Stock Verified</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.inward.show', $grn) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i> View Details
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-5 text-muted">
                        <i class="fas fa-boxes-packing fa-3x mb-3 d-block text-secondary opacity-50"></i>
                        No stock inward records found. Click <strong>"New Inward Entry"</strong> above to record incoming stock.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($grns->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $grns->links() }}
    </div>
    @endif
</div>
@endsection
