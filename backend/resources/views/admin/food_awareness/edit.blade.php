@extends('admin.layouts.app')

@section('title', 'Edit Food Safety Alert: ' . $item->title)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.food-awareness.index') }}" class="text-decoration-none small text-muted">
        <i class="fas fa-arrow-left me-1"></i> Back to Food Awareness Watch
    </a>
    <h2 class="mt-2 mb-1">Edit Awareness Alert</h2>
    <p class="text-muted small mb-0">Update exposure facts, media photo/video, or testing guide for this alert</p>
</div>

<form action="{{ route('admin.food-awareness.update', $item) }}" method="POST" enctype="multipart/form-data">
    @method('PUT')
    @include('admin.food_awareness._form')
</form>
@endsection
