@extends('admin.layouts.app')
@section('title', 'Customers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0">Customers</h2>
        <span class="text-muted small">Registered customers directory & export</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.customers.export', request()->query()) }}" class="btn btn-success d-inline-flex align-items-center shadow-sm">
            <i class="fas fa-file-excel me-2"></i> Download Excel
        </a>
        <form class="d-flex" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search name, email, phone..." value="{{ request('search') }}">
            <button class="btn btn-outline-primary ms-2">Search</button>
            @if(request('search'))
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary ms-1">Reset</a>
            @endif
        </form>
    </div>
</div>
<div class="card shadow-sm"><div class="card-body">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Profession</th>
                    <th>Gender</th>
                    <th>Orders</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                <tr>
                    <td><strong>{{ $customer->name }}</strong></td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->phone ?? '-' }}</td>
                    <td>
                        @if($customer->profession)
                            <span class="badge bg-light text-dark border">{{ $customer->profession }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($customer->gender)
                            <span class="text-capitalize">{{ $customer->gender }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td><span class="badge bg-primary rounded-pill">{{ $customer->orders_count }}</span></td>
                    <td><span class="badge bg-{{ $customer->status==='active'?'success':'secondary' }}">{{ ucfirst($customer->status) }}</span></td>
                    <td class="small text-muted">{{ $customer->created_at ? $customer->created_at->format('d M Y') : '-' }}</td>
                    <td><a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">No customers found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $customers->links() }}
    </div>
</div></div>
@endsection
