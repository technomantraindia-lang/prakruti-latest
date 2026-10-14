@extends('admin.layouts.app')

@section('title', 'Add New PDF Guideline')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.guidelines.index') }}" class="text-decoration-none text-muted small">
        <i class="fas fa-arrow-left me-1"></i> Back to Guidelines Library
    </a>
    <h2 class="mt-2 mb-1">Add New PDF Guideline</h2>
    <p class="text-muted small mb-0">Publish an official health report, WHO document, or dietary standard for the customer storefront</p>
</div>

<form method="POST" action="{{ route('admin.guidelines.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.guidelines._form')
</form>
@endsection
