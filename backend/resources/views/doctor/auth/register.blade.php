<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration | Prakruti Lifestyle Medicine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1b4d24;
            --primary-dark: #103317;
            --accent: #2e7d32;
            --bg-sand: #fbf9f4;
            --border-color: #e2dbce;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f2713 0%, #1a421e 50%, #245729 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            color: #2c3e50;
        }
        .register-card {
            width: 100%;
            max-width: 540px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .register-header {
            background: linear-gradient(145deg, #1b4d24, #276933);
            color: #ffffff;
            padding: 32px 32px 26px;
            text-align: center;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #d4ebd6;
            margin-bottom: 12px;
        }
        .register-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .register-header p {
            color: #c5dfc7;
            font-size: 0.84rem;
            margin-bottom: 0;
        }
        .register-body {
            padding: 32px;
        }
        .form-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1b4d24;
            margin-bottom: 5px;
        }
        .form-control, .form-select {
            border: 1.5px solid var(--border-color);
            background-color: var(--bg-sand);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            background-color: #ffffff;
            box-shadow: 0 0 0 3.5px rgba(46, 125, 50, 0.12);
        }
        .input-group-text {
            border: 1.5px solid var(--border-color);
            border-right: none;
            background-color: var(--bg-sand);
            color: #637765;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            padding: 10px 14px;
        }
        .input-group .form-control {
            border-left: none;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .btn-doctor {
            background: linear-gradient(135deg, #1b4d24, #2e7d32);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            border-radius: 12px;
            padding: 12px;
            border: none;
            width: 100%;
            box-shadow: 0 8px 20px rgba(27, 77, 36, 0.25);
            transition: all 0.2s ease;
        }
        .btn-doctor:hover {
            background: linear-gradient(135deg, #103317, #1b4d24);
            color: #ffffff;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <span class="brand-badge"><i class="fas fa-stethoscope me-1"></i> Medical Practitioner Portal</span>
            <h1>Doctor Registration</h1>
            <p>Create your Doctor Workspace account to conduct consultations</p>
        </div>
        <div class="d-flex border-bottom text-center">
            <a href="{{ route('doctor.login') }}" class="w-50 py-3 fw-bold text-decoration-none text-muted bg-light">
                <i class="fas fa-sign-in-alt me-1"></i> Doctor Login
            </a>
            <a href="{{ route('doctor.register') }}" class="w-50 py-3 fw-bold text-decoration-none text-success border-bottom border-3 border-success bg-white">
                <i class="fas fa-user-plus me-1"></i> Doctor Register
            </a>
        </div>
        <div class="register-body">
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3 small">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('doctor.register.post') }}">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label for="name" class="form-label">Full Doctor Name *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-md"></i></span>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Dr. Varsha Shah">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Doctor Email *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required placeholder="doctor@example.com">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">Contact / Mobile *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                            <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" required placeholder="e.g. 9876543210">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="qualification" class="form-label">Medical Degree / Qualification</label>
                        <input type="text" class="form-control" id="qualification" name="qualification" value="{{ old('qualification') }}" placeholder="e.g. MBBS, MD, BAMS, DCH">
                    </div>

                    <div class="col-md-6">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" value="{{ old('specialization', 'Lifestyle Medicine') }}" placeholder="e.g. Lifestyle Medicine, Nutrition">
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Password * (Min 8 chars)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••" style="border-right: none;">
                            <button class="btn btn-outline-secondary" type="button" style="border: 1.5px solid var(--border-color); border-left: none; background-color: var(--bg-sand); color: #637765; border-top-right-radius: 12px; border-bottom-right-radius: 12px;" onclick="togglePasswordVisibility('password', 'togglePassIcon1')" title="Show / Hide Password">
                                <i class="fas fa-eye" id="togglePassIcon1"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="••••••••" style="border-right: none;">
                            <button class="btn btn-outline-secondary" type="button" style="border: 1.5px solid var(--border-color); border-left: none; background-color: var(--bg-sand); color: #637765; border-top-right-radius: 12px; border-bottom-right-radius: 12px;" onclick="togglePasswordVisibility('password_confirmation', 'togglePassIcon2')" title="Show / Hide Password">
                                <i class="fas fa-eye" id="togglePassIcon2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="terms" required checked>
                    <label class="form-check-label small text-muted" for="terms">
                        I confirm that I am a verified medical/lifestyle practitioner with Prakruti.
                    </label>
                </div>

                <button type="submit" class="btn btn-doctor">
                    <i class="fas fa-user-plus me-2"></i> Register &amp; Enter Doctor Workspace
                </button>
            </form>

            <div class="text-center mt-3 pt-2 border-top">
                <span class="small text-muted">Already registered as a doctor?</span>
                <a href="{{ route('doctor.login') }}" class="small fw-bold text-success text-decoration-none ms-1">
                    Sign in to Doctor Panel &rarr;
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
