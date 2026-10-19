@extends('admin.layouts.app')

@section('title', 'PDF Guidelines Library')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">PDF Guidelines Library</h2>
        <p class="text-muted small mb-0">Manage PDF guideline files, titles, and descriptions available in the research library</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.guidelines.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add New PDF Guideline
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.guidelines.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by guideline title or description..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.guidelines.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">Doc</th>
                        <th>Guideline Title &amp; Description</th>
                        <th>File &amp; Size</th>
                        <th>Downloads</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>
                                <div class="bg-danger text-white rounded p-2 text-center" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-file-pdf fa-lg"></i>
                                </div>
                            </td>
                            <td>
                                <strong class="d-block text-dark">{{ $item->title }}</strong>
                                <small class="text-muted d-block" style="max-width: 500px; white-space: normal;">
                                    {{ Str::limit($item->description, 140) }}
                                </small>
                            </td>
                            <td>
                                @if($item->file_path)
                                    <div>
                                        <i class="fas fa-file-pdf text-danger me-1"></i>
                                        <span class="small fw-semibold">{{ basename($item->file_path) }}</span>
                                    </div>
                                    <small class="text-muted">{{ $item->file_size ?? 'PDF Document' }}</small>
                                @elseif($item->external_url)
                                    <a href="{{ $item->external_url }}" target="_blank" rel="noopener noreferrer" class="small text-primary text-decoration-none">
                                        <i class="fas fa-external-link-alt fa-xs me-1"></i> External Document
                                    </a>
                                @else
                                    <span class="text-muted small">No file attached</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border">
                                    <i class="fas fa-download me-1"></i> {{ number_format($item->download_count) }}
                                </span>
                            </td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary">Hidden</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @if($item->download_url)
                                        <a href="{{ $item->download_url }}" target="_blank" class="btn btn-outline-info" title="Preview / Open PDF">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.guidelines.edit', $item) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.guidelines.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this guideline?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-file-pdf fa-3x mb-3 text-secondary opacity-50"></i>
                                <p class="mb-0">No guidelines found in the library.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($items->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $items->links() }}
        </div>
    @endif
</div>
@endsection
