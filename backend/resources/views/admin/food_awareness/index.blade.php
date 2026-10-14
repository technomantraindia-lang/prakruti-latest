@extends('admin.layouts.app')

@section('title', 'Food Safety & Adulteration Watch')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Food Safety &amp; Adulteration Watch</h2>
        <p class="text-muted small mb-0">Manage awareness news, adulteration exposures, videos, and purity verification cards shown on the Education page</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.food-awareness.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add Awareness Alert
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.food-awareness.index') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <option value="spices" {{ request('category') === 'spices' ? 'selected' : '' }}>Spices & Masalas</option>
                    <option value="ghee_dairy" {{ request('category') === 'ghee_dairy' ? 'selected' : '' }}>Ghee, Milk & Dairy</option>
                    <option value="produce" {{ request('category') === 'produce' ? 'selected' : '' }}>Fruits & Vegetables</option>
                    <option value="grains_pulses" {{ request('category') === 'grains_pulses' ? 'selected' : '' }}>Grains, Dals & Pulses</option>
                    <option value="oils_fats" {{ request('category') === 'oils_fats' ? 'selected' : '' }}>Cooking Oils & Fats</option>
                    <option value="general" {{ request('category') === 'general' ? 'selected' : '' }}>General</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="severity" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Severities</option>
                    <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical Alert</option>
                    <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Chemical Warning</option>
                    <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Consumer Awareness</option>
                </select>
            </div>
            @if(request('category') || request('severity'))
                <div class="col-auto">
                    <a href="{{ route('admin.food-awareness.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;">Media</th>
                        <th>Alert Title &amp; Summary</th>
                        <th>Category</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                    <tr>
                        <td>
                            @if($item->media_type === 'video' && $item->video)
                                <div class="position-relative rounded overflow-hidden border bg-dark" style="width: 80px; height: 56px;">
                                    @if($item->image)
                                        <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="w-100 h-100" style="object-fit: cover;" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=900&q=80';">
                                    @else
                                        <video src="{{ $item->video_url }}#t=0.1" class="w-100 h-100" style="object-fit: cover;" muted></video>
                                    @endif
                                    <span class="position-absolute top-50 start-50 translate-middle badge bg-danger rounded-circle p-1 shadow-sm" style="font-size: 0.65rem;">
                                        <i class="fas fa-play"></i>
                                    </span>
                                </div>
                            @elseif($item->image)
                                <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="rounded border" style="width: 80px; height: 56px; object-fit: cover;" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=900&q=80';">
                            @else
                                <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 80px; height: 56px;">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <strong class="d-block text-dark">{{ $item->title }}</strong>
                            <div class="text-muted small">
                                @if($item->tag)
                                    <span class="badge bg-light text-secondary border">{{ $item->tag }}</span>
                                @endif
                                <span class="ms-1">{{ Str::limit($item->summary, 70) }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                {{ $item->category_label ?? ucfirst($item->category) }}
                            </span>
                        </td>
                        <td>
                            @if($item->severity === 'critical')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Critical Alert
                                </span>
                            @elseif($item->severity === 'warning')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                    <i class="fas fa-biohazard me-1"></i> Warning
                                </span>
                            @else
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                    <i class="fas fa-info-circle me-1"></i> Awareness
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($item->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-muted border">Hidden</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.food-awareness.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit Alert">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.food-awareness.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this awareness alert?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Alert">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-shield-alt fa-3x mb-3 d-block text-secondary opacity-50"></i>
                            <p class="mb-2">No food adulteration alerts added yet.</p>
                            <a href="{{ route('admin.food-awareness.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Add Your First Alert
                            </a>
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
