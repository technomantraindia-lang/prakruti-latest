@extends('admin.layouts.app')
@section('title', 'Vendors & Suppliers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="fas fa-truck-loading me-2 text-primary"></i> Vendors & Suppliers</h2>
        <p class="text-muted mb-0">Manage supplier profiles, GSTIN details, payment terms, and product pricing matrices.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.inward.create') }}" class="btn btn-outline-success"><i class="fas fa-boxes-packing me-1"></i> Inward Stock (GRN)</a>
        <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Vendor</a>
    </div>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, company, GSTIN, phone, or email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Vendor / Company</th>
                    <th>GSTIN & PAN</th>
                    <th>Contact Info</th>
                    <th>Location</th>
                    <th>Payment Terms</th>
                    <th class="text-center">Products</th>
                    <th class="text-center">Inwards (GRN)</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $vendor)
                <tr>
                    <td>
                        <a href="{{ route('admin.vendors.show', $vendor) }}" class="fw-bold text-dark text-decoration-none">
                            {{ $vendor->company_name ?: $vendor->name }}
                        </a>
                        @if($vendor->company_name)
                            <div class="small text-muted"><i class="fas fa-user-circle me-1"></i>{{ $vendor->name }}</div>
                        @endif
                    </td>
                    <td>
                        @if($vendor->gstin)
                            <span class="badge bg-light text-dark border font-monospace">{{ $vendor->gstin }}</span>
                        @else
                            <span class="text-muted small">No GSTIN</span>
                        @endif
                        @if($vendor->pan)
                            <div class="small text-muted font-monospace">PAN: {{ $vendor->pan }}</div>
                        @endif
                    </td>
                    <td>
                        @if($vendor->phone)<div><i class="fas fa-phone me-1 text-muted small"></i>{{ $vendor->phone }}</div>@endif
                        @if($vendor->email)<div><i class="fas fa-envelope me-1 text-muted small"></i>{{ $vendor->email }}</div>@endif
                    </td>
                    <td>
                        {{ $vendor->city ?: '-' }}{{ $vendor->state ? ', ' . $vendor->state : '' }}
                        @if($vendor->state_code)<span class="badge bg-secondary ms-1">State: {{ $vendor->state_code }}</span>@endif
                    </td>
                    <td>
                        <span class="badge bg-info text-dark">Net {{ $vendor->payment_terms_days }} Days</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-primary border fs-6">{{ $vendor->vendor_products_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-success border fs-6">{{ $vendor->goods_received_notes_count }}</span>
                    </td>
                    <td class="text-center">
                        @if($vendor->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.vendors.show', $vendor) }}" class="btn btn-outline-info" title="View details & products">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn btn-outline-secondary" title="Edit vendor">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.vendors.destroy', $vendor) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete vendor \'{{ addslashes($vendor->display_name) }}\'? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Delete vendor">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fas fa-truck-loading fa-3x mb-3 d-block text-secondary opacity-50"></i>
                        No vendors found. Click <strong>"Add Vendor"</strong> above to register your first supplier.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($vendors->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $vendors->links() }}
    </div>
    @endif
</div>
@endsection
