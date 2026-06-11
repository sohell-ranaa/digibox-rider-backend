@extends('layouts.app')

@section('title', 'Edit Rider')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('riders.index') }}"><i class="bi bi-people-fill"></i> Riders</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-pencil-square"></i> Edit: {{ $rider->name }}</li>
@endsection

@push('styles')
<style>
    .page-header {
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .edit-card {
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

    .stat-item {
        padding: 16px;
        background: linear-gradient(135deg, #f9fafb, #ffffff);
        border-radius: 10px;
        margin-bottom: 12px;
        border: 2px solid #f3f4f6;
        transition: all 0.2s;
    }

    .stat-item:hover {
        border-color: #2563EB;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
    }

    .stat-item .icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-right: 12px;
    }

    .stat-item .label {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 4px;
    }

    .stat-item .value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
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

    .status-badge.inactive {
        background: #f3f4f6;
        color: #6b7280;
    }

    .status-badge .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .status-badge.active .dot {
        background: #10b981;
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    }

    .status-badge.inactive .dot {
        background: #9ca3af;
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

    /* Mobile Responsive Styles */
    @media (max-width: 991px) {
        .page-header {
            padding: 20px 16px;
        }
        .edit-card {
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
        .stat-item {
            padding: 12px;
        }
        .stat-item .icon {
            width: 36px;
            height: 36px;
            font-size: 16px;
        }
        .stat-item .value {
            font-size: 1.1rem;
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
        .edit-card {
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
        .stat-item {
            padding: 10px;
            margin-bottom: 10px;
        }
        .stat-item .icon {
            width: 32px;
            height: 32px;
            font-size: 14px;
            margin-right: 10px;
        }
        .stat-item .label {
            font-size: 0.75rem;
        }
        .stat-item .value {
            font-size: 1rem;
        }
        .status-badge {
            padding: 6px 12px;
            font-size: 0.75rem;
        }
    }

    @media (max-width: 575px) {
        .page-header {
            padding: 12px 8px;
        }
        .edit-card {
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
        .stat-item {
            padding: 8px;
        }
        .stat-item .icon {
            width: 28px;
            height: 28px;
            font-size: 12px;
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
                    <i class="bi bi-pencil-square text-primary"></i> Edit Rider
                </h4>
                <p class="text-muted mb-0">Update rider information and settings</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Edit Form -->
        <div class="col-12 col-lg-8">
            <div class="edit-card">
                <form action="{{ route('riders.update', $rider) }}" method="POST" id="riderForm">
                    @csrf
                    @method('PUT')

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
                                   value="{{ old('username', $rider->username) }}"
                                   required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                   value="{{ old('name', $rider->name) }}"
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
                                   value="{{ old('phone', $rider->phone) }}"
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
                                   value="{{ old('email', $rider->email) }}"
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
                            <i class="bi bi-key-fill"></i>Password
                            <small class="text-muted">(Leave blank to keep current password)</small>
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="Enter new password">
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
                                   {{ old('is_active', $rider->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active Account
                            </label>
                        </div>
                        <small class="text-muted ms-4">Inactive riders cannot log in to the mobile app</small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Update Rider
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
                <!-- Rider Preview -->
                <div class="rider-preview">
                    <div class="rider-preview-avatar" id="avatarPreview">
                        {{ strtoupper(substr($rider->name, 0, 1)) }}
                    </div>
                    <h5 class="fw-bold mb-2" id="namePreview">{{ $rider->name }}</h5>
                    <p class="text-muted mb-2" id="usernamePreview">{{ '@' . $rider->username }}</p>
                    @if($rider->is_active)
                        <span class="status-badge active">
                            <span class="dot"></span>
                            Active
                        </span>
                    @else
                        <span class="status-badge inactive">
                            <span class="dot"></span>
                            Inactive
                        </span>
                    @endif
                </div>

                <!-- Statistics -->
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i>Statistics
                </h6>

                <div class="stat-item">
                    <div class="d-flex align-items-center">
                        <div class="icon" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #2563EB;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <div class="label">Total Duty Sessions</div>
                            <div class="value">{{ $rider->duty_sessions_count ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                @if($latestSession)
                <div class="stat-item">
                    <div class="d-flex align-items-center">
                        <div class="icon" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div>
                            <div class="label">Last Duty Session</div>
                            <div class="value" style="font-size: 0.9rem;">
                                {{ $latestSession->started_at->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="stat-item">
                    <div class="d-flex align-items-center">
                        <div class="icon" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div>
                            <div class="label">Member Since</div>
                            <div class="value" style="font-size: 0.9rem;">
                                {{ $rider->created_at->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Last Updated:</strong><br>
                    {{ $rider->updated_at->format('F d, Y \a\t h:i A') }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Live preview update
    document.getElementById('name').addEventListener('input', function() {
        const name = this.value || '{{ $rider->name }}';
        document.getElementById('namePreview').textContent = name;
        document.getElementById('avatarPreview').textContent = name.charAt(0).toUpperCase();
    });

    document.getElementById('username').addEventListener('input', function() {
        const username = this.value || '{{ $rider->username }}';
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
