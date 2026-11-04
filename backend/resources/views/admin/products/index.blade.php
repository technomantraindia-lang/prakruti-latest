@extends('admin.layouts.app')
@section('title', 'Products')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="mb-0">Products</h2>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.products.bulkCreate') }}" class="btn btn-outline-primary"><i class="fas fa-layer-group"></i> Bulk Add</a>
        <a href="{{ route('admin.products.import') }}" class="btn btn-outline-secondary"><i class="fas fa-file-excel"></i> Excel Import</a>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a>
    </div>
</div>

@if(request()->boolean('low_stock'))
    <div class="alert alert-danger d-flex justify-content-between align-items-center gap-3">
        <span><i class="fas fa-arrow-trend-down me-2"></i>Showing products at or below their available-stock threshold.</span>
        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-danger">Clear filter</a>
    </div>
@endif

<div class="card mb-4"><div class="card-body">
    <form method="GET" class="row g-2">
        @if(request()->boolean('low_stock'))<input type="hidden" name="low_stock" value="1">@endif
        <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search name or SKU..." value="{{ request('search') }}"></div>
        <div class="col-md-3">
            <select name="category_id" class="form-select">
                <option value="">All Categories</option>
                @foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="active" @selected(request('status')==='active')>Active</option>
                <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary">Filter</button> <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Reset</a></div>
    </form>
</div></div>

<div class="card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Image</th><th>Product</th><th>Category</th><th>MRP</th><th>Stock</th><th>Nearest Expiry</th><th>Status</th><th>Best Seller</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td>
                    @if($product->image)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded border" style="width:50px;height:50px;object-fit:cover;">
                    @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;"><i class="fas fa-image text-muted"></i></div>
                    @endif
                </td>
                <td><strong>{{ $product->name }}</strong><br><small class="text-muted">{{ $product->sku }}</small></td>
                <td>{{ $product->category?->name ?? '-' }}</td>
                <td>
                    @if($product->sale_price)<del class="text-muted small">₹{{ number_format($product->price,0) }}</del> @endif
                    <strong>₹{{ number_format($product->display_price,0) }}</strong>
                    <small class="text-muted">/ {{ $product->unit ?? 'pcs' }}</small>
                </td>
                <td>
                    <span class="{{ $product->isLowStock() ? 'text-danger fw-bold' : '' }}">{{ $product->stock_qty }}</span>
                    @if($product->reserved_stock > 0)
                        <br><small class="text-muted">Avail: <strong class="text-success">{{ $product->available_stock }}</strong></small>
                    @endif
                    @foreach($product->variations as $variation)
                        <br><small class="text-muted">{{ $variation->attr_val ?: ($variation->weight ? $variation->weight . ' kg' : 'Package') }}: <strong>{{ $variation->stock_qty }}</strong></small>
                    @endforeach
                </td>
                <td>
                    @php($batch = $product->activeBatches->first())
                    @if($batch?->expiry_date)
                        @php($daysLeft = now()->startOfDay()->diffInDays($batch->expiry_date->startOfDay(), false))
                        <span class="{{ $daysLeft <= 60 ? 'text-danger fw-bold' : 'text-dark' }}">{{ $batch->expiry_date->format('d M Y') }}</span>
                        <br><small class="text-muted">Batch {{ $batch->batch_number }} · {{ $batch->current_qty }} left</small>
                    @else
                        <span class="text-muted">Not recorded</span>
                    @endif
                    @foreach($product->variations as $variation)
                        @php($packageBatch = $variation->batches->first())
                        @if($packageBatch?->expiry_date)
                            <br><small class="text-muted">{{ $variation->attr_val ?: 'Package' }}: {{ $packageBatch->expiry_date->format('d M Y') }}</small>
                        @endif
                    @endforeach
                </td>
                <td>
                    <form action="{{ route('admin.products.toggleStatus', $product) }}" method="POST" class="d-inline">@csrf @method('PATCH')
                        <button class="btn btn-sm btn-{{ $product->status==='active'?'success':'secondary' }}">{{ ucfirst($product->status) }}</button>
                    </form>
                </td>
                <td>
                    <form action="{{ route('admin.products.toggleFeatured', $product) }}" method="POST" class="d-inline">@csrf @method('PATCH')
                        <button class="btn btn-sm btn-{{ $product->featured?'warning':'outline-secondary' }}">{{ $product->featured?'Yes':'No' }}</button>
                    </form>
                </td>
                <td>
                    <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No products found</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $products->links() }}
</div></div>
@endsection
