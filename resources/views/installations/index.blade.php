@extends('layouts.app')

@section('title', 'Installation Locations')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-building"></i> Installations</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    :root {
        --digibox-blue: #2563EB;
        --success-green: #10b981;
        --danger-red: #ef4444;
        --warning-orange: #f59e0b;
    }

    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
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

    .installation-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s;
        border: 2px solid transparent;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .installation-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        border-color: var(--digibox-blue);
    }

    .installation-card .card-map {
        height: 150px;
        background: #f3f4f6;
        position: relative;
        flex-shrink: 0;
    }

    .installation-card .card-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .installation-card .location-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
        height: 2.6em;
        line-height: 1.3em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .installation-card .location-address {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 12px;
        height: 2.5em;
        line-height: 1.25em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .info-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #f3f4f6;
        color: #4b5563;
    }

    .installation-card .info-badges-container {
        min-height: 60px;
    }

    .installation-item {
        display: flex;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        margin-top: auto;
        padding-top: 16px;
    }

    .btn-action {
        flex: 1;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s;
        border: none;
    }

    .btn-action:hover {
        transform: translateY(-2px);
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

    .leaflet-container {
        border-radius: 8px;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 991px) {
        .stats-card {
            padding: 16px;
        }
        .stats-card .icon-wrapper {
            width: 44px;
            height: 44px;
            font-size: 20px;
        }
        .installation-card .card-map {
            height: 130px;
        }
        .installation-card .card-body {
            padding: 16px;
        }
        .search-section {
            padding: 16px;
        }
    }

    @media (max-width: 767px) {
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 12px;
        }
        .d-flex.gap-2 {
            width: 100%;
        }
        .btn {
            flex: 1;
        }
        .stats-card {
            padding: 14px;
        }
        .stats-card h2 {
            font-size: 1.5rem;
        }
        .stats-card h6 {
            font-size: 0.8rem;
        }
        .stats-card .icon-wrapper {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }
        .installation-card .card-map {
            height: 120px;
        }
        .installation-card .card-body {
            padding: 14px;
        }
        .installation-card .location-name {
            font-size: 1rem;
        }
        .installation-card .location-address {
            font-size: 0.8rem;
        }
        .btn-action {
            padding: 7px 10px;
            font-size: 0.8rem;
        }
        .search-section {
            padding: 12px;
        }
    }

    @media (max-width: 575px) {
        h4.fw-bold {
            font-size: 1.1rem !important;
        }
        .text-muted {
            font-size: 0.875rem;
        }
        .stats-card {
            padding: 12px;
        }
        .stats-card h2 {
            font-size: 1.25rem;
        }
        .stats-card h6 {
            font-size: 0.75rem;
        }
        .installation-card .card-map {
            height: 100px;
        }
        .installation-card .card-body {
            padding: 12px;
        }
        .info-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
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

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-2">
                        <i class="bi bi-building-fill" style="color: #2563EB;"></i> Installation Locations
                    </h4>
                    <p class="text-muted mb-0">Manage customer installation sites and geofence zones</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-upload"></i> Import CSV
                    </button>
                    <a href="{{ route('installations.export') }}" class="btn btn-info text-white">
                        <i class="bi bi-download"></i> Export
                    </a>
                    <a href="{{ route('installations.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Location
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4 g-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Total Locations</p>
                        <h3 class="mb-0 fw-bold">{{ $installations->total() }}</h3>
                    </div>
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #2563EB, #1e40af); color: white;">
                        <i class="bi bi-building"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Active</p>
                        <h3 class="mb-0 fw-bold">{{ $installations->where('is_active', true)->count() }}</h3>
                    </div>
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Inactive</p>
                        <h3 class="mb-0 fw-bold">{{ $installations->where('is_active', false)->count() }}</h3>
                    </div>
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white;">
                        <i class="bi bi-pause-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Avg. Geofence</p>
                        <h3 class="mb-0 fw-bold">{{ round($installations->avg('geofence_radius_meters')) }}m</h3>
                    </div>
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #2563EB, #1d4ed8); color: white;">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Section -->
    <div class="search-section">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-0 bg-light" id="searchInput" placeholder="Search by name or address...">
                </div>
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <select class="form-select border-0 bg-light" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Installations Grid -->
    @if($installations->count() > 0)
    <div class="row g-4" id="installationsGrid">
        @foreach($installations as $installation)
        <div class="col-12 col-md-6 col-lg-4 installation-item"
             data-name="{{ strtolower($installation->name) }}"
             data-address="{{ strtolower($installation->address ?? '') }}"
             data-status="{{ $installation->is_active ? 'active' : 'inactive' }}">
            <div class="installation-card">
                <!-- Map Preview -->
                <div class="card-map">
                    <div id="map-{{ $installation->id }}" style="height: 100%; width: 100%;"></div>
                </div>

                <!-- Card Body -->
                <div class="card-body">
                    <!-- Status Badge -->
                    <div class="mb-2">
                        @if($installation->is_active)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle-fill"></i> Active
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="bi bi-pause-circle-fill"></i> Inactive
                            </span>
                        @endif
                    </div>

                    <!-- Location Name -->
                    <div class="location-name">{{ $installation->name }}</div>

                    <!-- Address -->
                    <div class="location-address">
                        <i class="bi bi-geo-alt"></i> {{ $installation->address ?? 'No address provided' }}
                    </div>

                    <!-- Info Badges -->
                    <div class="d-flex flex-wrap gap-2 mb-3 info-badges-container">
                        <span class="info-badge">
                            <i class="bi bi-pin-map-fill"></i>
                            {{ number_format($installation->latitude, 4) }}, {{ number_format($installation->longitude, 4) }}
                        </span>
                        <span class="info-badge">
                            <i class="bi bi-bullseye"></i>
                            {{ $installation->geofence_radius_meters }}m radius
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="{{ route('installations.edit', $installation) }}" class="btn btn-action btn-primary">
                            <i class="bi bi-pencil-fill"></i> Edit
                        </a>
                        <form action="{{ route('installations.destroy', $installation) }}" method="POST" class="flex-fill" onsubmit="return confirm('Are you sure you want to delete this installation?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-action btn-danger w-100">
                                <i class="bi bi-trash-fill"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $installations->links() }}
    </div>

    @else
    <!-- Empty State -->
    <div class="empty-state">
        <i class="bi bi-building"></i>
        <h5 class="fw-bold mb-2">No Installation Locations</h5>
        <p class="text-muted mb-4">Get started by adding your first installation location</p>
        <a href="{{ route('installations.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Location
        </a>
    </div>
    @endif
