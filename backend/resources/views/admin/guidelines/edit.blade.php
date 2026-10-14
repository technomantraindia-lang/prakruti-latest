@extends('admin.layouts.app')

@section('title', 'Edit PDF Guideline')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.guidelines.index') }}" class="text-decoration-none text-muted small">
        <i class="fas fa-arrow-left me-1"></i> Back to Guidelines Library
    </a>
    <h2 class="mt-2 mb-1">Edit PDF Guideline</h2>
    <p class="text-muted small mb-0">Update document details, file upload, or external WHO / ICMR reference links</p>
</div>

<form method="POST" action="{{ route('admin.guidelines.update', $item) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.guidelines._form')
</form>
@endsection
