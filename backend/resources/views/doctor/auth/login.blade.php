<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Panel Login | Prakruti Lifestyle Medicine</title>
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
            padding: 24px 16px;
            color: #2c3e50;
        }
        .login-card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .login-header {
            background: linear-gradient(145deg, #1b4d24, #276933);
            color: #ffffff;
            padding: 34px 32px 28px;
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
        .login-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.85rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .login-header p {
            color: #c5dfc7;
            font-size: 0.85rem;
            margin-bottom: 0;
        }
        .login-body {
            padding: 32px;
        }
        .form-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1b4d24;
            margin-bottom: 6px;
        }
        .form-control {
            border: 1.5px solid var(--border-color);
            background-color: var(--bg-sand);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
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
            padding: 13px;
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
        .btn-demo-login {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            color: #166534;
            font-weight: 700;
            font-size: 0.88rem;
            border-radius: 12px;
            padding: 11px;
            width: 100%;
            transition: all 0.2s ease;
        }
        .btn-demo-login:hover {
            background: #dcfce7;
            border-color: #4ade80;
            color: #14532d;
            transform: translateY(-1px);
        }
        .doctor-credentials-box {
            background: #f4f8f4;
            border: 1px dashed #b9d8bc;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.8rem;
            color: #1b4d24;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <span class="brand-badge"><i class="fas fa-stethoscope me-1"></i> Doctor Workspace</span>
            <h1>Doctor Portal</h1>
            <p>Access Patient Consultations &amp; Video Call Links</p>
        </div>
        <div class="d-flex border-bottom text-center">
            <a href="{{ route('doctor.login') }}" class="w-50 py-3 fw-bold text-decoration-none text-success border-bottom border-3 border-success bg-white">
                <i class="fas fa-sign-in-alt me-1"></i> Doctor Login
            </a>
            <a href="{{ route('doctor.register') }}" class="w-50 py-3 fw-bold text-decoration-none text-muted bg-light">
                <i class="fas fa-user-plus me-1"></i> Doctor Register
            </a>
        </div>
        <div class="login-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3 small">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('doctor.login.post') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Doctor Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-md"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', 'drvarshas@yahoo.com') }}" required autofocus placeholder="drvarshas@yahoo.com">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required value="Prakruti@123" placeholder="••••••••" style="border-right: none;">
                        <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" style="border: 1.5px solid var(--border-color); border-left: none; background-color: var(--bg-sand); color: #637765; border-top-right-radius: 12px; border-bottom-right-radius: 12px;" onclick="togglePasswordVisibility('password', 'togglePasswordIcon')" title="Show / Hide Password">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" checked>
                        <label class="form-check-label small text-muted" for="remember">Keep me logged in</label>
                    </div>
                    <a href="{{ route('doctor.register') }}" class="small text-decoration-none text-success fw-bold">Register as Doctor &rarr;</a>
                </div>

                <button type="submit" class="btn btn-doctor mb-2">
                    <i class="fas fa-sign-in-alt me-2"></i> Enter Doctor Panel
                </button>
            </form>

            <form method="POST" action="{{ route('doctor.demoLogin') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-demo-login">
                    <i class="fas fa-bolt text-warning me-1"></i> Quick Test: 1-Click Login (Dr. Varsha Shah)
                </button>
            </form>

            <div class="doctor-credentials-box">
                <div class="fw-bold mb-1"><i class="fas fa-user-md text-success me-1"></i> Doctor Credentials:</div>
                <div>&bull; <strong>Email:</strong> <code>drvarshas@yahoo.com</code></div>
                <div>&bull; <strong>Password:</strong> <code>Prakruti@123</code></div>
                <div class="mt-1 small text-muted fst-italic">Note: Dedicated Doctor Panel. Doctors only view verified patients and have no access to Admin Panel.</div>
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
