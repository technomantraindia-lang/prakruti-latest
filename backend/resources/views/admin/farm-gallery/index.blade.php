@extends('admin.layouts.app')

@section('title', 'Farm Gallery')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Farm Gallery</h2>
        <p class="text-muted small mb-0">Manage farm photos, videos, community moments & awards for the frontend gallery</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.farm-gallery.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add Photo / Video
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;">Media</th>
                        <th>Title &amp; Details</th>
                        <th>Category</th>
                        <th>Card Format</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                    <tr>
                        <td>
                            @if($item->image)
                                <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="rounded border" style="width: 80px; height: 56px; object-fit: cover;">
                            @elseif($item->videos->isNotEmpty())
                                <video src="{{ $item->videos->first()->video_url }}" class="rounded border bg-dark" style="width: 80px; height: 56px; object-fit: cover;"></video>
                            @else
                                <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 80px; height: 56px;">
                                    <i class="fas fa-image"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <strong class="d-block text-dark">{{ $item->title }}</strong>
                            <div class="text-muted small">
                                <span class="badge bg-light text-secondary border">{{ $item->tag }}</span>
                                @if($item->description)
                                    <span class="ms-1">{{ Str::limit($item->description, 50) }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                @if($item->category === 'farmer-community') 🧑‍🌾 @elseif($item->category === 'fields-cultivation') 🌾 @elseif($item->category === 'crops-vegetables') 🐄 @elseif($item->category === 'events' || $item->category === 'awards-events') 🎪 @endif
                                {{ $item->category_name }}
                            </span>
                        </td>
                        <td>
                            @if(($item->display_mode ?? 'image_text') === 'simple_image')
                                <span class="badge bg-light text-dark border"><i class="fas fa-image me-1"></i> Simple Photo</span>
                            @elseif(($item->display_mode ?? 'image_text') === 'simple_video')
                                <span class="badge bg-light text-dark border"><i class="fas fa-video me-1"></i> Simple Video</span>
                            @elseif(($item->display_mode ?? 'image_text') === 'video_text')
                                <span class="badge bg-info-subtle text-dark border"><i class="fas fa-film me-1"></i> Video + Text</span>
                            @else
                                <span class="badge bg-success-subtle text-success border"><i class="fas fa-file-image me-1"></i> Image + Text</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $item->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.farm-gallery.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit Item">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.farm-gallery.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this gallery item?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete Item">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-images fa-2x mb-2 d-block text-secondary"></i>
                            No farm gallery items found. Click <strong>Add Photo / Video</strong> to add one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="p-3 border-top">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
