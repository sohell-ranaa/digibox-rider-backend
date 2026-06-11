@extends('layouts.app')

@section('title', 'Change Password')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('profile.show') }}"><i class="bi bi-person-circle"></i> My Profile</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-key-fill"></i> Change Password</li>
@endsection

@push('styles')
<style>
    .password-card {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        padding: 12px 16px;
        transition: all 0.2s;
    }

    .form-control:focus {
        border-color: #2563EB;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .password-requirements {
        background: #f3f4f6;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
    }

    .password-requirements ul {
        margin: 0;
        padding-left: 20px;
    }

    .password-requirements li {
        color: #6b7280;
        margin-bottom: 4px;
    }

    .btn-primary {
        padding: 12px 32px;
        border-radius: 8px;
        font-weight: 600;
    }

    .btn-secondary {
        padding: 12px 32px;
        border-radius: 8px;
        font-weight: 600;
    }

    .page-header {
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .toggle-password {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6b7280;
        transition: color 0.2s;
    }

    .toggle-password:hover {
        color: #2563EB;
    }

    .password-input-wrapper {
        position: relative;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 991px) {
        .password-card {
            padding: 24px 16px;
        }
        .page-header {
            padding: 20px 16px;
        }
        .password-requirements {
            padding: 12px;
        }
    }

    @media (max-width: 767px) {
        .password-card {
            padding: 20px 12px;
        }
        .page-header {
            padding: 16px 12px;
        }
        .page-header h4 {
            font-size: 1.1rem !important;
        }
        .page-header p {
            font-size: 0.875rem;
        }
        .password-requirements {
            padding: 10px;
            font-size: 0.875rem;
        }
        .password-requirements h6 {
            font-size: 0.9rem;
        }
        .password-requirements ul {
            padding-left: 16px;
        }
        .form-control {
            padding: 10px 14px;
            font-size: 0.9rem;
        }
        .form-label {
            font-size: 0.875rem;
        }
        .btn-primary, .btn-secondary {
            width: 100%;
            padding: 10px 20px;
            font-size: 0.9rem;
        }
        .d-flex.gap-3 {
            flex-direction: column;
        }
    }

    @media (max-width: 575px) {
        .password-card {
            padding: 16px 8px;
        }
        .page-header {
            padding: 12px 8px;
        }
        .password-requirements {
            padding: 8px;
            font-size: 0.8rem;
        }
        .form-control {
            padding: 8px 12px;
            font-size: 0.85rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex align-items-center">
            <a href="{{ route('profile.show') }}" class="btn btn-light me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-key-fill text-success"></i> Change Password
                </h4>
                <p class="text-muted mb-0">Update your account password</p>
            </div>
        </div>
    </div>

    <!-- Change Password Form -->
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="password-card">
                <!-- Password Requirements -->
                <div class="password-requirements">
                    <h6 class="fw-bold mb-2">
                        <i class="bi bi-shield-check text-success me-2"></i>Password Requirements:
                    </h6>
                    <ul>
                        <li>Minimum 8 characters long</li>
                        <li>Must be different from current password</li>
                        <li>Confirm password must match new password</li>
                    </ul>
                </div>

                <form action="{{ route('profile.update-password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Current Password -->
                    <div class="mb-4">
                        <label for="current_password" class="form-label">
                            <i class="bi bi-lock-fill text-primary me-2"></i>Current Password
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password"
                                   name="current_password"
                                   required>
                            <i class="bi bi-eye toggle-password" onclick="togglePassword('current_password')"></i>
                        </div>
                        @error('current_password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div class="mb-4">
                        <label for="new_password" class="form-label">
                            <i class="bi bi-key-fill text-success me-2"></i>New Password
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password"
                                   class="form-control @error('new_password') is-invalid @enderror"
                                   id="new_password"
                                   name="new_password"
                                   required>
                            <i class="bi bi-eye toggle-password" onclick="togglePassword('new_password')"></i>
                        </div>
                        @error('new_password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mb-4">
                        <label for="new_password_confirmation" class="form-label">
                            <i class="bi bi-key-fill text-success me-2"></i>Confirm New Password
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password"
                                   class="form-control"
                                   id="new_password_confirmation"
                                   name="new_password_confirmation"
                                   required>
                            <i class="bi bi-eye toggle-password" onclick="togglePassword('new_password_confirmation')"></i>
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Security Notice:</strong> After changing your password, you will remain logged in on this device.
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Update Password
                        </button>
                        <a href="{{ route('profile.show') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.parentElement.querySelector('.toggle-password');

        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>
@endpush
@endsection
