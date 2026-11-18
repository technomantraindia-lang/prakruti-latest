<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lifestyle Medicine Consultant Login | Prakruti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e4620;
            --primary-dark: #132e15;
            --accent: #2e7d32;
            --bg-sand: #fbf9f4;
            --border-color: #e2dbce;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0d2110 0%, #17381b 50%, #204b25 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            color: #2c3e50;
        }
        .login-card {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .login-header {
            background: linear-gradient(145deg, #1b3d1d, #27572b);
            color: #ffffff;
            padding: 36px 32px 30px;
            text-align: center;
            position: relative;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #d4ebd6;
            margin-bottom: 14px;
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
            padding: 36px 32px;
        }
        .form-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e4620;
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
        .btn-consultant {
            background: linear-gradient(135deg, #1e4620, #2e7d32);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            border-radius: 12px;
            padding: 13px;
            border: none;
            width: 100%;
            box-shadow: 0 8px 20px rgba(30, 70, 32, 0.25);
            transition: all 0.2s ease;
        }
        .btn-consultant:hover {
            background: linear-gradient(135deg, #132e15, #1e4620);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(30, 70, 32, 0.35);
        }
        .consultant-credentials-box {
            background: #f4f8f4;
            border: 1px dashed #b9d8bc;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.8rem;
            color: #1e4620;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <span class="brand-badge"><i class="fas fa-video me-1"></i> Zoom Consultation Portal</span>
            <h1>Prakruti Lifestyle</h1>
            <p>Consultant &amp; Doctor Appointment Workspace</p>
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

            <form method="POST" action="{{ route('consultant.login.post') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Consultant / Doctor Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-md"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', 'drvarshas@yahoo.com') }}" required autofocus placeholder="drvarshas@yahoo.com">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label small text-muted" for="remember">Keep me logged in</label>
                    </div>
                    <a href="{{ route('admin.login') }}" class="small text-decoration-none text-muted">Admin Panel &rarr;</a>
                </div>

                <button type="submit" class="btn btn-consultant">
                    <i class="fas fa-stethoscope me-2"></i> Enter Consultation Workspace
                </button>
            </form>

            <div class="consultant-credentials-box">
                <div class="fw-bold mb-1"><i class="fas fa-shield-alt text-success me-1"></i> Doctor Access Credentials:</div>
                <div>&bull; <strong>Dr. Varsha Shah:</strong> <code>drvarshas@yahoo.com</code> / <code>Prakruti@123</code></div>
                <div>&bull; <strong>Or Admin Login:</strong> <code>admin@example.com</code> / <code>password</code></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
