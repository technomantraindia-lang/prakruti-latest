@extends('admin.layouts.app')
@section('title', 'Batch Inventory & Expiry Tracking')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="fas fa-clock-rotate-left me-2 text-primary"></i> Batch Inventory & Expiry Tracking</h2>
        <p class="text-muted mb-0">Track physical inventory batch numbers, manufacturing dates, and 60-day near-expiry quarantine items.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.inward.create') }}" class="btn btn-success"><i class="fas fa-plus me-1"></i> Inward New Batch</a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-primary border-4">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-bold">Total Batches</span>
                <h3 class="fw-bold mb-0 mt-1">{{ number_format($stats['total_batches']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-success border-4">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-bold">Active in Stock</span>
                <h3 class="fw-bold mb-0 mt-1 text-success">{{ number_format($stats['active_batches']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-warning border-4">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-bold">Near Expiry (≤ 60 Days)</span>
                <h3 class="fw-bold mb-0 mt-1 text-warning">{{ number_format($stats['near_expiry_count']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-danger border-4">
            <div class="card-body">
                <span class="text-muted small text-uppercase fw-bold">Expired Batches</span>
                <h3 class="fw-bold mb-0 mt-1 text-danger">{{ number_format($stats['expired_count']) }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by batch number, SKU, or product..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="near_expiry" @selected(request('status') === 'near_expiry')>Near Expiry</option>
                    <option value="quarantined" @selected(request('status') === 'quarantined')>Quarantined</option>
                    <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                    <option value="depleted" @selected(request('status') === 'depleted')>Depleted (0 Stock)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <div class="form-check form-check-inline mt-2">
                    <input class="form-check-input" type="checkbox" name="near_expiry_only" id="nearExpiryCheck" value="1" @checked(request('near_expiry_only'))>
                    <label class="form-check-label small" for="nearExpiryCheck">≤ 60d Expiry</label>
                </div>
                <div class="form-check form-check-inline mt-2">
                    <input class="form-check-input" type="checkbox" name="expired_only" id="expiredCheck" value="1" @checked(request('expired_only'))>
                    <label class="form-check-label small" for="expiredCheck">Expired</label>
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="{{ route('admin.inventory.batches') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Batches Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Batch Number</th>
                    <th>Product & SKU</th>
                    <th>Supplier</th>
                    <th>Mfg Date</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                    <th class="text-center">Current / Initial Qty</th>
                    <th>Unit Landed Cost</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">GRN Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                @php
                    $daysLeft = $batch->days_until_expiry;
                @endphp
                <tr>
                    <td>
                        <span class="font-monospace fw-bold fs-6 text-dark">{{ $batch->batch_number }}</span>
                    </td>
                    <td>
                        <strong>{{ $batch->product?->name }}</strong>
                        <div class="small text-muted">SKU: {{ $batch->product?->sku }}</div>
                    </td>
                    <td>
                        {{ $batch->vendor?->display_name ?: '-' }}
                    </td>
                    <td>
                        {{ $batch->mfg_date ? $batch->mfg_date->format('d M, Y') : '-' }}
                    </td>
                    <td>
                        {{ $batch->expiry_date ? $batch->expiry_date->format('d M, Y') : 'No Expiry' }}
                    </td>
                    <td>
                        @if($daysLeft !== null)
                            @if($daysLeft < 0)
                                <span class="badge bg-danger">Expired ({{ abs($daysLeft) }}d ago)</span>
                            @elseif($daysLeft <= 60)
                                <span class="badge bg-warning text-dark"><i class="fas fa-triangle-exclamation me-1"></i>{{ $daysLeft }} days left</span>
                            @else
                                <span class="badge bg-light text-success border">{{ $daysLeft }} days</span>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="fw-bold {{ $batch->current_qty > 0 ? 'text-success' : 'text-muted' }}">
                            {{ $batch->current_qty }}
                        </span>
                        <span class="text-muted small">/ {{ $batch->initial_qty }} units</span>
                    </td>
                    <td>
                        <strong>₹{{ number_format($batch->landed_unit_cost, 2) }}</strong>
                    </td>
                    <td class="text-center">
                        @if($batch->status === 'active' && $batch->current_qty > 0)
                            @if($daysLeft !== null && $daysLeft <= 60)
                                <span class="badge bg-warning text-dark">Near Expiry</span>
                            @else
                                <span class="badge bg-success">Active Stock</span>
                            @endif
                        @elseif($batch->status === 'expired' || ($daysLeft !== null && $daysLeft < 0))
                            <span class="badge bg-danger">Expired</span>
                        @elseif($batch->current_qty === 0)
                            <span class="badge bg-secondary">Depleted</span>
                        @else
                            <span class="badge bg-info text-dark">{{ ucfirst($batch->status) }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($batch->grnItem?->grn)
                            <a href="{{ route('admin.inward.show', $batch->grnItem->grn) }}" class="btn btn-sm btn-outline-primary font-monospace">
                                {{ $batch->grnItem->grn->grn_number }}
                            </a>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                        <i class="fas fa-boxes fa-3x mb-3 d-block text-secondary opacity-50"></i>
                        No batches found matching filter criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($batches->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $batches->links() }}
    </div>
    @endif
</div>
@endsection
