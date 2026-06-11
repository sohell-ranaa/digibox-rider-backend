@extends('layouts.app')

@section('title', 'Edit Profile')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('profile.show') }}"><i class="bi bi-person-circle"></i> My Profile</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-pencil-square"></i> Edit</li>
@endsection

@push('styles')
<style>
    .edit-card {
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

    /* Mobile Responsive Styles */
    @media (max-width: 991px) {
        .edit-card {
            padding: 24px 16px;
        }
        .page-header {
            padding: 20px 16px;
        }
    }

    @media (max-width: 767px) {
        .edit-card {
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
    }

    @media (max-width: 575px) {
        .edit-card {
            padding: 16px 8px;
        }
        .page-header {
            padding: 12px 8px;
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
            <a href="{{ route('profile.show') }}" class="btn btn-light me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-pencil-square text-primary"></i> Edit Profile
                </h4>
                <p class="text-muted mb-0">Update your profile information</p>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="edit-card">
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Name Field -->
                    <div class="mb-4">
                        <label for="name" class="form-label">
                            <i class="bi bi-person-fill text-primary me-2"></i>Full Name
                        </label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $admin->name) }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email Field -->
                    <div class="mb-4">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope-fill text-primary me-2"></i>Email Address
                        </label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email', $admin->email) }}"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Account Info -->
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Account created:</strong> {{ $admin->created_at->format('F d, Y \a\t h:i A') }}
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Save Changes
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
@endsection
