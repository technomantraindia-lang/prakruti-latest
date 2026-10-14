@extends('admin.layouts.app')

@section('title', 'Frontend Reviews & Testimonials')

@section('content')
@php
    $pendingTestimonialsCount = \Illuminate\Support\Facades\Schema::hasTable('testimonials') ? \App\Models\Testimonial::where('status', 'pending')->count() : 0;
    $pendingProductReviewsCount = \Illuminate\Support\Facades\Schema::hasTable('product_reviews') ? \App\Models\ProductReview::where('status', 'pending')->count() : 0;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-1 fw-bold text-dark">Frontend Reviews &amp; Testimonials</h2>
        <p class="text-muted mb-0">Manage customer ratings, stories, photos, and videos submitted from the storefront.</p>
    </div>
    <div class="btn-group shadow-sm">
        <a href="{{ route('admin.reviews.index', ['type' => 'product']) }}" class="btn btn-{{ $type === 'product' ? 'primary' : 'outline-primary' }} d-inline-flex align-items-center gap-2">
            <i class="fas fa-box"></i> Product Reviews
            @if($pendingProductReviewsCount > 0)
                <span class="badge bg-danger">{{ $pendingProductReviewsCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.reviews.index', ['type' => 'testimonial']) }}" class="btn btn-{{ $type === 'testimonial' ? 'primary' : 'outline-primary' }} d-inline-flex align-items-center gap-2">
            <i class="fas fa-comments"></i> Homepage Testimonials
            @if($pendingTestimonialsCount > 0)
                <span class="badge bg-danger animate-pulse">{{ $pendingTestimonialsCount }} NEW</span>
            @endif
        </a>
    </div>
</div>

@if($type === 'testimonial')
    @if($pendingTestimonialsCount > 0)
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between p-3 mb-4 rounded-3" style="background: linear-gradient(90deg, #fffbeb 0%, #fef3c7 100%); border-left: 5px solid #d97706 !important;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-25 p-2 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; flex-shrink: 0;">
                    <i class="fas fa-bell fs-5 text-dark"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">
                        <span class="badge bg-danger me-2">NEW REQUESTS</span>
                        {{ $pendingTestimonialsCount }} Customer Testimonial(s) Awaiting Approval!
                    </h6>
                    <small class="text-dark opacity-75">Customer submissions with text, photos, or videos are held in pending status until you approve them to be displayed on the homepage.</small>
                </div>
            </div>
            <a href="{{ route('admin.reviews.index', ['type' => 'testimonial', 'status' => 'pending']) }}" class="btn btn-sm btn-dark px-3 fw-bold flex-shrink-0 ms-2">
                <i class="fas fa-filter me-1"></i> Filter Pending ({{ $pendingTestimonialsCount }})
            </a>
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fas fa-quote-left text-success"></i> Customer Stories &amp; Testimonials
                </h5>
                <small class="text-muted">Review customer submissions with media previews and 1-click publishing.</small>
            </div>
            <form method="GET" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="type" value="testimonial">
                <span class="text-muted small fw-bold">Filter:</span>
                <select name="status" class="form-select form-select-sm" style="min-width: 170px;" onchange="this.form.submit()">
                    <option value="">All Submissions</option>
                    @foreach(['pending' => 'Pending Approval', 'active' => 'Approved (Live on Home)', 'rejected' => 'Rejected / Hidden', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }} {{ $value === 'pending' && $pendingTestimonialsCount > 0 ? "({$pendingTestimonialsCount})" : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 220px;">Customer</th>
                        <th style="width: 110px;">Rating</th>
                        <th>Review Text</th>
                        <th style="width: 170px;">Media Uploads</th>
                        <th style="width: 160px;">Status</th>
                        <th style="width: 130px;">Submitted</th>
                        <th style="width: 230px;" class="text-end">Approval Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($testimonials as $testimonial)
                        <tr class="{{ $testimonial->status === 'pending' ? 'table-warning bg-opacity-25' : '' }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($testimonial->image_url)
                                        <img src="{{ $testimonial->image_url }}" alt="{{ $testimonial->name ?? 'Customer' }}" class="rounded-circle shadow-sm" style="width: 42px; height: 42px; object-fit: cover; border: 2px solid #2d5a27;" onerror="this.style.display='none';">
                                    @else
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; font-size: 1rem;">
                                            {{ $testimonial->name ? strtoupper(substr($testimonial->name, 0, 1)) : 'C' }}
                                        </div>
                                    @endif
                                    <div>
                                        <strong class="text-dark d-block">{{ $testimonial->name ?: 'Anonymous Customer' }}</strong>
                                        <div class="small text-muted">
                                            <i class="fas fa-map-marker-alt text-secondary me-1"></i>{{ $testimonial->location ?: 'Verified Customer' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-warning fw-bold fs-6">{{ str_repeat('★', (int) $testimonial->rating) }}</div>
                                <span class="small text-muted fw-bold">({{ $testimonial->rating }}/5)</span>
                            </td>
                            <td style="max-width: 340px;">
                                <div class="text-dark fst-italic">
                                    {{ $testimonial->msg ? '"' . $testimonial->msg . '"' : '<No written text>' }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @if($testimonial->image_url)
                                        <a href="{{ $testimonial->image_url }}" target="_blank" class="btn btn-outline-primary btn-sm py-1 px-2 d-inline-flex align-items-center gap-1 text-decoration-none" style="font-size: 0.78rem;">
                                            <i class="fas fa-image text-primary"></i>
                                            <span>View Photo</span>
                                            <i class="fas fa-external-link-alt ms-auto small opacity-75"></i>
                                        </a>
                                    @endif
                                    @if($testimonial->video_url)
                                        <a href="{{ $testimonial->video_url }}" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-2 d-inline-flex align-items-center gap-1 text-decoration-none" style="font-size: 0.78rem;">
                                            <i class="fas fa-video text-danger"></i>
                                            <span>Watch Video</span>
                                            <i class="fas fa-external-link-alt ms-auto small opacity-75"></i>
                                        </a>
                                    @endif
                                    @if(!$testimonial->image_url && !$testimonial->video_url)
                                        <span class="text-muted small">
                                            <i class="fas fa-font me-1"></i>Text Only
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($testimonial->status === 'active' || $testimonial->status === 'approved')
                                    <span class="badge bg-success text-white px-2 py-1 shadow-sm">
                                        <i class="fas fa-check-circle me-1"></i>Approved (Live)
                                    </span>
                                @elseif($testimonial->status === 'rejected' || $testimonial->status === 'inactive')
                                    <span class="badge bg-danger text-white px-2 py-1 shadow-sm">
                                        <i class="fas fa-ban me-1"></i>Rejected
                                    </span>
                                @else
                                    <span class="badge bg-danger text-white px-2 py-1 shadow-sm animate-pulse d-inline-block mb-1">
                                        <i class="fas fa-sparkles me-1"></i>NEW
                                    </span>
                                    <span class="badge bg-warning text-dark border border-warning-subtle px-2 py-1 d-block" style="font-size: 0.75rem;">
                                        <i class="fas fa-clock me-1"></i>Pending Review
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                <div>{{ $testimonial->created_at?->format('d M Y') }}</div>
                                <div class="opacity-75">{{ $testimonial->created_at?->format('h:i A') }}</div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                                    @if($testimonial->status !== 'active' && $testimonial->status !== 'approved')
                                        <form action="{{ route('admin.reviews.testimonial.update', $testimonial) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="btn btn-sm btn-success fw-bold d-inline-flex align-items-center gap-1 px-2 py-1 shadow-sm" title="Approve and publish directly to homepage">
                                                <i class="fas fa-check-circle"></i> Approve
                                            </button>
                                        </form>
                                    @endif

                                    @if($testimonial->status !== 'rejected')
                                        <form action="{{ route('admin.reviews.testimonial.update', $testimonial) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 px-2 py-1" title="Reject and hide from homepage">
                                                <i class="fas fa-times-circle"></i> Reject
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('admin.reviews.testimonial.destroy', $testimonial) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this customer testimonial?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-1" title="Delete record">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-comments fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                No testimonials found for the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $testimonials->links() }}</div>
@else
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fas fa-star text-warning"></i> Product Customer Reviews
                </h5>
                <small class="text-muted">Manage product specific reviews submitted by shoppers.</small>
            </div>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="type" value="product">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['active' => 'Active', 'pending' => 'Pending', 'hidden' => 'Hidden'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productReviews as $review)
                        <tr>
                            <td>
                                <strong>{{ $review->product?->name ?? 'Unknown Product' }}</strong>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $review->name }}</div>
                                <small class="text-muted">{{ $review->location ?: 'Verified Buyer' }}</small>
                            </td>
                            <td>
                                <span class="text-warning fw-bold">{{ str_repeat('★', (int) $review->rating) }}</span>
                                <small class="text-muted">({{ $review->rating }}/5)</small>
                            </td>
                            <td style="max-width: 320px;">
                                {{ $review->comment }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $review->status === 'active' ? 'success' : ($review->status === 'pending' ? 'warning text-dark' : 'secondary') }}">
                                    {{ ucfirst($review->status) }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $review->created_at?->format('d M Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.reviews.product.update', $review) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($review->status !== 'active')
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    @else
                                        <input type="hidden" name="status" value="hidden">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Hide</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-star-half-alt fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                No product reviews found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $productReviews->links() }}</div>
@endif

<style>
.animate-pulse {
    animation: pulseGlow 1.8s infinite;
}
@keyframes pulseGlow {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.5);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
    }
}
</style>
@endsection
