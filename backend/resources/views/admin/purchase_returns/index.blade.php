@extends('admin.layouts.app')

@section('title', 'Purchase Returns (RTV) & Vendor Debit Notes')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Purchase Returns (Return to Vendor)</h1>
            <p class="text-muted small mb-0">Manage returns of defective or near-expiry inventory and automated Vendor Debit Notes.</p>
        </div>
        <a href="{{ route('admin.purchase-returns.create') }}" class="btn btn-danger">
            <i class="fas fa-undo-alt me-1"></i> Create Purchase Return (RTV)
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <form method="GET" action="{{ route('admin.purchase-returns.index') }}" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search Return #, Debit Note #, Vendor..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <select name="vendor_id" class="form-select">
                        <option value="">-- All Vendors --</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }} ({{ $v->company_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Return Number</th>
                            <th>Vendor</th>
                            <th>Debit Note #</th>
                            <th>Reason</th>
                            <th>Taxable</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $ret)
                            <tr>
                                <td>
                                    <strong>{{ $ret->return_number }}</strong>
                                    <span class="badge bg-danger ms-1">RTV</span>
                                </td>
                                <td>
                                    <strong>{{ $ret->vendor->name ?? 'N/A' }}</strong><br>
                                    <small class="text-muted">GST: {{ $ret->vendor->gstin ?? 'Unregistered' }}</small>
                                </td>
                                <td>
                                    @if($ret->debitNote)
                                        <span class="badge bg-warning text-dark font-monospace">{{ $ret->debitNote->debit_note_number }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><span class="text-truncate d-inline-block" style="max-width: 180px;">{{ $ret->reason }}</span></td>
                                <td>₹{{ number_format($ret->total_taxable, 2) }}</td>
                                <td class="fw-bold text-danger">₹{{ number_format($ret->total_amount, 2) }}</td>
                                <td>{{ $ret->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.purchase-returns.show', $ret->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-box-open fa-3x mb-3 text-secondary"></i>
                                    <p class="mb-0">No purchase returns recorded yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($returns->hasPages())
            <div class="card-footer bg-white">
                {{ $returns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
