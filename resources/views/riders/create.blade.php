@extends('layouts.app')

@section('title', 'Create New Rider')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('riders.index') }}"><i class="bi bi-people-fill"></i> Riders</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-person-plus"></i> Create</li>
@endsection

@push('styles')
<style>
    .page-header {
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .create-card {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }

    .info-sidebar {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: sticky;
        top: 24px;
    }

    .rider-preview {
        text-align: center;
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 2px solid #f3f4f6;
    }

    .rider-preview-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563EB, #1d4ed8);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        font-weight: 700;
        margin: 0 auto 16px;
        border: 4px solid #eff6ff;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .form-label i {
        color: #2563EB;
        margin-right: 8px;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        padding: 12px 16px;
        transition: all 0.2s;
    }

    .form-control:focus, .form-select:focus {
        border-color: #2563EB;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .form-check-input {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 2px solid #d1d5db;
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: #2563EB;
        border-color: #2563EB;
    }

    .form-check-label {
        font-weight: 600;
        color: #374151;
        margin-left: 8px;
        cursor: pointer;
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

    .password-input-wrapper {
        position: relative;
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

    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f3f4f6;
    }

    .section-title i {
        color: #2563EB;
        margin-right: 8px;
    }

    .info-box {
        background: #eff6ff;
        border-left: 4px solid #2563EB;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 16px;
    }

    .info-box h6 {
        color: #1e40af;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .info-box ul {
        margin-bottom: 0;
        padding-left: 20px;
    }

    .info-box li {
        color: #1f2937;
        margin-bottom: 4px;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 991px) {
        .page-header {
            padding: 20px 16px;
        }
        .create-card {
            padding: 24px 16px;
        }
        .info-sidebar {
            padding: 20px 16px;
            position: static;
            margin-top: 20px;
        }
        .section-title {
            font-size: 1rem;
        }
    }

    @media (max-width: 767px) {
        .page-header {
            padding: 16px 12px;
        }
        .page-header h4 {
            font-size: 1.1rem !important;
        }
        .page-header p {
            font-size: 0.875rem;
        }
        .create-card {
            padding: 20px 12px;
        }
        .info-sidebar {
            padding: 16px 12px;
        }
        .rider-preview-avatar {
            width: 64px;
            height: 64px;
            font-size: 1.5rem;
        }
        .section-title {
            font-size: 0.95rem;
            margin-bottom: 12px;
            padding-bottom: 10px;
        }
        .form-control, .form-select {
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
        .info-box {
            padding: 12px;
            font-size: 0.875rem;
        }
        .info-box h6 {
            font-size: 0.9rem;
        }
    }

    @media (max-width: 575px) {
        .page-header {
            padding: 12px 8px;
        }
        .create-card {
            padding: 16px 8px;
        }
        .info-sidebar {
            padding: 12px 8px;
        }
        .rider-preview-avatar {
            width: 56px;
            height: 56px;
            font-size: 1.25rem;
        }
        .form-control, .form-select {
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
            <a href="{{ route('riders.index') }}" class="btn btn-light me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-person-plus text-primary"></i> Create New Rider
                </h4>
                <p class="text-muted mb-0">Add a new rider to the delivery team</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Create Form -->
        <div class="col-12 col-lg-8">
            <div class="create-card">
                <form action="{{ route('riders.store') }}" method="POST" id="riderForm">
                    @csrf

                    <!-- Basic Information Section -->
                    <div class="section-title">
                        <i class="bi bi-person-badge"></i>Basic Information
                    </div>

                    <div class="row g-3">
                        <!-- Username -->
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label">
                                <i class="bi bi-at"></i>Username <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('username') is-invalid @enderror"
                                   id="username"
                                   name="username"
                                   value="{{ old('username') }}"
                                   required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Used for mobile app login</small>
                        </div>

                        <!-- Full Name -->
                        <div class="col-12 col-md-6">
                            <label for="name" class="form-label">
                                <i class="bi bi-person-fill"></i>Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="section-title mt-4">
                        <i class="bi bi-telephone"></i>Contact Information
                    </div>

                    <div class="row g-3">
                        <!-- Phone -->
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label">
                                <i class="bi bi-telephone-fill"></i>Phone Number
                            </label>
                            <input type="text"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   id="phone"
                                   name="phone"
                                   value="{{ old('phone') }}"
                                   placeholder="e.g., 01712345678">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope-fill"></i>Email Address
                            </label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="e.g., rider@example.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Security Section -->
                    <div class="section-title mt-4">
                        <i class="bi bi-shield-lock"></i>Security Settings
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-key-fill"></i>Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="Enter password"
                                   required>
                            <i class="bi bi-eye toggle-password" onclick="togglePassword('password')"></i>
                        </div>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>

                    <!-- Account Status Section -->
                    <div class="section-title mt-4">
                        <i class="bi bi-toggle-on"></i>Account Status
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox"
                                   name="is_active"
                                   class="form-check-input"
                                   id="is_active"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active Account
                            </label>
                        </div>
                        <small class="text-muted ms-4">Active riders can log in to the mobile app</small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Create Rider
                        </button>
                        <a href="{{ route('riders.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="info-sidebar">
                <!-- Preview -->
                <div class="rider-preview">
                    <div class="rider-preview-avatar" id="avatarPreview">
                        <i class="bi bi-person"></i>
                    </div>
                    <h5 class="fw-bold mb-2" id="namePreview">New Rider</h5>
                    <p class="text-muted mb-0" id="usernamePreview">@username</p>
                </div>

                <!-- Requirements -->
                <div class="info-box">
                    <h6>
                        <i class="bi bi-info-circle-fill me-2"></i>Requirements
                    </h6>
                    <ul>
                        <li>Username must be unique</li>
                        <li>Password minimum 6 characters</li>
                        <li>Phone and email are optional</li>
                        <li>New riders are active by default</li>
                    </ul>
                </div>

                <!-- Quick Tips -->
                <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
                    <h6 style="color: #92400e;">
                        <i class="bi bi-lightbulb-fill me-2"></i>Quick Tips
                    </h6>
                    <ul style="color: #78350f;">
                        <li>Choose memorable usernames</li>
                        <li>Share credentials securely</li>
                        <li>Test login on mobile app</li>
                        <li>Monitor first duty session</li>
                    </ul>
                </div>

                <!-- App Download -->
                <div class="info-box" style="background: #d1fae5; border-left-color: #10b981;">
                    <h6 style="color: #065f46;">
                        <i class="bi bi-phone-fill me-2"></i>Mobile App
                    </h6>
                    <p style="color: #047857; margin-bottom: 0; font-size: 0.875rem;">
                        After creating the rider, share the mobile app download link and login credentials with them.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Live preview update
    document.getElementById('name').addEventListener('input', function() {
        const name = this.value || 'New Rider';
        document.getElementById('namePreview').textContent = name;
        const avatarPreview = document.getElementById('avatarPreview');
        if (this.value) {
            avatarPreview.textContent = name.charAt(0).toUpperCase();
        } else {
            avatarPreview.innerHTML = '<i class="bi bi-person"></i>';
        }
    });

    document.getElementById('username').addEventListener('input', function() {
        const username = this.value || 'username';
        document.getElementById('usernamePreview').textContent = '@' + username;
    });

    // Toggle password visibility
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
