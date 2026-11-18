@extends('admin.layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-9">
        <div class="mb-4">
            <h2 class="mb-1"><i class="fas fa-key text-success me-2"></i>Change Password</h2>
            <p class="text-muted mb-0">Update the password for the currently logged-in administrator.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-shield-halved text-success me-2"></i>Admin Password</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="admin_current_password">Current password</label>
                        <div class="input-group">
                            <input type="password" id="admin_current_password" name="admin_current_password" class="form-control password-eye-input @error('admin_current_password') is-invalid @enderror" autocomplete="current-password" required>
                            <button type="button" class="btn btn-outline-secondary password-eye-toggle" aria-label="Show password" title="Show password"><i class="fas fa-eye" aria-hidden="true"></i></button>
                        </div>
                        @error('admin_current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="admin_new_password">New password</label>
                        <div class="input-group">
                            <input type="password" id="admin_new_password" name="admin_new_password" class="form-control password-eye-input @error('admin_new_password') is-invalid @enderror" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="btn btn-outline-secondary password-eye-toggle" aria-label="Show password" title="Show password"><i class="fas fa-eye" aria-hidden="true"></i></button>
                        </div>
                        @error('admin_new_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <div class="form-text">Use at least 8 characters.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="admin_new_password_confirmation">Confirm new password</label>
                        <div class="input-group">
                            <input type="password" id="admin_new_password_confirmation" name="admin_new_password_confirmation" class="form-control password-eye-input" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="btn btn-outline-secondary password-eye-toggle" aria-label="Show password" title="Show password"><i class="fas fa-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success"><i class="fas fa-lock me-1"></i>Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-eye-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = button.parentElement.querySelector('.password-eye-input');
            const icon = button.querySelector('i');
            if (!input) return;
            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            icon.className = showing ? 'fas fa-eye-slash' : 'fas fa-eye';
            button.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
            button.setAttribute('title', showing ? 'Hide password' : 'Show password');
        });
    });
});
</script>
@endsection
