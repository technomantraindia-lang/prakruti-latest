<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Doctor Panel') | Prakruti Lifestyle Medicine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --doc-primary: #17421f;
            --doc-dark: #0f2b14;
            --doc-accent: #2e7d32;
            --zoom-blue: #2d8cff;
            --bg-page: #f8faf7;
            --text-dark: #192c1b;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .doctor-navbar {
            background: linear-gradient(135deg, #13381a 0%, #1e5227 100%);
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
            padding: 12px 0;
        }
        .doctor-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #ffffff !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .badge-doctor-role {
            background: rgba(255, 255, 255, 0.16);
            color: #d8f3dc;
            font-size: 0.72rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 4px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .nav-link-doctor {
            color: #d2ecd5 !important;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 14px !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .nav-link-doctor:hover, .nav-link-doctor.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.16);
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
        .appointment-card {
            background: #ffffff;
            border: 1px solid #e1ebdb;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 14px;
            transition: all 0.2s ease;
        }
        .appointment-card:hover {
            border-color: #aed5b2;
            box-shadow: 0 6px 18px rgba(23, 66, 31, 0.08);
        }
        .stat-card-doc {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2ebd8;
            padding: 16px 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease;
        }
        .stat-card-doc:hover {
            transform: translateY(-2px);
        }

        /* Mobile, Laptop & Desktop Optimizations */
        @media (max-width: 767.98px) {
            .doctor-brand {
                font-size: 1.1rem;
            }
            .badge-doctor-role {
                display: none;
            }
            .stat-card-doc {
                padding: 12px 14px;
                border-radius: 10px;
            }
            .stat-card-doc .fs-4 {
                font-size: 1.25rem !important;
            }
            .stat-card-doc .text-muted {
                font-size: 0.68rem !important;
            }
            .appointment-card {
                padding: 16px;
                border-radius: 12px;
            }
            .nav-pills-scroll {
                overflow-x: auto;
                flex-wrap: nowrap !important;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 4px;
            }
            .nav-pills-scroll .nav-item {
                flex-shrink: 0;
            }
            .btn-zoom, .appointment-card .btn {
                min-height: 42px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        @media (min-width: 768px) and (max-width: 1199.98px) {
            .appointment-card {
                padding: 18px 20px;
            }
            .doctor-brand {
                font-size: 1.25rem;
            }
        }
        @media (min-width: 1200px) {
            .appointment-card {
                padding: 22px 28px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg doctor-navbar sticky-top">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand doctor-brand" href="{{ route('doctor.appointments') }}">
                <i class="fas fa-user-md text-warning"></i>
                <span>Prakruti Doctor Panel</span>
                <span class="badge-doctor-role"><i class="fas fa-check-circle me-1"></i> Admin-Approved Patients</span>
            </a>
            <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#doctorNav" aria-controls="doctorNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="doctorNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                    <li class="nav-item">
                        <a class="nav-link nav-link-doctor {{ request('tab', 'all') === 'all' ? 'active' : '' }}" href="{{ route('doctor.appointments', ['tab' => 'all']) }}">
                            <i class="fas fa-list me-1"></i> All Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-doctor {{ request('tab') === 'upcoming' ? 'active' : '' }}" href="{{ route('doctor.appointments', ['tab' => 'upcoming']) }}">
                            <i class="fas fa-calendar-alt me-1"></i> Upcoming Consultations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-doctor {{ request('tab') === 'today' ? 'active' : '' }}" href="{{ route('doctor.appointments', ['tab' => 'today']) }}">
                            <i class="fas fa-video me-1"></i> Today's Calls
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-doctor {{ request('tab') === 'followup' ? 'active' : '' }}" href="{{ route('doctor.appointments', ['tab' => 'followup']) }}">
                            <i class="fas fa-redo me-1"></i> Follow-up Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-doctor {{ request('tab') === 'completed' ? 'active' : '' }}" href="{{ route('doctor.appointments', ['tab' => 'completed']) }}">
                            <i class="fas fa-check-double me-1"></i> Completed
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2 fw-semibold" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-stethoscope text-success"></i>
                            <span>{{ auth()->user()?->name ?: 'Doctor Workspace' }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><span class="dropdown-item-text small text-muted">Role: Doctor / Consultant</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('doctor.logout') }}">
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
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="bg-white border-top py-3 text-center text-muted small mt-auto">
        <div class="container">
            <span>&copy; {{ date('Y') }} Prakruti Lifestyle Medicine. Doctor Panel Workspace &bull; Vadodara, Gujarat</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>
