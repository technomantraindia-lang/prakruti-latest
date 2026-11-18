@extends('admin.layouts.app')

@section('title', 'Doctor Master')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="fas fa-user-md text-success me-2"></i>Doctor Master</h2>
        <p class="text-muted mb-0">Maintain reusable doctor profiles for consultation assignment and dispatch.</p>
    </div>
    <a href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Consultations
    </a>
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

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card shadow-sm border-0 sticky-xl-top" style="top: 1rem;">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add Doctor Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.doctor-profiles.store') }}">
                    @csrf
                    @include('admin.doctor-profiles._form', ['doctor' => null, 'submitLabel' => 'Add Doctor'])
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-address-book text-success me-2"></i>Saved Doctor Profiles</h5>
                <span class="badge bg-light text-dark border">{{ $doctors->count() }} total</span>
            </div>
            <div class="card-body p-0">
                @forelse($doctors as $doctor)
                    <div class="p-4 border-bottom">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">{{ $doctor->name }}</h5>
                                <div class="text-muted small">{{ $doctor->specialization ?: 'Doctor' }} · {{ $doctor->qualification ?: 'Qualification not added' }}</div>
                                <div class="small mt-2">
                                    <a class="text-decoration-none me-3" href="tel:{{ $doctor->phone }}"><i class="fas fa-phone text-success me-1"></i>{{ $doctor->phone }}</a>
                                    <a class="text-decoration-none" href="mailto:{{ $doctor->email }}"><i class="fas fa-envelope text-danger me-1"></i>{{ $doctor->email }}</a>
                                </div>
                            </div>
                            <span class="badge {{ $doctor->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $doctor->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>

                        <details>
                            <summary class="text-success fw-bold small" style="cursor:pointer;">Edit profile</summary>
                            <form method="POST" action="{{ route('admin.doctor-profiles.update', $doctor) }}" class="pt-3">
                                @csrf
                                @method('PUT')
                                @include('admin.doctor-profiles._form', ['doctor' => $doctor, 'submitLabel' => 'Save Changes'])
                            </form>
                        </details>

                        <form method="POST" action="{{ route('admin.doctor-profiles.destroy', $doctor) }}" class="mt-3" onsubmit="return confirm('Remove this doctor from the master list?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash me-1"></i>Remove / Deactivate</button>
                            <span class="text-muted small ms-2">{{ $doctor->consultations_count }} consultation(s) linked</span>
                        </form>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No doctor profiles yet. Add the first doctor using the form.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
