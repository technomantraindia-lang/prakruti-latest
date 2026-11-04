@extends('admin.layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Notifications</h2><p class="text-muted mb-0">Order alerts and operational messages for your admin account.</p></div>
    @if($unreadCount > 0)
        <form method="POST" action="{{ route('admin.notifications.readAll') }}">@csrf<button class="btn btn-outline-success"><i class="fas fa-check-double me-1"></i> Mark all read</button></form>
    @endif
</div>
<div class="card shadow-sm"><div class="list-group list-group-flush">
    @forelse($notifications as $notification)
        @php($data = $notification->data ?? [])
        <div class="list-group-item d-flex align-items-start gap-3 py-3 {{ $notification->read_at ? '' : 'bg-success bg-opacity-10' }}">
            <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:38px;height:38px"><i class="fas fa-bell"></i></div>
            <div class="flex-grow-1"><div class="fw-bold">{{ $data['title'] ?? 'Store notification' }}</div><div class="text-muted small">{{ $data['message'] ?? 'You have a new admin notification.' }}</div><div class="text-muted small mt-1">{{ $notification->created_at->diffForHumans() }}</div></div>
            <div class="d-flex gap-2">
                @if(!empty($data['action_url']))<a href="{{ $data['action_url'] }}" class="btn btn-sm btn-outline-primary">Open</a>@endif
                @if(!$notification->read_at)<form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Read</button></form>@endif
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5"><i class="far fa-bell-slash fa-2x mb-2"></i><div>No notifications yet.</div></div>
    @endforelse
</div><div class="card-footer bg-white">{{ $notifications->links() }}</div></div>
@endsection
