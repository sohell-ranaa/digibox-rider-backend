<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Digibox Rider Tracker') - Admin Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @stack('styles')

    <style>
        :root {
            --digibox-blue: #2563EB;
            --digibox-blue-dark: #1e40af;
            --digibox-blue-light: #3b82f6;
            --success-green: #10b981;
            --warning-orange: #f59e0b;
            --danger-red: #ef4444;
            --info-cyan: #06b6d4;
            --purple: #8b5cf6;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        body.sidebar-open {
            overflow: hidden;
        }

        /* Sidebar with Digibox Blue */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--digibox-blue) 0%, var(--digibox-blue-dark) 100%);
            box-shadow: 4px 0 20px rgba(37, 99, 235, 0.15);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            z-index: 1040;
            overflow-y: auto;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .sidebar-footer {
            margin-top: auto;
        }
        .sidebar-footer small {
            font-size: 0.75rem;
            opacity: 0.7;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 14px 20px;
            margin: 5px 8px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.15);
            color: #fff;
            transform: translateX(4px);
        }
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.15) 100%);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }

        /* Mobile Sidebar Toggle */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1039;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .sidebar-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        /* Main content offset */
        .main-content {
            margin-left: 250px;
            transition: margin-left 0.3s ease;
            display: block;
            min-height: 100vh;
            position: relative;
        }

        /* Mobile Responsive */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 4px 0 30px rgba(0, 0, 0, 0.3);
            }
            .sidebar.show {
                transform: translateX(0) !important;
            }
            .sidebar-overlay {
                display: block !important;
            }
            .main-content {
                margin-left: 0;
            }
            .breadcrumb-wrapper {
                padding: 8px 0;
            }
            .breadcrumb {
                font-size: 0.8rem;
            }
        }

        /* Top Navbar */
        .navbar {
            background: white;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
            border-bottom: 1px solid #e5e7eb;
            padding: 14px 20px;
            position: sticky;
            top: 0;
            z-index: 1030;
            width: 100%;
        }
        .navbar .container-fluid {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100%;
            flex-wrap: nowrap !important;
        }
        .navbar .container-fluid > div {
            flex-shrink: 0;
        }
        .navbar-brand {
            font-weight: 700 !important;
            color: #1f2937 !important;
            font-size: 18px !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.3 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        h5.navbar-brand {
            font-size: 18px !important;
            margin-bottom: 0 !important;
        }
        .hamburger-btn {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 8px;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
            position: relative;
            z-index: 1050;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
        }
        .hamburger-btn:hover {
            background: #e5e7eb;
            border-color: var(--digibox-blue);
        }
        .hamburger-btn:active {
            background: #d1d5db;
            transform: scale(0.95);
        }
        .hamburger-btn i {
            font-size: 24px;
            color: var(--digibox-blue);
            pointer-events: none;
            line-height: 1;
        }

        /* User Avatar */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--digibox-blue);
        }
        .user-avatar-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--digibox-blue) 0%, var(--digibox-blue-dark) 100%);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            border: 2px solid #e5e7eb;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        /* User Dropdown Button */
        .user-dropdown-btn {
            background: white !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 50px !important;
            padding: 5px 12px 5px 5px !important;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            white-space: nowrap;
            flex-shrink: 0;
            max-width: 100%;
        }
        .user-dropdown-btn:hover {
            border-color: var(--digibox-blue) !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2) !important;
        }
        .user-dropdown-btn:focus {
            border-color: var(--digibox-blue) !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
        }
        .user-dropdown-btn::after {
            display: none !important;
        }
        .user-dropdown-btn .dropdown-arrow {
            font-size: 10px;
            color: #6b7280;
            margin-left: 0;
            flex-shrink: 0;
        }
        .user-dropdown-btn .fw-semibold {
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 14px;
        }
        .user-dropdown-btn .user-avatar-placeholder {
            flex-shrink: 0 !important;
        }

        /* Dropdown Menu Styling */
        .dropdown-menu {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            padding: 8px;
            min-width: 200px;
            margin-top: 8px !important;
        }
        .dropdown-item {
            border-radius: 8px;
            padding: 10px 14px;
            transition: all 0.2s;
            font-size: 14px;
        }
        .dropdown-item:hover {
            background: #f3f4f6;
            color: var(--digibox-blue);
        }
        .dropdown-item.text-danger:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        .dropdown-divider {
            margin: 8px 0;
        }

        /* Breadcrumb */
        .breadcrumb-wrapper {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 0;
        }
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.875rem;
        }
        .breadcrumb-item {
            color: #6b7280;
        }
        .breadcrumb-item a {
            color: var(--digibox-blue);
            text-decoration: none;
            transition: all 0.2s;
        }
        .breadcrumb-item a:hover {
            color: var(--digibox-blue-dark);
            text-decoration: underline;
        }
        .breadcrumb-item.active {
            color: #1f2937;
            font-weight: 600;
        }
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #9ca3af;
            padding: 0 8px;
        }
        .breadcrumb-item i {
            font-size: 0.875rem;
            margin-right: 4px;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            margin-bottom: 28px;
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }
        @media (hover: hover) and (pointer: fine) {
            .card:hover {
                box-shadow: 0 8px 24px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }
        }
        .card-body {
            padding: 28px;
        }
        .card-title {
            font-weight: 700;
            color: #1f2937;
            font-size: 18px;
        }

        /* Stat Cards with Digibox Theme */
        .stat-card {
            background: linear-gradient(135deg, var(--digibox-blue) 0%, var(--digibox-blue-dark) 100%);
            color: white;
            padding: 28px;
            border-radius: 16px;
            margin-bottom: 28px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        @media (hover: hover) and (pointer: fine) {
            .stat-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 28px rgba(37, 99, 235, 0.35);
            }
        }
        .stat-card h2 {
            font-size: 42px;
            font-weight: 800;
            margin: 12px 0;
        }
        .stat-card h6 {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-card.success {
            background: linear-gradient(135deg, var(--success-green) 0%, #059669 100%);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
        }

        .stat-card.warning {
            background: linear-gradient(135deg, var(--warning-orange) 0%, #d97706 100%);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.25);
        }

        .stat-card.info {
            background: linear-gradient(135deg, var(--info-cyan) 0%, #0891b2 100%);
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
        }

        @media (hover: hover) and (pointer: fine) {
            .stat-card.success:hover {
                box-shadow: 0 12px 28px rgba(16, 185, 129, 0.35);
            }
            .stat-card.warning:hover {
                box-shadow: 0 12px 28px rgba(245, 158, 11, 0.35);
            }
            .stat-card.info:hover {
                box-shadow: 0 12px 28px rgba(6, 182, 212, 0.35);
            }
        }

        /* Performance Metric Cards */
        .performance-metric {
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            padding: 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .performance-metric::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--digibox-blue);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .performance-metric h3 {
            font-size: 32px;
            font-weight: 800;
            color: #1f2937;
        }
        .performance-metric i {
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        @media (hover: hover) and (pointer: fine) {
            .performance-metric:hover {
                border-color: var(--digibox-blue);
                box-shadow: 0 8px 20px rgba(37, 99, 235, 0.15);
                transform: translateY(-3px);
            }
            .performance-metric:hover::before {
                transform: scaleX(1);
            }
            .performance-metric:hover i {
                opacity: 1;
                transform: scale(1.1);
            }
        }

        /* Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 18px 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .alert-success {
            background: linear-gradient(90deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        .alert-warning {
            background: linear-gradient(90deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }
        .alert-info {
            background: linear-gradient(90deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }
        .alert-danger {
            background: linear-gradient(90deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        /* Badges */
        .badge {
            padding: 6px 14px;
            font-weight: 600;
            border-radius: 8px;
            letter-spacing: 0.3px;
        }
        .bg-primary {
            background: var(--digibox-blue) !important;
        }

        /* Progress Bars */
        .progress {
            border-radius: 8px;
            background-color: #e5e7eb;
            overflow: hidden;
        }
        .progress-bar {
            border-radius: 8px;
            background: linear-gradient(90deg, var(--digibox-blue) 0%, var(--digibox-blue-light) 100%);
        }

        /* Top Performers */
        .performer-rank {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--digibox-blue) 0%, var(--digibox-blue-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .performer-rank.gold {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
        }
        .performer-rank.silver {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
            box-shadow: 0 4px 12px rgba(148, 163, 184, 0.4);
        }
        .performer-rank.bronze {
            background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
            box-shadow: 0 4px 12px rgba(251, 146, 60, 0.4);
        }

        .content-wrapper {
            padding: 32px;
            width: 100%;
        }
        .page-title {
            font-size: 32px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 32px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--digibox-blue);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--digibox-blue-dark);
        }

        /* Keyboard Accessibility */
        *:focus-visible {
            outline: 3px solid var(--digibox-blue);
            outline-offset: 2px;
        }
        .btn:focus-visible,
        .nav-link:focus-visible {
            outline: 3px solid rgba(255, 255, 255, 0.8);
            outline-offset: 2px;
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 991px) {
            .content-wrapper {
                padding: 20px 16px;
            }
            .page-title {
                font-size: 24px;
                margin-bottom: 20px;
            }
            .navbar {
                padding: 12px 16px;
            }
            .navbar-brand {
                font-size: 16px !important;
            }
            .user-dropdown-btn {
                padding: 5px 12px 5px 5px !important;
                gap: 6px !important;
            }
            .user-dropdown-btn .fw-semibold {
                font-size: 14px;
                max-width: 120px;
            }
            .user-avatar-placeholder {
                width: 34px;
                height: 34px;
                font-size: 14px;
            }
            .hamburger-btn {
                width: 38px;
                height: 38px;
            }
            .stat-card {
                margin-bottom: 20px;
            }
            .stat-card h2 {
                font-size: 32px;
            }
            .card-body {
                padding: 20px;
            }
            .performance-metric {
                padding: 16px;
                margin-bottom: 12px;
            }
            .performance-metric h3 {
                font-size: 24px;
            }
            .performance-metric i {
                font-size: 32px !important;
            }
        }

        @media (max-width: 767px) {
            .navbar {
                padding: 10px 14px;
            }
            .navbar-brand {
                font-size: 15px !important;
            }
            .user-dropdown-btn {
                padding: 4px 10px 4px 4px !important;
                gap: 5px !important;
            }
            .user-dropdown-btn .fw-semibold {
                font-size: 13px;
                max-width: 100px;
            }
            .user-dropdown-btn .dropdown-arrow {
                font-size: 10px;
            }
            .user-avatar-placeholder {
                width: 32px;
                height: 32px;
                font-size: 13px;
            }
            .hamburger-btn {
                width: 36px;
                height: 36px;
            }
            .hamburger-btn i {
                font-size: 20px;
            }
            .breadcrumb-wrapper {
                padding: 6px 0;
            }
            .breadcrumb {
                font-size: 0.75rem;
            }
            .content-wrapper {
                padding: 16px 12px;
            }
            .page-title {
                font-size: 20px;
                margin-bottom: 16px;
            }
            .stat-card {
                padding: 20px;
                margin-bottom: 16px;
            }
            .stat-card h2 {
                font-size: 28px;
            }
            .stat-card h6 {
                font-size: 13px;
            }
            .card-body {
                padding: 16px;
            }
            .card-title {
                font-size: 16px;
            }
            .performance-metric {
                padding: 14px;
            }
            .performance-metric h3 {
                font-size: 20px;
            }
            .performance-metric i {
                font-size: 28px !important;
                margin-bottom: 8px !important;
            }
            .performance-metric small {
                font-size: 11px;
            }
            .performer-rank {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            .badge {
                font-size: 11px !important;
                padding: 4px 10px !important;
            }
            .alert {
                padding: 14px 16px;
                font-size: 14px;
            }
        }

        @media (max-width: 575px) {
            .navbar {
                padding: 9px 12px;
            }
            .navbar-brand {
                font-size: 14px !important;
            }
            .user-dropdown-btn {
                padding: 4px 8px 4px 4px !important;
                gap: 4px !important;
            }
            .user-dropdown-btn .fw-semibold {
                font-size: 12px;
                max-width: 80px;
            }
            .user-dropdown-btn .dropdown-arrow {
                display: none !important;
            }
            .user-avatar-placeholder {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
            .hamburger-btn {
                width: 34px;
                height: 34px;
                padding: 6px;
            }
            .hamburger-btn i {
                font-size: 18px;
            }
            .breadcrumb-item i {
                display: none;
            }
            .content-wrapper {
                padding: 12px;
            }
            .btn-sm {
                font-size: 12px;
                padding: 4px 8px;
            }
        }

        /* Extra small devices */
        @media (max-width: 400px) {
            .navbar {
                padding: 8px 10px;
            }
            .navbar-brand {
                font-size: 13px !important;
            }
            .user-dropdown-btn .fw-semibold {
                max-width: 60px;
            }
        }

        /* Override Bootstrap gaps on navbar */
        .navbar .d-flex.gap-3 {
            gap: 0.75rem !important;
        }
        .navbar .d-flex.gap-2 {
            gap: 0.5rem !important;
        }

        @media (max-width: 991px) {
            .navbar .d-flex.gap-3 {
                gap: 0.5rem !important;
            }
            .navbar .d-flex.gap-2 {
                gap: 0.4rem !important;
            }
        }

        @media (max-width: 767px) {
            .navbar .d-flex.gap-3 {
                gap: 0.4rem !important;
            }
            .navbar .d-flex.gap-2 {
                gap: 0.3rem !important;
            }
        }

        @media (max-width: 575px) {
            .navbar .d-flex.gap-3 {
                gap: 0.3rem !important;
            }
            .navbar .d-flex.gap-2 {
                gap: 0.25rem !important;
            }
        }
    </style>
</head>
<body>
    @auth('admin')
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <div class="p-4 text-white border-bottom border-white border-opacity-10">
                    <h4 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Digibox</h4>
                    <small class="text-white-50">Rider Tracker</small>
                </div>
                <nav class="nav flex-column px-3 py-3">
                    <a class="nav-link {{ request()->routeIs('dashboard.home') ? 'active' : '' }}" href="{{ route('dashboard.home') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link {{ request()->routeIs('tracking.*') ? 'active' : '' }}" href="{{ route('tracking.index') }}">
                        <i class="bi bi-pin-map-fill"></i> Tracking
                    </a>
                    <a class="nav-link {{ request()->routeIs('riders.*') ? 'active' : '' }}" href="{{ route('riders.index') }}">
                        <i class="bi bi-people-fill"></i> Riders
                    </a>
                    <a class="nav-link {{ request()->routeIs('installations.*') ? 'active' : '' }}" href="{{ route('installations.index') }}">
                        <i class="bi bi-building"></i> Installations
                    </a>

                    <hr class="my-3 border-white border-opacity-10">

                    <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}">
                        <i class="bi bi-person-circle"></i> My Profile
                    </a>
                </nav>

                <!-- Sidebar Footer -->
                <div class="sidebar-footer mt-auto p-3 text-center border-top border-white border-opacity-10">
                    <small class="text-white-50 d-block mb-1">Version 1.0.0</small>
                    <small class="text-white-50">© 2024 Digibox</small>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content flex-fill">
                <!-- Top Navbar -->
                <nav class="navbar navbar-light">
                    <div class="container-fluid">
                        <div class="d-flex align-items-center gap-3">
                            <button
                                class="hamburger-btn d-lg-none"
                                type="button"
                                id="navbarToggler"
                                aria-label="Toggle sidebar menu">
                                <i class="bi bi-list"></i>
                            </button>
                            <h5 class="navbar-brand mb-0">@yield('page-title', 'Dashboard')</h5>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="dropdown">
                                <button class="btn user-dropdown-btn d-flex align-items-center gap-2" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-avatar-placeholder">
                                        {{ strtoupper(substr(Auth::guard('admin')->user()->name, 0, 1)) }}
                                    </div>
                                    <span class="d-none d-md-inline fw-semibold text-dark">{{ Auth::guard('admin')->user()->name }}</span>
                                    <i class="bi bi-chevron-down dropdown-arrow d-none d-md-inline"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                                            <i class="bi bi-person-fill me-2"></i>My Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.change-password') }}">
                                            <i class="bi bi-key-fill me-2"></i>Change Password
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Breadcrumb Navigation -->
                @if(View::hasSection('breadcrumb'))
                <div class="breadcrumb-wrapper">
                    <div class="container-fluid">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                @yield('breadcrumb')
                            </ol>
                        </nav>
                    </div>
                </div>
                @endif

                <!-- Page Content -->
                <div class="content-wrapper">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    @else
        @yield('content')
    @endauth

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Mobile Sidebar Script -->
    <script>
        (function() {
            'use strict';

            // Wait for DOM to be fully loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            function init() {
                const sidebar = document.getElementById('sidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');
                const hamburgerBtn = document.getElementById('navbarToggler');

                console.log('Init - Sidebar:', sidebar);
                console.log('Init - Overlay:', sidebarOverlay);
                console.log('Init - Hamburger:', hamburgerBtn);

                if (!sidebar || !sidebarOverlay) {
                    console.error('Sidebar or overlay not found in DOM!');
                    return;
                }

                // Function to toggle sidebar
                function toggleSidebar(e) {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    console.log('Toggle clicked!');

                    const isShowing = sidebar.classList.contains('show');
                    console.log('Currently showing:', isShowing);

                    if (isShowing) {
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                        document.body.classList.remove('sidebar-open');
                    } else {
                        sidebar.classList.add('show');
                        sidebarOverlay.classList.add('show');
                        document.body.classList.add('sidebar-open');
                    }
                }

                // Function to close sidebar
                function closeSidebar() {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }

                // Attach event to hamburger button
                if (hamburgerBtn) {
                    hamburgerBtn.addEventListener('click', toggleSidebar);
                    console.log('Event listener attached to hamburger');
                }

                // Close menu when clicking overlay
                sidebarOverlay.addEventListener('click', closeSidebar);

                // Close menu when clicking a nav link on mobile
                const navLinks = sidebar.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 992) {
                            closeSidebar();
                        }
                    });
                });

                // Close sidebar when window is resized to desktop
                let resizeTimeout;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(function() {
                        if (window.innerWidth >= 992) {
                            closeSidebar();
                        }
                    }, 150);
                }, {passive: true});

                // Make toggle function globally accessible for inline onclick as fallback
                window.toggleMobileSidebar = toggleSidebar;
            }
        })();
    </script>

    @stack('scripts')
</body>
</html>