</div>

<!-- CSV Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('installations.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Installations from CSV</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control form-control-lg" accept=".csv,.txt" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Supported format: CSV with comma-separated values
                        </small>
                    </div>

                    <div class="alert alert-info border-0">
                        <h6 class="alert-heading fw-bold">
                            <i class="bi bi-lightbulb-fill"></i> CSV Format Guide
                        </h6>
                        <p class="mb-2 small">Your CSV file should have the following columns:</p>
                        <div class="bg-white p-3 rounded" style="font-family: monospace; font-size: 0.875rem;">
                            <strong>Name,Address,Latitude,Longitude,Geofence Radius</strong><br>
                            Tesco Extra,Jalan Balakong,3.0412,101.7685,150<br>
                            MINES Shopping Mall,Jalan Dulang,3.0435,101.7701,100
                        </div>
                        <p class="mb-0 mt-2 small text-muted">
                            <i class="bi bi-check-circle"></i> Geofence Radius is optional (default: 100m)
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-upload"></i> Import Locations
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Initialize maps for each installation
    document.addEventListener('DOMContentLoaded', function() {
        @foreach($installations as $installation)
        (function() {
            const mapId = 'map-{{ $installation->id }}';
            const lat = {{ $installation->latitude }};
            const lng = {{ $installation->longitude }};
            const radius = {{ $installation->geofence_radius_meters }};

            const map = L.map(mapId, {
                center: [lat, lng],
                zoom: 16,
                zoomControl: false,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                tap: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: ''
            }).addTo(map);

            // Marker
            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    html: '<div style="background: #2563EB; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="bi bi-building-fill" style="font-size: 14px;"></i></div>',
                    className: '',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                })
            }).addTo(map);

            // Geofence circle
            L.circle([lat, lng], {
                color: '#2563EB',
                fillColor: '#BFDBFE',
                fillOpacity: 0.2,
                radius: radius,
                weight: 2
            }).addTo(map);
        })();
        @endforeach
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const installationItems = document.querySelectorAll('.installation-item');

    function filterInstallations() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;

        installationItems.forEach(item => {
            const name = item.dataset.name;
            const address = item.dataset.address;
            const status = item.dataset.status;

            const matchesSearch = name.includes(searchTerm) || address.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;

            if (matchesSearch && matchesStatus) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterInstallations);
    statusFilter.addEventListener('change', filterInstallations);

    function resetFilters() {
        searchInput.value = '';
        statusFilter.value = '';
        filterInstallations();
    }
</script>
@endpush
@endsection
