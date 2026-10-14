@extends('admin.layouts.app')

@section('title', 'Add Food Safety & Adulteration Alert')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.food-awareness.index') }}" class="text-decoration-none small text-muted">
        <i class="fas fa-arrow-left me-1"></i> Back to Food Awareness Watch
    </a>
    <h2 class="mt-2 mb-1">Add Awareness Alert</h2>
    <p class="text-muted small mb-0">Publish an investigative exposure, viral food news, or purity advisory for website visitors</p>
</div>

<form action="{{ route('admin.food-awareness.store') }}" method="POST" enctype="multipart/form-data">
    @include('admin.food_awareness._form')
</form>
@endsection
