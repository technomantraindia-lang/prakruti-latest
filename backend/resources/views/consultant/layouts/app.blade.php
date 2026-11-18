<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Consultant Workspace') | Prakruti Lifestyle Medicine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e4620;
            --primary-dark: #122d14;
            --primary-light: #e8f5e9;
            --accent: #2e7d32;
            --zoom-blue: #2d8cff;
            --bg-page: #f8faf7;
            --text-dark: #1a2e1d;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .consultant-navbar {
            background: linear-gradient(135deg, #163618 0%, #204b24 100%);
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
            padding: 12px 0;
        }
        .consultant-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #ffffff !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .badge-consultant-role {
            background: rgba(255, 255, 255, 0.16);
            color: #d8f3dc;
            font-size: 0.72rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            letter-spacing: 0.04em;
        }
        .nav-link-custom {
            color: #d2ecd5 !important;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 14px !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .nav-link-custom:hover, .nav-link-custom.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.14);
        }
        .btn-zoom {
            background-color: var(--zoom-blue);
            color: #ffffff;
            font-weight: 700;
            border: none;
        }
        .btn-zoom:hover {
            background-color: #1a75e8;
            color: #ffffff;
        }
        .btn-whatsapp {
            background-color: #25d366;
            color: #ffffff;
            font-weight: 700;
            border: none;
        }
        .btn-whatsapp:hover {
            background-color: #1eb855;
            color: #ffffff;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2ebd8;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .appointment-row {
            background: #ffffff;
            border: 1px solid #e5ece0;
            border-radius: 14px;
            margin-bottom: 12px;
            padding: 18px 22px;
            transition: all 0.2s ease;
        }
        .appointment-row:hover {
            border-color: #b3d9b7;
            box-shadow: 0 4px 14px rgba(30, 70, 32, 0.06);
        }
        .zoom-link-pill {
            background: #ebf5ff;
            color: #0056b3;
            border: 1px solid #c2e0ff;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-family: monospace;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg consultant-navbar sticky-top">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand consultant-brand" href="{{ route('consultant.appointments') }}">
                <i class="fas fa-seedling text-warning"></i>
                <span>Prakruti Lifestyle Medicine</span>
                <span class="badge-consultant-role"><i class="fas fa-stethoscope me-1"></i> Consultant Portal</span>
            </a>
            <button class="navbar-toggler text-white" type="button" data-bs-dismiss="collapse" data-bs-target="#consultantNav" aria-controls="consultantNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="consultantNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom {{ request('filter', 'pending') === 'pending' ? 'active' : '' }}" href="{{ route('consultant.appointments', ['filter' => 'pending']) }}">
                            <i class="fas fa-clock me-1"></i> Pending Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom {{ request('filter') === 'followup' ? 'active' : '' }}" href="{{ route('consultant.appointments', ['filter' => 'followup']) }}">
                            <i class="fas fa-video me-1"></i> Order Follow-ups (Zoom)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom {{ request('filter') === 'scheduled' ? 'active' : '' }}" href="{{ route('consultant.appointments', ['filter' => 'scheduled']) }}">
                            <i class="fas fa-calendar-check me-1"></i> Scheduled Meetings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom {{ request('filter') === 'all' ? 'active' : '' }}" href="{{ route('consultant.appointments', ['filter' => 'all']) }}">
                            <i class="fas fa-list me-1"></i> All Consultations
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#newOrderFollowupModal">
                        <i class="fas fa-plus-circle"></i> + New Order Follow-up (Zoom)
                    </button>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-warning" title="Go to Admin Panel">
                            <i class="fas fa-user-shield me-1"></i> Admin Panel
                        </a>
                    @endif
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2 fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-md text-success"></i>
                            <span>{{ auth()->user()->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><span class="dropdown-item-text small text-muted">Role: {{ auth()->user()->role?->name ?? 'Consultant' }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('consultant.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i> Log Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 py-4">
        <div class="container-fluid px-lg-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-check-circle fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('launch_whatsapp') || session('launch_gmail'))
                <div class="alert alert-info alert-dismissible fade show shadow-sm border-0" role="alert">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <strong><i class="fas fa-paper-plane me-1"></i> Notification Ready to Send:</strong>
                            Click below if your browser blocked the automated popup tab:
                        </div>
                        <div class="d-flex gap-2">
                            @if(session('launch_whatsapp'))
                                <a href="{{ session('launch_whatsapp') }}" target="_blank" class="btn btn-sm btn-whatsapp">
                                    <i class="fab fa-whatsapp me-1"></i> Open WhatsApp Web
                                </a>
                            @endif
                            @if(session('launch_gmail'))
                                <a href="{{ session('launch_gmail') }}" target="_blank" class="btn btn-sm btn-danger">
                                    <i class="fab fa-google me-1"></i> Open Gmail Compose
                                </a>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="bg-white border-top py-3 text-center text-muted small mt-auto">
        <div class="container">
            <span>&copy; {{ date('Y') }} Prakruti Corporation. Lifestyle Medicine Video Consultation Workspace &bull; Vadodara, Gujarat</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
