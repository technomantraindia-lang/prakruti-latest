@extends('admin.layouts.app')
@section('title', $customer->name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">{{ $customer->name }}</h2>
        <span class="text-muted small">Customer ID: CUST-{{ str_pad($customer->id, 5, '0', STR_PAD_LEFT) }}</span>
    </div>
    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Customers</a>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light"><strong>Customer Profile</strong></div>
            <div class="card-body">
                <p class="mb-2"><strong>Email:</strong> <br><span class="text-muted">{{ $customer->email }}</span></p>
                <p class="mb-2"><strong>Phone:</strong> <br><span class="text-muted">{{ $customer->phone ?? 'Not provided' }}</span></p>
                <p class="mb-2"><strong>Profession:</strong> <br><span class="badge bg-light text-dark border">{{ $customer->profession ?? 'Not specified' }}</span></p>
                <p class="mb-2"><strong>Gender:</strong> <br><span class="text-capitalize text-muted">{{ $customer->gender ?? 'Not specified' }}</span></p>
                <p class="mb-2"><strong>Status:</strong> <br><span class="badge bg-{{ $customer->status==='active'?'success':'secondary' }}">{{ ucfirst($customer->status) }}</span></p>
                <p class="mb-2"><strong>Registered On:</strong> <br><span class="text-muted">{{ $customer->created_at ? $customer->created_at->format('M d, Y h:i A') : '-' }}</span></p>
                <p class="mb-0"><strong>Policy &amp; Terms Agreed:</strong> <br>
                    @if($customer->terms_accepted_at)
                        <span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-check-circle me-1"></i> Agreed on {{ $customer->terms_accepted_at->format('M d, Y h:i A') }}</span>
                    @else
                        <span class="badge bg-light text-muted border"><i class="fas fa-info-circle me-1"></i> Agreed during registration</span>
                    @endif
                </p>
            </div>
        </div>
        @if($customer->addresses->count())
        <div class="card shadow-sm"><div class="card-header bg-light"><strong>Saved Addresses</strong></div><div class="card-body">
            @foreach($customer->addresses as $addr)
            <div class="mb-2 p-2 border rounded bg-light">
                <small class="badge bg-secondary mb-1">{{ ucfirst($addr->type) }}</small><br>
                <strong>{{ $addr->fname }} {{ $addr->lname }}</strong><br>
                {{ $addr->address }}, {{ $addr->city }}, {{ $addr->state }} {{ $addr->zip }}
            </div>
            @endforeach
        </div></div>
        @endif
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm"><div class="card-header bg-light"><strong>Recent Orders</strong></div><div class="card-body">
            <table class="table table-sm table-hover align-middle">
                <thead><tr><th>Order #</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    @forelse ($customer->orders as $order)
                    <tr>
                        <td><strong>{{ $order->order_num }}</strong></td>
                        <td>₹{{ number_format($order->total, 2) }}</td>
                        <td><span class="badge bg-info">{{ ucfirst($order->status) }}</span></td>
                        <td>{{ $order->created_at->format('M d, Y') }}</td>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No orders placed yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div></div>
    </div>
</div>
@endsection
