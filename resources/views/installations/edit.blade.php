@extends('layouts.app')

@section('title', 'Edit Installation Location')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('installations.index') }}"><i class="bi bi-building"></i> Installations</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-pencil-square"></i> Edit: {{ $installation->location_name }}</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    :root {
        --digibox-blue: #2563EB;
        --success-green: #10b981;
        --danger-red: #ef4444;
    }

    .page-header {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .form-card {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .map-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: sticky;
        top: 20px;
    }

    #map {
        height: 400px;
        width: 100%;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        padding: 10px 14px;
        transition: all 0.2s;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--digibox-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .info-box {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border-left: 4px solid var(--digibox-blue);
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .map-info {
        background: #f9fafb;
        padding: 16px;
        border-radius: 0 0 12px 12px;
    }

    .map-info .info-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .map-info .info-item:last-child {
        border-bottom: none;
    }

    .btn-action {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .coordinate-helper {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 12px;
        border-radius: 8px;
        font-size: 0.875rem;
        margin-top: 8px;
    }

    .range-slider-container {
        position: relative;
        padding-top: 10px;
    }

    .range-value {
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--digibox-blue);
        color: white;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: var(--danger-red);
    }

    .switch-container {
        background: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
    }

    .form-check-input:checked {
        background-color: var(--success-green);
        border-color: var(--success-green);
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    /* Mobile Responsive Styles */
    @media (max-width: 991px) {
        .page-header {
            padding: 24px 20px;
        }
        .form-card {
            padding: 24px 16px;
        }
        .map-card {
            position: static;
            margin-top: 20px;
        }
        #map {
            height: 350px;
        }
    }

    @media (max-width: 767px) {
        .page-header {
            padding: 20px 16px;
        }
        .page-header h4 {
            font-size: 1.1rem !important;
        }
        .page-header p {
            font-size: 0.875rem;
        }
        .form-card {
            padding: 20px 12px;
        }
        .map-info {
            padding: 12px;
        }
        #map {
            height: 300px;
        }
        .form-control, .form-select {
            padding: 9px 12px;
            font-size: 0.9rem;
        }
        .form-label {
            font-size: 0.875rem;
        }
        .btn-action {
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
        .coordinate-helper {
            padding: 10px;
            font-size: 0.8rem;
        }
        .switch-container {
            padding: 12px;
        }
    }

    @media (max-width: 575px) {
        .page-header {
            padding: 16px 12px;
        }
        .form-card {
            padding: 16px 8px;
        }
        .map-info {
            padding: 10px;
        }
        #map {
            height: 250px;
        }
        .form-control, .form-select {
            padding: 8px 10px;
            font-size: 0.85rem;
        }
        .info-box {
            padding: 10px;
            font-size: 0.8rem;
        }
        .coordinate-helper {
            padding: 8px;
            font-size: 0.75rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <strong>Validation Error:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4 class="fw-bold mb-2">
                    <i class="bi bi-pencil-square"></i> Edit Installation Location
                </h4>
                <p class="mb-0 opacity-90">Update location details and geofence settings</p>
            </div>
            <a href="{{ route('installations.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <form action="{{ route('installations.update', $installation) }}" method="POST" id="editForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Left Column - Form -->
            <div class="col-12 col-lg-7">
                <div class="form-card">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-info-circle-fill text-primary"></i> Location Information
                    </h5>

                    <!-- Name -->
                    <div class="mb-4">
                        <label class="form-label">
                            Installation Name <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control form-control-lg @error('name') is-invalid @enderror"
                            value="{{ old('name', $installation->name) }}"
                            placeholder="e.g., Tesco Extra - Main Entrance"
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div class="mb-4">
                        <label class="form-label">
                            Address
                        </label>
                        <textarea
                            name="address"
                            id="address"
                            class="form-control @error('address') is-invalid @enderror"
                            rows="3"
                            placeholder="Enter full address with street, city, and postcode">{{ old('address', $installation->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <h5 class="fw-bold mb-3 mt-5">
                        <i class="bi bi-geo-alt-fill text-danger"></i> GPS Coordinates
                    </h5>

                    <div class="info-box">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-lightbulb-fill text-primary"></i>
                            <div>
                                <strong>Tip:</strong> Click on the map to automatically set coordinates, or enter them manually below.
                            </div>
                        </div>
                    </div>

                    <!-- Coordinates -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-globe"></i> Latitude <span class="text-danger">*</span>
                            </label>
                            <input
                                type="number"
                                step="0.000001"
                                name="latitude"
                                id="latitude"
                                class="form-control @error('latitude') is-invalid @enderror"
                                value="{{ old('latitude', $installation->latitude) }}"
                                placeholder="3.0412"
                                required>
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-globe"></i> Longitude <span class="text-danger">*</span>
                            </label>
                            <input
                                type="number"
                                step="0.000001"
                                name="longitude"
                                id="longitude"
                                class="form-control @error('longitude') is-invalid @enderror"
                                value="{{ old('longitude', $installation->longitude) }}"
                                placeholder="101.7685"
                                required>
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Geofence Radius -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-bullseye"></i> Geofence Radius <span class="text-danger">*</span>
                        </label>
                        <div class="range-slider-container">
                            <span class="range-value" id="radiusValue">{{ old('geofence_radius_meters', $installation->geofence_radius_meters) }}m</span>
                            <input
                                type="range"
                                class="form-range"
                                id="radiusSlider"
                                min="50"
                                max="500"
                                step="10"
                                value="{{ old('geofence_radius_meters', $installation->geofence_radius_meters) }}">
                            <input
                                type="hidden"
                                name="geofence_radius_meters"
                                id="geofence_radius_meters"
                                value="{{ old('geofence_radius_meters', $installation->geofence_radius_meters) }}">
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted">50m</small>
                                <small class="text-muted">500m</small>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Detection radius for automatic check-ins
                        </small>
                        @error('geofence_radius_meters')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-toggle-on"></i> Status
                        </label>
                        <div class="switch-container">
                            <div class="form-check form-switch">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    class="form-check-input"
                                    id="is_active"
                                    role="switch"
                                    {{ old('is_active', $installation->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    <strong>Active Location</strong>
                                    <br>
                                    <small class="text-muted">Inactive locations won't appear in tracking</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 mt-5">
                        <button type="submit" class="btn btn-primary btn-action flex-fill">
                            <i class="bi bi-save"></i> Update Location
                        </button>
                        <a href="{{ route('installations.index') }}" class="btn btn-secondary btn-action">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="button" class="btn btn-danger btn-action" onclick="confirmDelete()">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column - Map Preview -->
            <div class="col-12 col-lg-5">
                <div class="map-card">
                    <div id="map"></div>
                    <div class="map-info">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-map"></i> Live Preview
                        </h6>
                        <div class="info-item">
                            <i class="bi bi-pin-map-fill text-danger"></i>
                            <div class="flex-fill">
                                <small class="text-muted">Current Position</small>
                                <div class="fw-semibold" id="currentCoords">
                                    {{ number_format($installation->latitude, 6) }}, {{ number_format($installation->longitude, 6) }}
                                </div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-bullseye text-primary"></i>
                            <div class="flex-fill">
                                <small class="text-muted">Geofence Radius</small>
                                <div class="fw-semibold" id="currentRadius">{{ $installation->geofence_radius_meters }}m</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-info-circle text-info"></i>
                            <div class="flex-fill">
                                <small class="text-muted">Click map to reposition marker</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Delete Confirmation Form (Hidden) -->
<form id="deleteForm" action="{{ route('installations.destroy', $installation) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Initialize map
    let map = L.map('map').setView([{{ $installation->latitude }}, {{ $installation->longitude }}], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Custom marker
    let marker = L.marker([{{ $installation->latitude }}, {{ $installation->longitude }}], {
        draggable: true,
        icon: L.divIcon({
            html: '<div style="background: #ef4444; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 4px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.4);"><i class="bi bi-building-fill" style="font-size: 18px;"></i></div>',
            className: '',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        })
    }).addTo(map);

    // Geofence circle
    let circle = L.circle([{{ $installation->latitude }}, {{ $installation->longitude }}], {
        color: '#ef4444',
        fillColor: '#fecaca',
        fillOpacity: 0.2,
        radius: {{ $installation->geofence_radius_meters }},
        weight: 2,
        dashArray: '5, 5'
    }).addTo(map);

    // Update coordinates when marker is dragged
    marker.on('dragend', function(e) {
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
    });

    // Update coordinates when map is clicked
    map.on('click', function(e) {
        const position = e.latlng;
        marker.setLatLng(position);
        circle.setLatLng(position);
        updateCoordinates(position.lat, position.lng);
    });

    function updateCoordinates(lat, lng) {
        document.getElementById('latitude').value = lat.toFixed(6);
        document.getElementById('longitude').value = lng.toFixed(6);
        document.getElementById('currentCoords').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
    }

    // Update map when coordinates are manually entered
    document.getElementById('latitude').addEventListener('change', updateMapFromInputs);
    document.getElementById('longitude').addEventListener('change', updateMapFromInputs);

    function updateMapFromInputs() {
        const lat = parseFloat(document.getElementById('latitude').value);
        const lng = parseFloat(document.getElementById('longitude').value);

        if (!isNaN(lat) && !isNaN(lng)) {
            marker.setLatLng([lat, lng]);
            circle.setLatLng([lat, lng]);
            map.setView([lat, lng], 16);
            document.getElementById('currentCoords').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }
    }

    // Geofence radius slider
    const radiusSlider = document.getElementById('radiusSlider');
    const radiusValue = document.getElementById('radiusValue');
    const radiusInput = document.getElementById('geofence_radius_meters');
    const currentRadius = document.getElementById('currentRadius');

    radiusSlider.addEventListener('input', function() {
        const radius = this.value;
        radiusValue.textContent = radius + 'm';
        radiusInput.value = radius;
        currentRadius.textContent = radius + 'm';
        circle.setRadius(parseInt(radius));
    });

    // Delete confirmation
    function confirmDelete() {
        if (confirm('Are you sure you want to delete this installation location?\n\nThis action cannot be undone.')) {
            document.getElementById('deleteForm').submit();
        }
    }

    // Form validation
    document.getElementById('editForm').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const lat = document.getElementById('latitude').value;
        const lng = document.getElementById('longitude').value;

        if (!name || !lat || !lng) {
            e.preventDefault();
            alert('Please fill in all required fields (Name, Latitude, Longitude)');
            return false;
        }
    });
</script>
@endpush
@endsection
