@extends('admin.layouts.app')

@section('title', 'Website Visitors')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="mb-1">Website Visitors</h2>
        <p class="text-muted mb-0">Daily visitor activity from prakrutiheal.com, including guests who have not logged in or registered.</p>
    </div>
    <span class="badge bg-light text-success border px-3 py-2"><i class="fas fa-shield-alt me-1"></i> Guest privacy protected</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small">Unique visitors today</div><div class="fs-3 fw-bold text-success">{{ $todayCount }}</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small">Guest visitors</div><div class="fs-3 fw-bold text-warning">{{ $todayGuestCount }}</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small">Registered visitors</div><div class="fs-3 fw-bold text-primary">{{ $todayRegisteredCount }}</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small">Page views today</div><div class="fs-3 fw-bold text-dark">{{ $todayPageViews }}</div></div></div></div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small fw-bold">Visit date</label><input type="date" name="date" value="{{ $selectedDate }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label small fw-bold">Visitor type</label><select name="type" class="form-select"><option value="all" @selected($selectedType === 'all')>All visitors</option><option value="guest" @selected($selectedType === 'guest')>Guests only</option><option value="registered" @selected($selectedType === 'registered')>Registered only</option></select></div>
            <div class="col-md-4"><label class="form-label small fw-bold">Search name, email, phone, IP or page</label><input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search visitor details"></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-success flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button><a href="{{ route('admin.visitors.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm"><i class="fas fa-info-circle me-2"></i> For a guest visitor, the admin can see technical visit information only. Personal name, email and phone become available after the visitor logs in, registers, or submits those details in a form.</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Visitor</th><th>Contact</th><th>Type</th><th>Purchase</th><th>Follow-up</th><th>Page / source</th><th>Technical details</th><th>Last seen</th></tr></thead>
            <tbody>
                @forelse($visits as $visit)
                    <tr>
                        <td><div class="fw-bold">{{ $visit->display_name }}</div><div class="text-muted small">{{ $visit->visit_date?->format('d M Y') }}</div></td>
                        <td><div>{{ $visit->display_email ?: 'Not available' }}</div><div class="text-muted small">{{ $visit->display_phone ?: 'Phone not available' }}</div></td>
                        <td>{!! $visit->is_authenticated ? '<span class="badge bg-primary-subtle text-primary">Registered / logged in</span>' : '<span class="badge bg-warning-subtle text-warning-emphasis">Guest / not registered</span>' !!}</td>
                        <td>{!! $visit->has_purchased ? '<span class="badge bg-success-subtle text-success">Purchased</span>' : '<span class="badge bg-secondary-subtle text-secondary">No purchase found</span>' !!}</td>
                        <td>
                            @if($visit->marketing_consent)
                                <span class="badge bg-success-subtle text-success">Opted in</span>
                                <div class="small text-muted mt-1">{{ $visit->follow_up_sent_at ? 'Sent '.$visit->follow_up_sent_at->format('d M, h:i A') : 'One email pending' }}</div>
                            @else
                                <span class="badge bg-light text-muted border">No consent</span>
                            @endif
                        </td>
                        <td><div class="text-break">{{ $visit->landing_path ?: '/' }}</div><div class="text-muted small text-break">{{ $visit->referrer ?: 'Direct visit' }}</div></td>
                        <td><div class="small">IP: {{ $visit->ip_address ?: 'Unavailable' }}</div><div class="small text-muted">{{ $visit->visit_count }} page view(s)</div><div class="small text-muted text-truncate" style="max-width:220px" title="{{ $visit->user_agent }}">{{ $visit->user_agent ?: 'Browser details unavailable' }}</div></td>
                        <td><div>{{ $visit->last_seen_at?->format('d M Y, h:i A') }}</div><div class="text-muted small">{{ $visit->last_seen_at?->diffForHumans() }}</div></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5"><i class="fas fa-user-clock fa-2x mb-2"></i><div>No visitor records found for this filter.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($visits->hasPages())<div class="card-footer bg-white">{{ $visits->links() }}</div>@endif
</div>
@endsection
