@extends('layouts.app')

@section('title', 'My Profile')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-person-circle"></i> My Profile</li>
@endsection

@push('styles')
<style>
    .profile-header {
        background: linear-gradient(135deg, #2563EB, #1d4ed8);
        border-radius: 12px;
        padding: 40px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: white;
        color: #2563EB;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 700;
        margin: 0 auto 20px;
        border: 5px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }

    .info-card {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }

    .info-item {
        display: flex;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-item i {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #2563EB;
        font-size: 18px;
        margin-right: 16px;
    }

    .info-item .label {
        font-weight: 600;
        color: #6b7280;
        min-width: 120px;
    }

    .info-item .value {
        font-weight: 700;
        color: #1f2937;
        font-size: 1.1rem;
    }

    .action-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s;
        border: 2px solid transparent;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        border-color: #2563EB;
    }

    .action-card .icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 16px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .status-badge.active {
        background: #d1fae5;
        color: #065f46;
    }

    .status-badge .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    }

    /* Mobile Responsive Styles */
    @media (max-width: 991px) {
        .profile-header {
            padding: 30px 20px;
        }
        .info-card {
            padding: 24px 16px;
        }
        .action-card {
            padding: 20px 16px;
            margin-bottom: 16px;
        }
        .action-card .icon-wrapper {
            width: 52px;
            height: 52px;
            font-size: 24px;
        }
    }

    @media (max-width: 767px) {
        .profile-header {
            padding: 24px 16px;
        }
        .profile-header h2 {
            font-size: 1.5rem;
        }
        .profile-header p {
            font-size: 0.9rem;
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            font-size: 2.5rem;
        }
        .info-card {
            padding: 20px 12px;
        }
        .info-item {
            flex-direction: column;
            align-items: flex-start;
            padding: 12px 0;
        }
        .info-item i {
            width: 36px;
            height: 36px;
            font-size: 16px;
            margin-right: 0;
            margin-bottom: 8px;
        }
        .info-item .label {
            font-size: 0.875rem;
            min-width: auto;
            margin-bottom: 4px;
        }
        .info-item .value {
            font-size: 1rem;
        }
        .action-card {
            padding: 16px 12px;
        }
        .action-card .icon-wrapper {
            width: 48px;
            height: 48px;
            font-size: 20px;
        }
        .action-card h5 {
            font-size: 1.1rem;
        }
        .action-card p {
            font-size: 0.875rem;
        }
    }

    @media (max-width: 575px) {
        .profile-header {
            padding: 20px 12px;
        }
        .profile-header h2 {
            font-size: 1.25rem;
        }
        .profile-avatar {
            width: 70px;
            height: 70px;
            font-size: 2rem;
        }
        .info-card {
            padding: 16px 8px;
        }
        .status-badge {
            padding: 6px 12px;
            font-size: 0.75rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Success Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            {{ strtoupper(substr($admin->name, 0, 1)) }}
        </div>
        <h2 class="text-center fw-bold mb-2">{{ $admin->name }}</h2>
        <p class="text-center mb-3" style="opacity: 0.9;">{{ $admin->email }}</p>
        <div class="text-center">
            @if($admin->is_active)
                <span class="status-badge active">
                    <span class="dot"></span>
                    Active Account
                </span>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <!-- Profile Information Card -->
        <div class="col-12 col-lg-7">
            <div class="info-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-person-circle text-primary me-2"></i>
                        Profile Information
                    </h5>
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil-fill"></i> Edit Profile
                    </a>
                </div>

                <div class="info-item">
                    <i class="bi bi-person-fill"></i>
                    <span class="label">Full Name:</span>
                    <span class="value">{{ $admin->name }}</span>
                </div>

                <div class="info-item">
                    <i class="bi bi-envelope-fill"></i>
                    <span class="label">Email Address:</span>
                    <span class="value">{{ $admin->email }}</span>
                </div>

                <div class="info-item">
                    <i class="bi bi-shield-check"></i>
                    <span class="label">Account Status:</span>
                    <span class="value text-success">
                        {{ $admin->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="info-item">
                    <i class="bi bi-calendar-check"></i>
                    <span class="label">Member Since:</span>
                    <span class="value">{{ $admin->created_at->format('M d, Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-12 col-lg-5">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-lightning-fill text-warning me-2"></i>
                Quick Actions
            </h5>

            <div class="row g-3">
                <div class="col-12">
                    <a href="{{ route('profile.edit') }}" class="text-decoration-none">
                        <div class="action-card">
                            <div class="icon-wrapper" style="background: linear-gradient(135deg, #2563EB, #1d4ed8); color: white;">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <h6 class="fw-bold mb-2">Edit Profile</h6>
                            <p class="text-muted mb-0 small">Update your name and email address</p>
                        </div>
                    </a>
                </div>

                <div class="col-12">
                    <a href="{{ route('profile.change-password') }}" class="text-decoration-none">
                        <div class="action-card">
                            <div class="icon-wrapper" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                <i class="bi bi-key-fill"></i>
                            </div>
                            <h6 class="fw-bold mb-2">Change Password</h6>
                            <p class="text-muted mb-0 small">Update your account password for security</p>
                        </div>
                    </a>
                </div>

                <div class="col-12">
                    <a href="{{ route('dashboard.home') }}" class="text-decoration-none">
                        <div class="action-card">
                            <div class="icon-wrapper" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                                <i class="bi bi-speedometer2"></i>
                            </div>
                            <h6 class="fw-bold mb-2">Go to Dashboard</h6>
                            <p class="text-muted mb-0 small">Return to main dashboard</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
