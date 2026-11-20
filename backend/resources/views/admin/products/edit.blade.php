@extends('admin.layouts.app')
@section('title', 'Edit Product')
@section('content')
<h2 class="mb-4">Edit Product</h2>
<div class="card"><div class="card-body">
    <form action="{{ route('admin.products.update', array_merge(['product' => $product->id], request()->query())) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
        @foreach(request()->query() as $qKey => $qVal)
            @if(in_array($qKey, ['search', 'category_id', 'status', 'low_stock', 'page']))
                <input type="hidden" name="{{ $qKey }}" value="{{ $qVal }}">
            @endif
        @endforeach
        @include('admin.products._form')
        <button type="submit" class="btn btn-primary">Update Product</button>
        <a href="{{ route('admin.products.index', request()->query() ?: session('admin_products_filters', [])) }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
