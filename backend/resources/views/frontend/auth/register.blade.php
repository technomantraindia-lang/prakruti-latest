@extends('frontend.layouts.app')

@section('title', 'Register')

@section('content')
<div class="row justify-content-center my-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-gradient" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Create Account</h2>
                    <p class="text-muted">Register to start shopping fresh groceries</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-start-0 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="John Doe" required autofocus>
                        </div>
                        @error('name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control bg-light border-start-0 @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="john@example.com" required>
                        </div>
                        @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Mobile Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-phone text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-start-0 @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="9876543210" required>
                        </div>
                        @error('phone')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="profession" class="form-label">Profession *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-briefcase text-muted"></i></span>
                            <select class="form-select bg-light border-start-0 @error('profession') is-invalid @enderror" id="profession" name="profession" required>
                                <option value="">-- Select Profession --</option>
                                <option value="Doctor / Healthcare" {{ old('profession') == 'Doctor / Healthcare' ? 'selected' : '' }}>Doctor / Healthcare Professional</option>
                                <option value="IT / Software / Tech" {{ old('profession') == 'IT / Software / Tech' ? 'selected' : '' }}>IT / Software / Technology</option>
                                <option value="Business / Entrepreneur" {{ old('profession') == 'Business / Entrepreneur' ? 'selected' : '' }}>Business / Entrepreneur</option>
                                <option value="Homemaker" {{ old('profession') == 'Homemaker' ? 'selected' : '' }}>Homemaker / Home Manager</option>
                                <option value="Teacher / Education" {{ old('profession') == 'Teacher / Education' ? 'selected' : '' }}>Teacher / Professor / Education</option>
                                <option value="Corporate / Private Sector" {{ old('profession') == 'Corporate / Private Sector' ? 'selected' : '' }}>Corporate / Private Sector</option>
                                <option value="Government / Public Sector" {{ old('profession') == 'Government / Public Sector' ? 'selected' : '' }}>Government / Public Sector</option>
                                <option value="Finance / Banking" {{ old('profession') == 'Finance / Banking' ? 'selected' : '' }}>Finance / Banking / Accounting</option>
                                <option value="Student" {{ old('profession') == 'Student' ? 'selected' : '' }}>Student</option>
                                <option value="Retired" {{ old('profession') == 'Retired' ? 'selected' : '' }}>Retired</option>
                                <option value="Other" {{ old('profession') == 'Other' ? 'selected' : '' }}>Other Profession</option>
                            </select>
                        </div>
                        @error('profession')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-venus-mars text-muted"></i></span>
                            <select class="form-select bg-light border-start-0 @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                                <option value="">-- Select Gender --</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other / Prefer not to say</option>
                            </select>
                        </div>
                        @error('gender')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control bg-light border-start-0 @error('password') is-invalid @enderror" id="password" name="password" placeholder="••••••••" required>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.82rem; font-style: italic;">* Password must be at least 8 characters long.</small>
                        @error('password')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control bg-light border-start-0" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input @error('terms_accepted') is-invalid @enderror" type="checkbox" id="terms_accepted" name="terms_accepted" value="1" {{ old('terms_accepted') ? 'checked' : '' }} required>
                            <label class="form-check-label small" for="terms_accepted">
                                I agree to the <strong>Terms &amp; Conditions, Return, Breakage &amp; Opened Goods Policy</strong> as per standard norms. ✅
                            </label>
                        </div>
                        @error('terms_accepted')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold text-white shadow-sm mb-3">Register</button>

                    <div class="text-center">
                        <span class="text-muted">Already have an account?</span>
                        <a href="{{ route('login') }}" class="text-decoration-none fw-bold" style="color: var(--primary);">Login here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
