@extends('layouts.app')

@section('title', 'Riders Management')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-people-fill"></i> Riders</li>
@endsection

@push('styles')
<style>
    /* Riders Page Mobile Optimized v2.0 */
    :root {
        --digibox-blue: #2563EB;
        --success-green: #10b981;
        --warning-orange: #f59e0b;
        --danger-red: #ef4444;
    }

    /* Ensure proper mobile rendering */
    * {
        box-sizing: border-box;
    }

    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
        padding-left: 12px;
        padding-right: 12px;
    }

    @media (min-width: 768px) {
        .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
        }
    }

    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    /* Touch-friendly button sizes */
    .form-control,
    .form-select,
    .btn {
        min-height: 44px;
    }

    @media (hover: hover) and (pointer: fine) {
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
    }

    .stats-card .icon-wrapper {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .rider-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s;
        border: 2px solid transparent;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        margin-bottom: 0;
    }

    /* Desktop: Horizontal layout */
    @media (min-width: 768px) {
        .rider-card {
            flex-direction: row !important;
        }
    }

    @media (hover: hover) and (pointer: fine) {
        .rider-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-color: var(--digibox-blue);
        }
    }

    .rider-card .card-header-custom {
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        padding: 20px 16px;
        text-align: center;
        border-bottom: 2px solid #e5e7eb;
        border-right: none;
        min-width: auto;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    /* Desktop: Sidebar layout */
    @media (min-width: 768px) {
        .rider-card .card-header-custom {
            border-bottom: none;
            border-right: 2px solid #e5e7eb !important;
            min-width: 180px;
            width: 180px;
            padding: 24px 16px;
        }
    }

    .rider-card .rider-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--digibox-blue), #1d4ed8);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        font-size: 1.6rem;
        color: white;
        font-weight: 700;
        position: relative;
        flex-shrink: 0;
    }

    /* Desktop: Larger avatar */
    @media (min-width: 768px) {
        .rider-card .rider-avatar {
            width: 72px;
            height: 72px;
            font-size: 1.8rem;
            margin-bottom: 12px;
        }
    }

    .online-indicator {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 3px solid white;
        animation: pulse 2s infinite;
    }

    .online-indicator.online {
        background: #10b981;
        box-shadow: 0 0 12px rgba(16, 185, 129, 0.8);
    }

    .online-indicator.offline {
        background: #9ca3af;
        animation: none;
    }

    /* Desktop: Larger online indicator */
    @media (min-width: 768px) {
        .online-indicator {
            width: 18px;
            height: 18px;
        }
    }

    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.8);
        }
        50% {
            box-shadow: 0 0 20px rgba(16, 185, 129, 1);
        }
    }

    .rider-card .rider-name {
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
        line-height: 1.3;
        word-break: break-word;
    }

    .rider-card .rider-username {
        font-size: 0.8rem;
        color: #6b7280;
        font-family: 'Courier New', monospace;
        letter-spacing: -0.5px;
        word-break: break-word;
    }

    /* Desktop: Larger text in cards */
    @media (min-width: 768px) {
        .rider-card .rider-name {
            font-size: 1.1rem;
        }

        .rider-card .rider-username {
            font-size: 0.85rem;
        }
    }

    .rider-card .card-body {
        padding: 18px 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0; /* Allow flex children to shrink */
    }

    .rider-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: auto;
    }

    /* Desktop: Better spacing and layout */
    @media (min-width: 768px) {
        .rider-card .card-body {
            padding: 20px 24px;
        }

        .rider-info-grid {
            gap: 16px 20px;
        }
    }

    /* Large Desktop: 4 columns for info items */
    @media (min-width: 1200px) {
        .rider-info-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 16px 24px;
        }
    }

    .rider-info-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0; /* Allow text to wrap */
    }

    .rider-info-item .label {
        font-size: 0.7rem;
        color: #9ca3af;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }

    .rider-info-item .label i {
        color: var(--digibox-blue);
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    .rider-info-item .value {
        font-weight: 700;
        color: #1f2937;
        font-size: 0.875rem;
        word-wrap: break-word;
        overflow-wrap: break-word;
        line-height: 1.3;
    }

    /* Desktop: Larger info text */
    @media (min-width: 768px) {
        .rider-info-item .label {
            font-size: 0.75rem;
        }

        .rider-info-item .label i {
            font-size: 0.8rem;
        }

        .rider-info-item .value {
            font-size: 0.95rem;
        }
    }

    .action-buttons {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #e5e7eb;
    }

    .btn-action {
        padding: 11px 18px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    /* Desktop: Better spacing and sizing */
    @media (min-width: 768px) {
        .action-buttons {
            margin-top: 18px;
            padding-top: 18px;
        }

        .btn-action {
            padding: 12px 24px;
            font-size: 0.9rem;
        }
    }

    @media (hover: hover) and (pointer: fine) {
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
        }
    }

    .search-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .empty-state i {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 16px;
    }

    .rider-item {
        display: flex;
    }

    .status-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
        flex-shrink: 0;
        vertical-align: middle;
    }

    .status-dot.active {
        background: var(--success-green);
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
    }

    .status-dot.inactive {
        background: #9ca3af;
    }

    .duty-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .duty-badge.on-duty {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: #065f46;
        border: 1px solid #6ee7b7;
    }

    .duty-badge.off-duty {
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .duty-badge.on-duty-offline {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #92400e;
        border: 1px solid #fbbf24;
    }

    .duty-badge i {
        font-size: 10px;
    }

    .stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 700;
        font-size: 0.85rem;
    }

    .stat-badge i {
        flex-shrink: 0;
        font-size: 0.75rem;
    }

    .stat-badge.success {
        color: var(--success-green);
    }

    .stat-badge.warning {
        color: var(--warning-orange);
    }

    .stat-badge.info {
        color: var(--info-cyan);
    }

    .last-seen-text {
        font-size: 0.75rem;
        color: #9ca3af;
        font-style: italic;
    }

    /* Additional Tablet Responsive Styles */
    @media (max-width: 991px) {
        .search-section {
            padding: 16px;
        }
        .stats-card {
            padding: 16px;
        }
        .stats-card h3 {
            font-size: 1.75rem;
        }
    }

    /* Force column layout on mobile/tablet only */
    @media (max-width: 767px) {
        .rider-card {
            flex-direction: column !important;
        }
        .rider-card .card-header-custom {
            border-right: none !important;
            border-bottom: 2px solid #e5e7eb !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            padding: 20px 16px !important;
        }
        .rider-card .card-body {
            width: 100% !important;
        }
        .rider-card .rider-avatar {
            width: 56px;
            height: 56px;
            font-size: 1.3rem;
        }
        .online-indicator {
            width: 14px;
            height: 14px;
        }
        .rider-card .rider-name {
            font-size: 1rem !important;
        }
        .rider-card .rider-username {
            font-size: 0.8rem !important;
        }
    }

    @media (max-width: 767px) {
        /* Page Header */
        .page-header-mobile {
            margin-bottom: 20px !important;
        }
        .page-header-mobile h4 {
            font-size: 1.25rem !important;
            margin-bottom: 8px !important;
        }
        .page-header-mobile .text-muted {
            font-size: 0.85rem !important;
        }
        .page-header-mobile .btn {
            width: 100%;
            margin-top: 12px;
            padding: 12px 20px;
            font-size: 0.95rem;
        }

        /* Search Section */
        .search-section {
            padding: 14px;
        }
        .search-section .input-group-text,
        .search-section .form-control,
        .search-section .form-select {
            font-size: 0.9rem;
            padding: 10px 14px;
        }
        .search-section .btn {
            padding: 10px 14px;
            font-size: 0.9rem;
        }

        /* Stats Cards */
        .stats-card {
            padding: 14px;
            margin-bottom: 12px;
        }
        .stats-card h3 {
            font-size: 1.5rem;
        }
        .stats-card .small {
            font-size: 0.75rem;
        }
        .stats-card .icon-wrapper {
            width: 42px;
            height: 42px;
            font-size: 20px;
        }

        /* Rider Cards - Mobile */
        .rider-card {
            flex-direction: column !important;
        }
        .rider-card .card-body {
            padding: 16px !important;
            width: 100% !important;
        }
        .rider-card .card-header-custom {
            padding: 16px !important;
            width: 100% !important;
            min-width: 100% !important;
            border-right: none !important;
            border-bottom: 2px solid #e5e7eb !important;
        }
        .rider-card .rider-avatar {
            width: 54px !important;
            height: 54px !important;
            font-size: 1.25rem !important;
        }
        .rider-card .rider-name {
            font-size: 0.95rem !important;
        }
        .rider-card .rider-username {
            font-size: 0.75rem !important;
        }
        .rider-info-grid {
            gap: 10px;
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .rider-info-item .label {
            font-size: 0.65rem !important;
        }
        .rider-info-item .label i {
            font-size: 0.7rem !important;
        }
        .rider-info-item .value {
            font-size: 0.8rem !important;
        }
        .btn-action {
            padding: 10px 16px;
            font-size: 0.875rem;
        }
        .online-indicator {
            width: 14px !important;
            height: 14px !important;
        }
    }

    @media (max-width: 575px) {
        /* Page Header */
        .page-header-mobile h4 {
            font-size: 1.1rem !important;
        }
        .page-header-mobile .text-muted {
            font-size: 0.8rem !important;
            display: none; /* Hide subtitle on very small screens */
        }
        .page-header-mobile .btn {
            padding: 10px 16px;
            font-size: 0.875rem;
        }

        /* Search Section - Stack all filters */
        .search-section {
            padding: 12px;
        }
        .search-section .row > div {
            margin-bottom: 8px;
        }
        .search-section .row > div:last-child {
            margin-bottom: 0;
        }

        /* Stats Cards */
        .stats-card {
            padding: 10px 12px;
        }
        .stats-card h3 {
            font-size: 1.4rem;
        }
        .stats-card .small {
            font-size: 0.7rem;
            line-height: 1.2;
        }
        .stats-card .icon-wrapper {
            width: 36px;
            height: 36px;
            font-size: 17px;
            flex-shrink: 0;
        }

        /* Rider Cards - Mobile */
        .rider-card {
            margin-bottom: 0;
            flex-direction: column !important;
            display: flex !important;
        }
        .rider-card .card-header-custom {
            padding: 14px 12px !important;
            border-right: none !important;
            border-bottom: 2px solid #e5e7eb !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            flex-direction: column !important;
        }
        .rider-card .card-body {
            padding: 14px 12px !important;
            width: 100% !important;
            flex: 1;
        }
        .rider-card .rider-avatar {
            width: 50px;
            height: 50px;
            font-size: 1.15rem;
            margin: 0 auto 8px auto !important;
            border-width: 2px;
        }
        .rider-card .rider-name {
            font-size: 0.9rem;
            margin-bottom: 4px;
            text-align: center;
        }
        .rider-card .rider-username {
            font-size: 0.75rem;
            text-align: center;
        }
        .rider-info-grid {
            gap: 10px;
            grid-template-columns: 1fr 1fr !important;
            display: grid !important;
        }
        .rider-info-item {
            gap: 5px;
        }
        .rider-info-item .label {
            font-size: 0.62rem;
            letter-spacing: 0.2px;
            gap: 3px;
        }
        .rider-info-item .label i {
            font-size: 0.65rem;
        }
        .rider-info-item .value {
            font-size: 0.82rem;
            word-break: break-word;
        }
        .action-buttons {
            margin-top: 12px;
            padding-top: 12px;
        }
        .btn-action {
            padding: 11px 16px;
            font-size: 0.875rem;
        }
        .online-indicator {
            width: 13px;
            height: 13px;
            border-width: 2px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
        }

        /* Empty State */
        .empty-state {
            padding: 40px 16px;
        }
        .empty-state i {
            font-size: 3rem;
        }
        .empty-state h5 {
            font-size: 1rem;
        }
        .empty-state p {
            font-size: 0.85rem;
        }

        /* Pagination */
        .pagination {
            font-size: 0.875rem;
        }
        .pagination .page-link {
            padding: 8px 12px;
        }
    }

    /* Extra small screens */
    @media (max-width: 400px) {
        .stats-card h3 {
            font-size: 1.25rem;
        }
        .stats-card .icon-wrapper {
            width: 34px;
            height: 34px;
            font-size: 16px;
        }

        /* Force column layout for very small screens */
        .rider-card {
            flex-direction: column !important;
        }
        .rider-card .card-header-custom {
            padding: 12px 10px !important;
            border-right: none !important;
            border-bottom: 2px solid #e5e7eb !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
        }
        .rider-card .card-body {
            padding: 12px 10px !important;
            width: 100% !important;
        }
        .rider-card .rider-avatar {
            width: 46px !important;
            height: 46px !important;
            font-size: 1.05rem !important;
            margin: 0 auto 6px auto !important;
        }
        .rider-card .rider-name {
            font-size: 0.875rem !important;
        }
        .rider-card .rider-username {
            font-size: 0.7rem !important;
        }
        .rider-info-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 8px !important;
        }
        .rider-info-item .label {
            font-size: 0.58rem !important;
        }
        .rider-info-item .label i {
            font-size: 0.6rem !important;
        }
        .rider-info-item .value {
            font-size: 0.75rem !important;
        }
        .btn-action {
            padding: 10px 14px !important;
            font-size: 0.825rem !important;
        }
        .online-indicator {
            width: 12px !important;
            height: 12px !important;
        }
    }

    /* Universal mobile card fix - mobile and tablet only */
    @media (max-width: 767px) {
        .rider-card {
            flex-direction: column !important;
            max-width: 100%;
        }
        .rider-card .card-header-custom {
            border-right: 0 !important;
            border-bottom: 2px solid #e5e7eb !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
        }
        .rider-card .card-body {
            width: 100% !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4 page-header-mobile">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center">
                <div class="mb-3 mb-md-0">
                    <h4 class="fw-bold mb-2">
                        <i class="bi bi-people-fill text-primary"></i> Riders Management
                    </h4>
                    <p class="text-muted mb-0">Manage delivery riders and their accounts</p>
                </div>
                <a href="{{ route('riders.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Rider
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4 g-2 g-md-3">
        <div class="col-6 col-md-4 col-lg-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Total Riders</p>
                        <h3 class="mb-0 fw-bold">{{ $riders->total() }}</h3>
                    </div>
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #2563EB, #1d4ed8); color: white;">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Active</p>
                        <h3 class="mb-0 fw-bold">{{ $riders->where('is_active', true)->count() }}</h3>
                    </div>
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Inactive</p>
                        <h3 class="mb-0 fw-bold">{{ $riders->where('is_active', false)->count() }}</h3>
                    </div>
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white;">
                        <i class="bi bi-pause-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Online</p>
                        <h3 class="mb-0 fw-bold">{{ $riders->where('is_online', true)->count() }}</h3>
                    </div>
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                        <i class="bi bi-wifi"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Working</p>
                        <h3 class="mb-0 fw-bold">{{ $riders->where('is_on_duty', true)->count() }}</h3>
                    </div>
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Section -->
    <div class="search-section">
        <div class="row g-2">
            <div class="col-12">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-0 bg-light" id="searchInput" placeholder="Search by name, username, phone, or email...">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <select class="form-select border-0 bg-light" id="statusFilter">
                    <option value="">Account Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <select class="form-select border-0 bg-light" id="onlineFilter">
                    <option value="">Connection</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <select class="form-select border-0 bg-light" id="dutyFilter">
                    <option value="">Work Status</option>
                    <option value="on-duty">Working</option>
                    <option value="off-duty">Not Working</option>
                </select>
            </div>
            <div class="col-6 col-md-12 col-lg-3">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    <i class="bi bi-arrow-clockwise"></i> Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Riders Grid -->
    @if($riders->count() > 0)
    <div class="row g-2 g-md-3" id="ridersGrid">
        @foreach($riders as $rider)
        <div class="col-12 col-lg-6 col-xl-4 rider-item"
             data-name="{{ strtolower($rider->name) }}"
             data-username="{{ strtolower($rider->username) }}"
             data-phone="{{ strtolower($rider->phone ?? '') }}"
             data-email="{{ strtolower($rider->email ?? '') }}"
             data-status="{{ $rider->is_active ? 'active' : 'inactive' }}"
             data-online="{{ $rider->is_online ? 'online' : 'offline' }}"
             data-duty="{{ $rider->is_on_duty ? 'on-duty' : 'off-duty' }}">
            <div class="rider-card">
                <!-- Card Header with Avatar -->
                <div class="card-header-custom">
                    <div class="rider-avatar">
                        {{ strtoupper(substr($rider->name, 0, 1)) }}
                        <!-- Online Indicator -->
                        <span class="online-indicator {{ $rider->is_online ? 'online' : 'offline' }}"
                              title="{{ $rider->is_online ? 'Online' : 'Offline' }}"></span>
                    </div>
                    <div class="rider-name">{{ $rider->name }}</div>
                    <div class="rider-username">{{ '@' . $rider->username }}</div>
                </div>

                <!-- Card Body -->
                <div class="card-body">
                    <div class="rider-info-grid">
                        <!-- Account Status -->
                        <div class="rider-info-item">
                            <span class="label">
                                <i class="bi bi-shield-check"></i> Account
                            </span>
                            <span class="value">
                                @if($rider->is_active)
                                    <span class="status-dot active"></span> Active
                                @else
                                    <span class="status-dot inactive"></span> Inactive
                                @endif
                            </span>
                        </div>

                        <!-- Last Seen -->
                        <div class="rider-info-item">
                            <span class="label">
                                <i class="bi bi-clock"></i> Last Seen
                            </span>
                            <span class="value">
                                @if($rider->last_seen)
                                    {{ $rider->last_seen->format('M d, h:i A') }}
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </span>
                        </div>

                        <!-- Weekly Hours -->
                        <div class="rider-info-item">
                            <span class="label">
                                <i class="bi bi-calendar-week"></i> This Week
                            </span>
                            <span class="value stat-badge success">
                                <i class="bi bi-clock-fill"></i>
                                {{ number_format($rider->weekly_hours, 1) }}h
                            </span>
                        </div>

                        <!-- Connection Status -->
                        <div class="rider-info-item">
                            <span class="label">
                                <i class="bi bi-wifi"></i> Connection
                            </span>
                            <span class="value">
                                @if($rider->is_online)
                                    <span class="status-dot active"></span> Online
                                @else
                                    <span class="status-dot inactive"></span> Offline
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="{{ route('riders.show', $rider) }}" class="btn btn-action btn-primary w-100">
                            <i class="bi bi-eye-fill"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-3 mt-md-4 d-flex justify-content-center">
        {{ $riders->links() }}
    </div>

    @else
    <!-- Empty State -->
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <h5 class="fw-bold mb-2">No Riders Found</h5>
        <p class="text-muted mb-4">Get started by adding your first rider</p>
        <a href="{{ route('riders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Rider
        </a>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter elements
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const onlineFilter = document.getElementById('onlineFilter');
    const dutyFilter = document.getElementById('dutyFilter');
    const riderItems = document.querySelectorAll('.rider-item');

    function filterRiders() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        const onlineValue = onlineFilter.value;
        const dutyValue = dutyFilter.value;

        let visibleCount = 0;

        riderItems.forEach(item => {
            const name = item.dataset.name;
            const username = item.dataset.username;
            const phone = item.dataset.phone;
            const email = item.dataset.email;
            const status = item.dataset.status;
            const online = item.dataset.online;
            const duty = item.dataset.duty;

            // Search in name, username, phone, and email
            const matchesSearch = name.includes(searchTerm) ||
                                  username.includes(searchTerm) ||
                                  phone.includes(searchTerm) ||
                                  email.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const matchesOnline = !onlineValue || online === onlineValue;
            const matchesDuty = !dutyValue || duty === dutyValue;

            if (matchesSearch && matchesStatus && matchesOnline && matchesDuty) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        console.log(`Showing ${visibleCount} riders`);
    }

    // Add event listeners
    if (searchInput) searchInput.addEventListener('input', filterRiders);
    if (statusFilter) statusFilter.addEventListener('change', filterRiders);
    if (onlineFilter) onlineFilter.addEventListener('change', filterRiders);
    if (dutyFilter) dutyFilter.addEventListener('change', filterRiders);

    // Make resetFilters function globally available
    window.resetFilters = function() {
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        if (onlineFilter) onlineFilter.value = '';
        if (dutyFilter) dutyFilter.value = '';
        filterRiders();
    }
});
</script>
@endpush
@endsection
