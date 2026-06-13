@extends('layouts.app')

@section('title', 'Rider Tracking')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-pin-map-fill"></i> Tracking</li>
@endsection

@section('content')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<style>
    /* Hide routing machine instructions panel */
    .leaflet-routing-container {
        display: none;
    }

<style>
    :root {
        --digibox-blue: #2563EB;
        --success-green: #10b981;
        --warning-orange: #f59e0b;
        --danger-red: #ef4444;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-600: #4b5563;
    }

    /* Tab Navigation */
    .nav-tabs {
        border-bottom: 2px solid #e5e7eb;
    }

    .nav-tabs .nav-link {
        color: #6b7280;
        border: none;
        padding: 12px 24px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .nav-tabs .nav-link:hover {
        color: var(--digibox-blue);
        border: none;
    }

    .nav-tabs .nav-link.active {
        color: var(--digibox-blue);
        background: transparent;
        border: none;
        border-bottom: 3px solid var(--digibox-blue);
    }

    .nav-tabs .nav-link i {
        margin-right: 6px;
    }

    /* Map Container */
    #map, #liveMap {
        height: 600px;
        border-radius: 12px;
        position: relative;
        z-index: 1;
    }

    /* Timeline Sidebar */
    .timeline-sidebar {
        height: 600px;
        background: white;
        border-radius: 12px;
        padding: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .timeline-header {
        background: linear-gradient(135deg, var(--digibox-blue), #1e40af);
        color: white;
        padding: 20px;
        border-radius: 12px 12px 0 0;
        flex-shrink: 0;
    }

    .work-summary {
        background: var(--gray-50);
        padding: 16px;
        border-bottom: 2px solid #e5e7eb;
        flex-shrink: 0;
    }

    .work-summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }

    .work-summary-item strong {
        color: var(--gray-600);
        font-size: 0.875rem;
    }

    .work-summary-item .value {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--digibox-blue);
    }

    .work-summary-item .value.overtime {
        color: var(--warning-orange);
    }

    .timeline-content {
        padding: 16px;
        overflow-y: auto;
        flex: 1;
        scroll-behavior: smooth;
    }

    /* Custom scrollbar for timeline */
    .timeline-content::-webkit-scrollbar {
        width: 6px;
    }

    .timeline-content::-webkit-scrollbar-track {
        background: var(--gray-100);
        border-radius: 10px;
    }

    .timeline-content::-webkit-scrollbar-thumb {
        background: var(--digibox-blue);
        border-radius: 10px;
    }

    .timeline-content::-webkit-scrollbar-thumb:hover {
        background: #1e40af;
    }

    /* Session Card Styles */
    .session-card {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }

    .session-card:hover {
        border-color: var(--digibox-blue);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
    }

    .session-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 16px;
        border-bottom: 2px solid #f3f4f6;
        margin-bottom: 16px;
    }

    .session-time-range {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
    }

    .session-duration-badge {
        background: linear-gradient(135deg, var(--digibox-blue), #1e40af);
        color: white;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .session-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }

    .session-stat {
        background: var(--gray-50);
        padding: 12px;
        border-radius: 8px;
        text-align: center;
    }

    .session-stat-label {
        font-size: 0.7rem;
        color: var(--gray-600);
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .session-stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--digibox-blue);
    }

    .timeline-item {
        position: relative;
        padding-left: 50px;
        padding-bottom: 20px;
        transition: all 0.2s;
    }

    .timeline-item:hover {
        background: var(--gray-50);
        margin-left: -16px;
        margin-right: -16px;
        padding-left: 66px;
        padding-right: 16px;
        border-radius: 8px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 40px;
        bottom: -8px;
        width: 3px;
        background: linear-gradient(180deg, #e5e7eb 0%, transparent 100%);
    }

    .timeline-item:last-child::before {
        display: none;
    }

    .timeline-dot {
        position: absolute;
        left: 0;
        top: 4px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        z-index: 2;
    }

    .timeline-dot.start { background: linear-gradient(135deg, #10b981, #059669); }
    .timeline-dot.stop { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .timeline-dot.visit { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .timeline-dot.end { background: linear-gradient(135deg, #6b7280, #4b5563); }
    .timeline-dot.offline { background: #64748b; border: 3px dashed white; }

    .timeline-content {
        background: white;
        padding: 12px;
        border-radius: 8px;
        border-left: 3px solid #e5e7eb;
    }

    .timeline-time {
        font-size: 0.875rem;
        color: var(--digibox-blue);
        font-weight: 700;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .timeline-time i {
        font-size: 1rem;
    }

    .timeline-title {
        font-weight: 700;
        font-size: 1rem;
        color: #111827;
        margin-bottom: 6px;
    }

    .timeline-meta {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .timeline-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--gray-100);
        color: var(--gray-600);
        padding: 4px 10px;
        border-radius: 16px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .timeline-badge.duration {
        background: #fef3c7;
        color: #92400e;
    }

    .timeline-badge.location {
        background: #dbeafe;
        color: #1e40af;
    }

    .timeline-location {
        font-size: 0.875rem;
        color: var(--gray-600);
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Controls */
    .tracking-controls {
        background: white;
        padding: 24px;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
    }

    /* No Data State */
    .no-data-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray-600);
    }

    .no-data-state i {
        font-size: 64px;
        opacity: 0.3;
        margin-bottom: 16px;
    }

    /* Stop Modal */
    .stop-modal .modal-content {
        border-radius: 16px;
        border: none;
    }

    .stop-modal .modal-header {
        background: linear-gradient(135deg, var(--digibox-blue), #1e40af);
        color: white;
        border-radius: 16px 16px 0 0;
    }

    .stop-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin: 16px 0;
    }

    .stop-info-item {
        padding: 12px;
        background: var(--gray-50);
        border-radius: 8px;
    }

    .stop-info-label {
        font-size: 0.75rem;
        color: var(--gray-600);
        margin-bottom: 4px;
    }

    .stop-info-value {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
    }

    /* Responsive */
    @media (max-width: 991px) {
        #map { height: 450px; }
        .timeline-sidebar {
            height: 500px;
            margin-top: 20px;
        }
        .stop-info-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 767px) {
        #map { height: 350px; }
        .timeline-sidebar {
            height: 400px;
        }
    }
</style>
@endpush

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold mb-2">
                <i class="bi bi-geo-alt-fill text-primary"></i> Rider Tracking
            </h4>
            <p class="text-muted mb-0">Monitor live locations and work history</p>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="trackingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="live-tab" data-bs-toggle="tab" data-bs-target="#live-tab-pane" type="button" role="tab" aria-controls="live-tab-pane" aria-selected="true">
                <i class="bi bi-broadcast"></i> Live Tracking
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-tab-pane" type="button" role="tab" aria-controls="history-tab-pane" aria-selected="false">
                <i class="bi bi-clock-history"></i> Work History
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="trackingTabsContent">
        <!-- Live Tracking Tab -->
        <div class="tab-pane fade show active" id="live-tab-pane" role="tabpanel" aria-labelledby="live-tab" tabindex="0">
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div id="liveMap" style="height: 600px; border-radius: 12px;"></div>

                            <!-- Live map loading state -->
                            <div id="liveMapLoading" style="display:none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); z-index: 1001; align-items: center; justify-content: center;">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                                    <p class="mt-3 fw-semibold">Loading riders...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Auto-refresh indicator and legend -->
                        <div class="card-footer bg-light border-0">
                            <!-- Back to Live View button (hidden by default) -->
                            <div id="backToLiveView" style="display: none;" class="mb-2">
                                <button class="btn btn-sm btn-primary" onclick="backToAllRiders()">
                                    <i class="bi bi-arrow-left"></i> Back to All Riders
                                </button>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2" id="liveStatusBar">
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-sm btn-primary" onclick="loadLiveRiders()" title="Refresh live data">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    <button id="autoRefreshToggle" class="btn btn-sm btn-secondary" onclick="toggleAutoRefresh()" title="Enable auto-refresh">
                                        <i class="bi bi-play-circle"></i> Auto-Refresh
                                    </button>
                                    <small class="text-muted">
                                        <span id="liveStatusText">Auto-refresh: OFF (click to enable)</span>
                                    </small>
                                </div>
                                <small class="text-muted" id="liveLastUpdate">Last updated: Never</small>
                            </div>
                            <div class="d-flex gap-3 flex-wrap" style="font-size: 0.75rem;">
                                <div class="d-flex align-items-center gap-1">
                                    <div style="width: 16px; height: 16px; background: #10b981; border-radius: 50%; border: 2px solid white;"></div>
                                    <span class="text-muted">Online</span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <div style="width: 16px; height: 16px; background: #6b7280; border-radius: 50%; border: 2px solid white;"></div>
                                    <span class="text-muted">Offline</span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <div style="width: 16px; height: 16px; background: #ef4444; border-radius: 4px; border: 2px solid white;"></div>
                                    <span class="text-muted">Installation Location</span>
                                </div>
                            </div>

                            <!-- AI-Powered Speed Color Legend -->
                            <div class="mt-2 pt-2 border-top">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-stars text-purple" style="color: #8B5CF6;"></i>
                                    <small class="text-muted fw-semibold">AI-Powered Route Colors:</small>
                                </div>
                                <div class="d-flex gap-3 flex-wrap" style="font-size: 0.7rem;">
                                    <div class="d-flex align-items-center gap-1">
                                        <div style="width: 20px; height: 4px; background: #DC2626; border-radius: 2px; box-shadow: 0 1px 3px rgba(220,38,38,0.4);"></div>
                                        <span class="text-muted">Stopped</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <div style="width: 20px; height: 4px; background: #F59E0B; border-radius: 2px; box-shadow: 0 1px 3px rgba(245,158,11,0.4);"></div>
                                        <span class="text-muted">Slow</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <div style="width: 20px; height: 4px; background: #10B981; border-radius: 2px; box-shadow: 0 1px 3px rgba(16,185,129,0.4);"></div>
                                        <span class="text-muted">Normal</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <div style="width: 20px; height: 4px; background: #3B82F6; border-radius: 2px; box-shadow: 0 1px 3px rgba(59,130,246,0.4);"></div>
                                        <span class="text-muted">Fast</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <div style="width: 20px; height: 4px; background: #8B5CF6; border-radius: 2px; box-shadow: 0 1px 3px rgba(139,92,246,0.4);"></div>
                                        <span class="text-muted">Highway</span>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2" style="font-size: 0.65rem;">
                                    <i class="bi bi-check-circle-fill text-success"></i> GPS data processed with AI filtering & Kalman smoothing
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Tab -->
        <div class="tab-pane fade" id="history-tab-pane" role="tabpanel" aria-labelledby="history-tab" tabindex="0">
            <!-- Controls -->
    <div class="tracking-controls">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">
                    <i class="bi bi-person-fill"></i> Select Rider
                </label>
                <select class="form-select" id="riderFilter">
                    <option value="">Choose rider...</option>
                    @foreach($riders as $rider)
                        <option value="{{ $rider->id }}">{{ $rider->name }} ({{ $rider->username }})</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">
                    <i class="bi bi-calendar-fill"></i> Start Date
                </label>
                <input type="date" class="form-control" id="startDate" value="{{ date('Y-m-d') }}">
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">
                    <i class="bi bi-calendar-fill"></i> End Date
                </label>
                <input type="date" class="form-control" id="endDate" value="{{ date('Y-m-d') }}">
            </div>

            <div class="col-12 col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" id="loadHistory" style="background: var(--digibox-blue); border: none;">
                    <i class="bi bi-search"></i> Load
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Map -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div id="map"></div>

                <!-- Initial empty state -->
                <div id="mapEmptyState" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.95); z-index: 1000; display: flex; align-items: center; justify-content: center;">
                    <div class="text-center p-4">
                        <i class="bi bi-map" style="font-size: 3rem; color: #6c757d;"></i>
                        <p class="mt-3 fw-semibold mb-1" style="color: #495057;">No route selected</p>
                        <small style="color: #6c757d;">Select a rider and date range, then click Load to view tracking history</small>
                    </div>
                </div>

                <!-- Loading state -->
                <div id="mapLoading" style="display:none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); z-index: 1001; display: flex; align-items: center; justify-content: center;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                        <p class="mt-3 fw-semibold">Loading route...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="timeline-sidebar card border-0 shadow-sm">
                <div class="timeline-header">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Work Timeline</h6>
                </div>

                <div id="workSummary" style="display:none;" class="work-summary">
                    <!-- Work summary will be inserted here -->
                </div>

                <div id="timelineContent" class="timeline-content">
                    <div class="no-data-state">
                        <i class="bi bi-calendar-x"></i>
                        <p class="fw-semibold">No data selected</p>
                        <small>Select a rider and date range to view work timeline</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
        <!-- End History Tab -->
    </div>
    <!-- End Tab Content -->
</div>

<!-- Stop Details Modal -->
<div class="modal fade stop-modal" id="stopModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pause-circle-fill"></i> Stop Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="stopModalContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
<script>
    // Installation locations data from backend
    const installations = @json($installations);

    let map;
    let liveMap;
    let routeLayer = null;
    let markersLayer = null;
    let liveMarkersLayer = null;
    let liveInstallationsLayer = null;
    let liveRouteLayer = null;
    let liveRefreshInterval = null;
    let isViewingRiderHistory = false;
    let currentViewingRiderId = null;

    // Initialize history map (will be shown in History tab)
    map = L.map('map').setView([3.0339, 101.7598], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Initialize live map (shown in Live tab)
    liveMap = L.map('liveMap').setView([3.0339, 101.7598], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(liveMap);

    // Custom marker icons
    const startIcon = L.divIcon({
        html: '<div style="background: #10b981; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="bi bi-play-fill" style="font-size: 20px;"></i></div>',
        className: '',
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });

    const endIcon = L.divIcon({
        html: '<div style="background: #4b5563; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="bi bi-stop-fill" style="font-size: 20px;"></i></div>',
        className: '',
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });

    const stopIcon = L.divIcon({
        html: '<div style="background: #f59e0b; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="bi bi-pause-fill" style="font-size: 16px;"></i></div>',
        className: '',
        iconSize: [35, 35],
        iconAnchor: [17, 17]
    });

    const visitIcon = L.divIcon({
        html: '<div style="background: #ef4444; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="bi bi-building" style="font-size: 16px;"></i></div>',
        className: '',
        iconSize: [35, 35],
        iconAnchor: [17, 17]
    });

    // Installation icon (unvisited)
    const installationIcon = L.divIcon({
        html: '<div style="background: #8B5CF6; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2); opacity: 0.8;"><i class="bi bi-building" style="font-size: 14px;"></i></div>',
        className: '',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    const offlineIcon = L.divIcon({
        html: '<div style="background: #64748b; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px dashed white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="bi bi-wifi-off" style="font-size: 14px;"></i></div>',
        className: '',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });

    // Live tracking icons
    const liveOnDutyIcon = L.divIcon({
        html: '<div style="background: #10b981; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 3px 10px rgba(16,185,129,0.5);"><i class="bi bi-person-fill" style="font-size: 20px;"></i></div>',
        className: '',
        iconSize: [45, 45],
        iconAnchor: [22, 22]
    });

    const liveOffDutyIcon = L.divIcon({
        html: '<div style="background: #6b7280; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="bi bi-person" style="font-size: 18px;"></i></div>',
        className: '',
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });

    // Tab switching handlers
    document.getElementById('live-tab').addEventListener('shown.bs.tab', function () {
        setTimeout(() => {
            liveMap.invalidateSize();
            renderInstallationLocations(); // Re-render installations
            loadLiveRiders();
        }, 100);
    });

    document.getElementById('history-tab').addEventListener('shown.bs.tab', function () {
        setTimeout(() => {
            map.invalidateSize();

            // Pause auto-refresh when switching to History tab (save resources)
            if (realtimeRefreshInterval) {
                clearInterval(realtimeRefreshInterval);
                realtimeRefreshInterval = null;
                console.log('⏸️ Auto-refresh paused (switched to History tab)');
            }
        }, 100);
    });

    // Render installation locations on live map (once)
    renderInstallationLocations();

    // Load live riders on page load
    loadLiveRiders();

    // Render installation locations on live map
    function renderInstallationLocations() {
        if (liveInstallationsLayer) {
            liveMap.removeLayer(liveInstallationsLayer);
        }

        liveInstallationsLayer = L.layerGroup().addTo(liveMap);

        installations.forEach(installation => {
            const lat = parseFloat(installation.latitude);
            const lng = parseFloat(installation.longitude);
            const radius = installation.geofence_radius_meters || 100;

            // Installation marker with label
            const installationIconHtml = `
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <div style="background: #ef4444; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid white; box-shadow: 0 3px 10px rgba(239,68,68,0.5);">
                        <i class="bi bi-building-fill" style="font-size: 18px;"></i>
                    </div>
                    <div style="background: white; padding: 3px 6px; border-radius: 3px; margin-top: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); white-space: nowrap; border: 1px solid #fecaca; max-width: 120px; overflow: hidden; text-overflow: ellipsis;">
                        <small style="font-size: 10px; color: #dc2626; font-weight: 600;">${installation.name.split('-')[0].trim()}</small>
                    </div>
                </div>
            `;

            const installationIcon = L.divIcon({
                html: installationIconHtml,
                className: '',
                iconSize: [120, 60],
                iconAnchor: [60, 30]
            });

            const marker = L.marker([lat, lng], { icon: installationIcon }).addTo(liveInstallationsLayer);

            // Geofence circle
            L.circle([lat, lng], {
                color: '#ef4444',
                fillColor: '#fecaca',
                fillOpacity: 0.1,
                radius: radius,
                weight: 2,
                dashArray: '5, 5'
            }).addTo(liveInstallationsLayer);

            // Popup with installation details
            const popupContent = `
                <div style="min-width: 200px;">
                    <h6 class="fw-bold mb-2" style="color: #ef4444;">
                        <i class="bi bi-building-fill"></i> Installation
                    </h6>
                    <div style="font-size: 0.875rem;">
                        <div class="mb-1">
                            <strong>Name:</strong><br>
                            ${installation.name}
                        </div>
                        <div class="mb-1">
                            <strong>Address:</strong><br>
                            <small>${installation.address || 'N/A'}</small>
                        </div>
                        <div class="mb-1">
                            <strong>Geofence:</strong> ${radius}m radius
                        </div>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent);
        });
    }

    // Live tracking functions
    // AUTO-REFRESH INTERVAL (60 seconds / 1 minute)
    let realtimeRefreshInterval = null;
    let autoRefreshEnabled = false; // Track auto-refresh state (OFF by default)

    function loadLiveRiders() {
        const loadingEl = document.getElementById('liveMapLoading');
        loadingEl.style.display = 'flex';

        // Use NEW real-time endpoint for live GPS streaming
        fetch('/realtime/riders')
            .then(response => response.json())
            .then(data => {
                // Data is returned as direct array, not wrapped in object
                const riders = Array.isArray(data) ? data : [];
                console.log('📍 Loaded', riders.length, 'online riders');
                renderLiveRiders(riders);
                loadingEl.style.display = 'none';

                const timestamp = new Date().toLocaleTimeString();
                const liveStatus = autoRefreshEnabled ? '(LIVE)' : '(PAUSED)';
                document.getElementById('liveLastUpdate').textContent = 'Last updated: ' + timestamp + ' ' + liveStatus;

                // Start auto-refresh if enabled and not already running
                if (autoRefreshEnabled && !realtimeRefreshInterval) {
                    realtimeRefreshInterval = setInterval(loadLiveRiders, 60000); // Refresh every 60 seconds (1 minute)
                    console.log('✅ Auto-refresh started (60s interval)');
                }
            })
            .catch(error => {
                console.error('Error loading live riders:', error);
                loadingEl.style.display = 'none';
            });
    }

    // Toggle auto-refresh on/off
    function toggleAutoRefresh() {
        autoRefreshEnabled = !autoRefreshEnabled;

        const toggleBtn = document.getElementById('autoRefreshToggle');
        const statusText = document.getElementById('liveStatusText');

        if (autoRefreshEnabled) {
            // Enable auto-refresh
            toggleBtn.className = 'btn btn-sm btn-success';
            toggleBtn.innerHTML = '<i class="bi bi-pause-circle"></i> Auto-Refresh';
            toggleBtn.title = 'Pause auto-refresh';
            statusText.textContent = 'Auto-refresh: ON (every 1 min)';

            // Start interval
            if (!realtimeRefreshInterval) {
                realtimeRefreshInterval = setInterval(loadLiveRiders, 60000); // 60 seconds = 1 minute
                console.log('✅ Auto-refresh enabled (60s interval)');
            }
        } else {
            // Disable auto-refresh
            toggleBtn.className = 'btn btn-sm btn-secondary';
            toggleBtn.innerHTML = '<i class="bi bi-play-circle"></i> Auto-Refresh';
            toggleBtn.title = 'Enable auto-refresh';
            statusText.textContent = 'Auto-refresh: OFF (click to enable)';

            // Stop interval
            if (realtimeRefreshInterval) {
                clearInterval(realtimeRefreshInterval);
                realtimeRefreshInterval = null;
                console.log('⏸️ Auto-refresh disabled');
            }
        }
    }

    // Stop auto-refresh when leaving page
    window.addEventListener('beforeunload', function() {
        if (realtimeRefreshInterval) {
            clearInterval(realtimeRefreshInterval);
        }
    });

    function renderLiveRiders(riders) {
        // Clear existing markers
        if (liveMarkersLayer) {
            liveMap.removeLayer(liveMarkersLayer);
        }

        liveMarkersLayer = L.layerGroup().addTo(liveMap);

        if (riders.length === 0) {
            return;
        }

        const bounds = [];

        riders.forEach(rider => {
            if (rider.latitude && rider.longitude) {
                const lat = parseFloat(rider.latitude);
                const lng = parseFloat(rider.longitude);

                bounds.push([lat, lng]);

                // Create custom icon with username label
                const iconColor = rider.is_online ? '#10b981' : '#6b7280';
                const iconHtml = `
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="background: ${iconColor}; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.4);">
                            <i class="bi bi-person-fill" style="font-size: 20px;"></i>
                        </div>
                        <div style="background: white; padding: 4px 8px; border-radius: 4px; margin-top: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); white-space: nowrap; border: 1px solid #e5e7eb;">
                            <strong style="font-size: 12px; color: #374151;">${rider.username}</strong>
                        </div>
                    </div>
                `;

                const customIcon = L.divIcon({
                    html: iconHtml,
                    className: '',
                    iconSize: [80, 70],
                    iconAnchor: [40, 35]
                });

                const marker = L.marker([lat, lng], { icon: customIcon }).addTo(liveMarkersLayer);

                // Create popup with rider info
                const popupContent = `
                    <div style="min-width: 200px;">
                        <h6 class="fw-bold mb-2" style="color: ${rider.is_online ? '#10b981' : '#6b7280'};">
                            <i class="bi bi-person-circle"></i> ${rider.name}
                        </h6>
                        <div style="font-size: 0.875rem;">
                            <div class="mb-1">
                                <strong>Connection:</strong>
                                <span class="badge ${rider.is_online ? 'bg-success' : 'bg-secondary'}">${rider.status}</span>
                            </div>
                            ${rider.is_on_duty ? `
                            <div class="mb-1">
                                <strong>Started Working:</strong> ${rider.duty_started_at}
                            </div>
                            <div class="mb-1">
                                <strong>Duration:</strong> ${rider.duty_duration}
                            </div>
                            ` : ''}
                            <div class="mb-1">
                                <strong>Last Update:</strong><br>
                                <small>${rider.recorded_at}</small>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-primary w-100 mt-2" onclick="viewRiderHistory(${rider.id}, '${rider.name}')">
                            <i class="bi bi-clock-history"></i> View Today's History
                        </button>
                    </div>
                `;

                marker.bindPopup(popupContent);

                // Auto-open popup on hover
                marker.on('mouseover', function() {
                    this.openPopup();
                });

                // Click to view history
                marker.on('click', function() {
                    viewRiderHistory(rider.id, rider.name);
                });
            }
        });

        // Add installation locations to bounds
        installations.forEach(installation => {
            if (installation.latitude && installation.longitude) {
                bounds.push([parseFloat(installation.latitude), parseFloat(installation.longitude)]);
            }
        });

        // Fit map to show all riders and installations
        if (bounds.length > 0) {
            liveMap.fitBounds(bounds, { padding: [80, 80], maxZoom: 16 });
        }
    }

    // View rider's today's history on live map
    function viewRiderHistory(riderId, riderName) {
        isViewingRiderHistory = true;
        currentViewingRiderId = riderId;

        // Update UI
        document.getElementById('backToLiveView').style.display = 'block';
        document.getElementById('liveStatusText').textContent = `Viewing ${riderName}'s history for today`;
        document.getElementById('liveMapLoading').style.display = 'flex';

        // Fetch today's history
        const today = new Date().toISOString().split('T')[0];

        fetch(`/tracking/historical?rider_id=${riderId}&start_date=${today}&end_date=${today}`)
            .then(response => response.json())
            .then(data => {
                renderRiderHistoryOnLiveMap(data, riderName);
                document.getElementById('liveMapLoading').style.display = 'none';
            })
            .catch(error => {
                console.error('Error loading rider history:', error);
                document.getElementById('liveMapLoading').style.display = 'none';
                alert('Failed to load rider history');
            });
    }

    // Render rider history on the live map
    async function renderRiderHistoryOnLiveMap(data, riderName) {
        // Clear existing route layer
        if (liveRouteLayer) {
            liveMap.removeLayer(liveRouteLayer);
        }

        // Hide other riders temporarily
        if (liveMarkersLayer) {
            liveMap.removeLayer(liveMarkersLayer);
        }

        const { locations, stops, visits, sessions, installations } = data;

        if (!locations || locations.length === 0) {
            alert(`No tracking data found for ${riderName} today`);
            backToAllRiders();
            return;
        }

        // Create route layer
        liveRouteLayer = L.layerGroup().addTo(liveMap);

        console.log('🤖 Rendering history:', locations.length, 'GPS points for', riderName);

        // Build realistic route using AI prediction
        await buildRealisticRouteOnLiveMap(locations, liveRouteLayer);

        // Add start marker
        if (sessions && sessions[0]) {
            L.marker([locations[0].latitude, locations[0].longitude], { icon: startIcon })
                .bindPopup(`
                    <div style="padding: 8px;">
                        <h6 class="fw-bold text-success mb-2"><i class="bi bi-play-circle-fill"></i> Start Location</h6>
                        <p class="mb-0 small">${locations[0].recorded_at_human}</p>
                    </div>
                `)
                .addTo(liveRouteLayer);
        }

        // Add last seen marker (current/last location)
        if (locations && locations.length > 0) {
            const lastLocation = locations[locations.length - 1];
            const isOnline = sessions && sessions[0] && sessions[0].status === 'active';
            const statusColor = isOnline ? 'primary' : 'secondary';
            const statusIcon = isOnline ? 'geo-alt-fill' : 'clock-history';
            const statusText = isOnline ? 'Current Location' : 'Last Seen';

            L.marker([lastLocation.latitude, lastLocation.longitude], { icon: endIcon })
                .bindPopup(`
                    <div style="padding: 8px;">
                        <h6 class="fw-bold text-${statusColor} mb-2">
                            <i class="bi bi-${statusIcon}"></i> ${statusText}
                        </h6>
                        <p class="mb-1 small"><strong>Time:</strong> ${lastLocation.recorded_at_human}</p>
                        <p class="mb-0 small text-muted">${lastLocation.latitude.toFixed(6)}, ${lastLocation.longitude.toFixed(6)}</p>
                    </div>
                `)
                .addTo(liveRouteLayer);
        }

        // Add visit markers (visited - RED)
        const visitedIds = new Set();
        if (visits && visits.length > 0) {
            visits.forEach(visit => {
                visitedIds.add(visit.installation_location_id || visit.id);
                L.marker([visit.latitude, visit.longitude], { icon: visitIcon })
                    .bindPopup(`
                        <div style="padding: 8px;">
                            <h6 class="fw-bold text-danger mb-2"><i class="bi bi-check-circle-fill"></i> Visited Installation</h6>
                            <p class="mb-1 small"><strong>${visit.installation_name}</strong></p>
                            <p class="mb-0 small">Duration: ${visit.duration_minutes || 'In progress'} min</p>
                        </div>
                    `)
                    .addTo(liveRouteLayer);
            });
        }

        // Add all installation markers (unvisited - PURPLE)
        if (installations && installations.length > 0) {
            installations.forEach(installation => {
                // Skip if already visited
                if (visitedIds.has(installation.id)) return;

                L.marker([installation.latitude, installation.longitude], { icon: installationIcon })
                    .bindPopup(`
                        <div style="padding: 8px;">
                            <h6 class="fw-bold text-purple mb-2"><i class="bi bi-building"></i> Installation Location</h6>
                            <p class="mb-1 small"><strong>${installation.name}</strong></p>
                            <p class="mb-0 small text-muted">${installation.address || ''}</p>
                            <p class="mb-0 mt-1"><span class="badge bg-secondary">Not Visited</span></p>
                        </div>
                    `)
                    .addTo(liveRouteLayer);
            });
        }

        // Add stop markers
        if (stops && stops.length > 0) {
            stops.forEach((stop, index) => {
                L.marker([stop.latitude, stop.longitude], { icon: stopIcon })
                    .bindPopup(`
                        <div style="padding: 8px;">
                            <h6 class="fw-bold text-warning mb-2"><i class="bi bi-pause-circle-fill"></i> Stop</h6>
                            <p class="mb-0 small">Duration: ${stop.duration_minutes || 'In progress'} min</p>
                        </div>
                    `)
                    .addTo(liveRouteLayer);
            });
        }

        // Fit map to route
        const routeBounds = locations.map(loc => [loc.latitude, loc.longitude]);
        if (routeBounds.length > 0) {
            liveMap.fitBounds(routeBounds, { padding: [50, 50] });
        }
    }

    // AI-enhanced live map route with intelligent filtering
    async function buildRealisticRouteOnLiveMap(locations, layer) {
        console.log('=================================================');
        console.log('🎨 LIVE MAP - BUILDING ROUTE');
        console.log('=================================================');
        console.log('Total locations:', locations.length);

        if (!locations || locations.length === 0) {
            console.error('❌ No locations to render on live map');
            return;
        }

        // SIMPLE TEST: Draw basic polyline first
        const allCoords = locations.map(loc => [loc.latitude, loc.longitude]);
        console.log('Drawing test polyline with', allCoords.length, 'points');

        const testLine = L.polyline(allCoords, {
            color: '#FF0000',
            weight: 8,
            opacity: 0.9
        }).addTo(layer);
        console.log('✅ Test polyline added');

        // Draw colored segments
        const segments = groupByMovementState(locations);
        console.log('✓ Created', segments.length, 'segments');

        segments.forEach((segment, i) => {
            if (segment.points.length < 1) return;

            const coords = segment.points.map(p => [p.latitude, p.longitude]);

            if (segment.type === 'stopped' && segment.points.length <= 3) {
                const centerLat = segment.points.reduce((sum, p) => sum + p.latitude, 0) / segment.points.length;
                const centerLng = segment.points.reduce((sum, p) => sum + p.longitude, 0) / segment.points.length;

                L.circle([centerLat, centerLng], {
                    radius: 15,
                    color: '#ef4444',
                    fillColor: '#ef4444',
                    fillOpacity: 0.3,
                    weight: 2
                }).addTo(layer);
            } else {
                L.polyline(coords, {
                    color: segment.color,
                    weight: segment.type === 'stopped' ? 4 : 5,
                    opacity: segment.type === 'stopped' ? 0.6 : 0.8,
                    smoothFactor: 1.0,
                    lineCap: 'round',
                    lineJoin: 'round',
                    dashArray: segment.type === 'stopped' ? '5, 10' : null
                }).addTo(layer);
            }
        });

        console.log('✅ Route rendered on live map');
        console.log('=================================================');
    }

    // Back to all riders view
    function backToAllRiders() {
        isViewingRiderHistory = false;
        currentViewingRiderId = null;

        // Clear route layer
        if (liveRouteLayer) {
            liveMap.removeLayer(liveRouteLayer);
            liveRouteLayer = null;
        }

        // Hide back button
        document.getElementById('backToLiveView').style.display = 'none';
        document.getElementById('liveStatusText').textContent = 'Click refresh to update';

        // Reload all riders
        loadLiveRiders();
    }

    // Load history button
    document.getElementById('loadHistory').addEventListener('click', loadWorkHistory);

    function loadWorkHistory() {
        const riderId = document.getElementById('riderFilter').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!riderId) {
            alert('Please select a rider');
            return;
        }

        showLoading();

        fetch(`/tracking/historical?rider_id=${riderId}&start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                renderWorkTimeline(data);
                renderRouteOnMap(data);
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                alert('Failed to load tracking data');
            });
    }

    function renderWorkTimeline(data) {
        const { locations, stops, visits, sessions } = data;

        if (!sessions || sessions.length === 0) {
            document.getElementById('timelineContent').innerHTML = `
                <div class="no-data-state">
                    <i class="bi bi-calendar-x"></i>
                    <p class="fw-semibold">No work sessions found</p>
                    <small>No duty sessions for selected date range</small>
                </div>
            `;
            document.getElementById('workSummary').style.display = 'none';
            return;
        }

        // Calculate totals
        const totalMinutes = sessions.reduce((sum, s) => sum + (s.duration_minutes || 0), 0);
        const totalHours = Math.floor(totalMinutes / 60);
        const remainingMinutes = totalMinutes % 60;

        // Show work summary
        document.getElementById('workSummary').style.display = 'block';
        document.getElementById('workSummary').innerHTML = `
            <div class="work-summary-item">
                <strong>Total Duration</strong>
                <span class="value">${totalHours}h ${remainingMinutes}m</span>
            </div>
            <div class="work-summary-item">
                <strong>Sessions</strong>
                <span class="value">${sessions.length}</span>
            </div>
            <div class="work-summary-item">
                <strong>Activities</strong>
                <span class="value">${stops.length + visits.length}</span>
            </div>
        `;

        // Build timeline with session cards
        let timelineHTML = '';

        sessions.forEach((session, sessionIndex) => {
            const sessionStops = stops.filter(s => s.duty_session_id === session.id);
            const sessionVisits = visits.filter(v => v.duty_session_id === session.id);
            const sessionLocations = locations.filter(loc => loc.duty_session_id === session.id);

            const startTime = new Date(session.started_at);
            const endTime = session.ended_at ? new Date(session.ended_at) : new Date();
            const durationMins = session.duration_minutes || 0;
            const durationHrs = Math.floor(durationMins / 60);
            const durationMinsRem = durationMins % 60;

            // Session card
            timelineHTML += `
                <div class="session-card">
                    <div class="session-header">
                        <div>
                            <div class="session-time-range">
                                <i class="bi bi-clock-history"></i>
                                ${formatTime(session.started_at)} - ${session.ended_at ? formatTime(session.ended_at) : 'Ongoing'}
                            </div>
                        </div>
                        <div class="session-duration-badge">
                            <i class="bi bi-hourglass-split"></i> ${durationHrs}h ${durationMinsRem}m
                        </div>
                    </div>

                    <div class="session-stats">
                        <div class="session-stat">
                            <div class="session-stat-label">Stops</div>
                            <div class="session-stat-value">${sessionStops.length}</div>
                        </div>
                        <div class="session-stat">
                            <div class="session-stat-label">Visits</div>
                            <div class="session-stat-value">${sessionVisits.length}</div>
                        </div>
                        <div class="session-stat">
                            <div class="session-stat-label">GPS Points</div>
                            <div class="session-stat-value">${sessionLocations.length}</div>
                        </div>
                    </div>

                    <div style="margin-top: 8px;">
            `;

            // Start event
            timelineHTML += `
                <div class="timeline-item">
                    <div class="timeline-dot start"><i class="bi bi-play-fill"></i></div>
                    <div class="timeline-content">
                        <div class="timeline-time">
                            <i class="bi bi-clock"></i>
                            ${formatTime(session.started_at)}
                        </div>
                        <div class="timeline-title">Started Duty</div>
                        <div class="timeline-meta">
                            <span class="timeline-badge">
                                <i class="bi bi-calendar3"></i> ${formatDate(session.started_at)}
                            </span>
                        </div>
                    </div>
                </div>
            `;

            // Combine and sort stops and visits by time
            const activities = [
                ...sessionStops.map(s => ({type: 'stop', data: s, time: new Date(s.started_at)})),
                ...sessionVisits.map(v => ({type: 'visit', data: v, time: new Date(v.arrived_at)}))
            ].sort((a, b) => a.time - b.time);

            // Render activities
            activities.forEach(activity => {
                if (activity.type === 'stop') {
                    const stop = activity.data;
                    timelineHTML += `
                        <div class="timeline-item" onclick="showStopDetails(${JSON.stringify(stop).replace(/"/g, '&quot;')})" style="cursor: pointer;">
                            <div class="timeline-dot stop"><i class="bi bi-pause-fill"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-time">
                                    <i class="bi bi-clock"></i>
                                    ${formatTime(stop.started_at)}
                                </div>
                                <div class="timeline-title">Stop / Break</div>
                                <div class="timeline-meta">
                                    <span class="timeline-badge duration">
                                        <i class="bi bi-hourglass-split"></i> ${stop.duration_minutes || 0} min
                                    </span>
                                    ${stop.ended_at ? `
                                        <span class="timeline-badge">
                                            <i class="bi bi-check-circle"></i> Ended ${formatTime(stop.ended_at)}
                                        </span>
                                    ` : `
                                        <span class="timeline-badge" style="background: #fef3c7; color: #92400e;">
                                            <i class="bi bi-three-dots"></i> In Progress
                                        </span>
                                    `}
                                </div>
                            </div>
                        </div>
                    `;
                } else if (activity.type === 'visit') {
                    const visit = activity.data;
                    timelineHTML += `
                        <div class="timeline-item" onclick="showVisitDetails(${JSON.stringify(visit).replace(/"/g, '&quot;')})" style="cursor: pointer;">
                            <div class="timeline-dot visit"><i class="bi bi-building"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-time">
                                    <i class="bi bi-clock"></i>
                                    ${formatTime(visit.arrived_at)}
                                </div>
                                <div class="timeline-title">Installation Visit</div>
                                <div class="timeline-location">
                                    <i class="bi bi-pin-map-fill"></i>
                                    <strong>${visit.installation_name || 'Installation'}</strong>
                                </div>
                                <div class="timeline-meta">
                                    <span class="timeline-badge duration">
                                        <i class="bi bi-hourglass-split"></i> ${visit.duration_minutes || 0} min
                                    </span>
                                    <span class="timeline-badge" style="background: #dcfce7; color: #166534;">
                                        <i class="bi bi-check-circle-fill"></i> ${visit.status || 'Completed'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });

            // End event
            if (session.ended_at) {
                timelineHTML += `
                    <div class="timeline-item">
                        <div class="timeline-dot end"><i class="bi bi-stop-fill"></i></div>
                        <div class="timeline-content">
                            <div class="timeline-time">
                                <i class="bi bi-clock"></i>
                                ${formatTime(session.ended_at)}
                            </div>
                            <div class="timeline-title">Ended Duty</div>
                            <div class="timeline-meta">
                                <span class="timeline-badge">
                                    <i class="bi bi-check-circle"></i> Session Completed
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Check if currently online
                let isOnline = false;
                if (sessionLocations.length > 0) {
                    const latestLocation = sessionLocations[sessionLocations.length - 1];
                    const lastTime = new Date(latestLocation.recorded_at);
                    const minutesAgo = (new Date() - lastTime) / 1000 / 60;
                    isOnline = minutesAgo < 10;
                }

                if (isOnline) {
                    timelineHTML += `
                        <div class="timeline-item">
                            <div class="timeline-dot start"><i class="bi bi-broadcast"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-time">
                                    <i class="bi bi-circle-fill text-success" style="font-size: 0.5rem;"></i>
                                    Live Now
                                </div>
                                <div class="timeline-title">Currently Active</div>
                                <div class="timeline-meta">
                                    <span class="timeline-badge" style="background: #dcfce7; color: #166534;">
                                        <i class="bi bi-broadcast"></i> GPS Tracking Active
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    timelineHTML += `
                        <div class="timeline-item">
                            <div class="timeline-dot" style="background: #f59e0b;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-time">
                                    <i class="bi bi-wifi-off"></i>
                                    Connection Lost
                                </div>
                                <div class="timeline-title">No Recent GPS Data</div>
                                <div class="timeline-meta">
                                    <span class="timeline-badge" style="background: #fef3c7; color: #92400e;">
                                        <i class="bi bi-exclamation-circle"></i> Session still active but no tracking
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }

            timelineHTML += `
                    </div>
                </div>
            `;
        });

        document.getElementById('timelineContent').innerHTML = timelineHTML;
    }

    function formatDate(datetime) {
        return new Date(datetime).toLocaleDateString('en-MY', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }

    // AI-based realistic path prediction between GPS points
    async function renderRouteOnMap(data) {
        // Hide empty state since we're rendering data
        hideEmptyState();

        // Clear existing layers
        if (routeLayer) {
            map.removeLayer(routeLayer);
        }
        if (markersLayer) {
            map.removeLayer(markersLayer);
        }

        const { locations, stops, visits, sessions } = data;

        if (!locations || locations.length === 0) {
            showEmptyState();
            return;
        }

        // Create layers group
        markersLayer = L.layerGroup().addTo(map);
        routeLayer = L.layerGroup().addTo(map);

        console.log('🤖 AI Path Prediction: Processing', locations.length, 'GPS points');

        // Build realistic route using AI prediction
        await buildRealisticRoute(locations, routeLayer);

        // Start marker
        if (sessions && sessions[0]) {
            L.marker([locations[0].latitude, locations[0].longitude], { icon: startIcon })
                .bindPopup(`
                    <div style="padding: 8px;">
                        <h6 class="fw-bold text-success mb-2"><i class="bi bi-play-circle-fill"></i> Start Location</h6>
                        <p class="mb-1"><strong>Started:</strong> ${formatDateTime(sessions[0].started_at)}</p>
                        <p class="mb-0 small text-muted">${locations[0].latitude.toFixed(6)}, ${locations[0].longitude.toFixed(6)}</p>
                    </div>
                `)
                .addTo(markersLayer);
        }

        // End/Last seen marker
        if (locations && locations.length > 0) {
            const lastLocation = locations[locations.length - 1];
            const session = sessions && sessions[0];
            const hasEnded = session && session.ended_at;
            const statusText = hasEnded ? 'End Location' : 'Last Seen';
            const statusIcon = hasEnded ? 'stop-circle-fill' : 'clock-history';
            const statusColor = hasEnded ? 'secondary' : 'primary';

            L.marker([lastLocation.latitude, lastLocation.longitude], { icon: endIcon })
                .bindPopup(`
                    <div style="padding: 8px;">
                        <h6 class="fw-bold text-${statusColor} mb-2">
                            <i class="bi bi-${statusIcon}"></i> ${statusText}
                        </h6>
                        <p class="mb-1"><strong>Time:</strong> ${lastLocation.recorded_at_human}</p>
                        ${hasEnded ? `<p class="mb-1"><strong>Duration:</strong> ${Math.floor(session.duration_minutes / 60)}h ${session.duration_minutes % 60}m</p>` : ''}
                        <p class="mb-0 small text-muted">${lastLocation.latitude.toFixed(6)}, ${lastLocation.longitude.toFixed(6)}</p>
                    </div>
                `)
                .addTo(markersLayer);
        }

        // Stop markers
        stops.forEach(stop => {
            L.marker([stop.latitude, stop.longitude], { icon: stopIcon })
                .bindPopup(`
                    <div style="padding: 8px; min-width: 200px;">
                        <h6 class="fw-bold text-warning mb-2"><i class="bi bi-pause-circle-fill"></i> Stop / Break</h6>
                        <p class="mb-1"><strong>Started:</strong> ${formatTime(stop.started_at)}</p>
                        <p class="mb-1"><strong>Duration:</strong> ${stop.duration_minutes} minutes</p>
                        ${stop.ended_at ? `<p class="mb-0"><strong>Ended:</strong> ${formatTime(stop.ended_at)}</p>` : '<p class="mb-0 text-muted">Ongoing...</p>'}
                    </div>
                `)
                .addTo(markersLayer);
        });

        // Visit markers (visited installations - RED)
        const visitedIds = new Set();
        visits.forEach(visit => {
            visitedIds.add(visit.installation_location_id || visit.id);
            L.marker([visit.latitude, visit.longitude], { icon: visitIcon })
                .bindPopup(`
                    <div style="padding: 8px; min-width: 200px;">
                        <h6 class="fw-bold text-danger mb-2"><i class="bi bi-check-circle-fill"></i> Visited Installation</h6>
                        <p class="mb-1"><strong>Location:</strong> ${visit.installation_name}</p>
                        <p class="mb-1"><strong>Arrived:</strong> ${formatTime(visit.arrived_at)}</p>
                        <p class="mb-1"><strong>Duration:</strong> ${visit.duration_minutes} minutes</p>
                        <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">${visit.status}</span></p>
                    </div>
                `)
                .addTo(markersLayer);
        });

        // All installation markers (unvisited - PURPLE)
        if (data.installations) {
            data.installations.forEach(installation => {
                // Skip if already visited (don't show duplicate)
                if (visitedIds.has(installation.id)) return;

                L.marker([installation.latitude, installation.longitude], { icon: installationIcon })
                    .bindPopup(`
                        <div style="padding: 8px; min-width: 200px;">
                            <h6 class="fw-bold text-purple mb-2"><i class="bi bi-building"></i> Installation Location</h6>
                            <p class="mb-1"><strong>${installation.name}</strong></p>
                            <p class="mb-0 small text-muted">${installation.address || 'No address'}</p>
                            <p class="mb-0 mt-2 small"><span class="badge bg-secondary">Not Visited</span></p>
                        </div>
                    `)
                    .addTo(markersLayer);
            });
        }

        // Fit map to show all locations (use timeout to allow route to load)
        setTimeout(() => {
            if (locations.length > 0) {
                const bounds = L.latLngBounds(locations.map(l => [l.latitude, l.longitude]));
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }, 500);
    }

    // AI-enhanced route with intelligent filtering and smoothing
    async function buildRealisticRoute(locations, routeLayer) {
        console.log('=================================================');
        console.log('🎨 BUILDING ROUTE - START');
        console.log('=================================================');
        console.log('Total locations received:', locations.length);
        console.log('First 5 locations:', locations.slice(0, 5));
        console.log('Last 5 locations:', locations.slice(-5));

        if (!locations || locations.length === 0) {
            console.error('❌ No locations to render');
            hideLoading();
            return;
        }

        // SIMPLE TEST: Draw a basic polyline with ALL points first
        console.log('🧪 TEST: Drawing simple polyline with all points...');
        const allCoords = locations.map(loc => [loc.latitude, loc.longitude]);
        console.log('Coordinates array length:', allCoords.length);
        console.log('First 3 coords:', allCoords.slice(0, 3));

        // Draw a thick red test line
        const testLine = L.polyline(allCoords, {
            color: '#FF0000',
            weight: 8,
            opacity: 0.9
        }).addTo(routeLayer);
        console.log('✅ Test polyline added:', testLine);
        console.log('Test polyline bounds:', testLine.getBounds());

        // ALSO draw colored segments
        console.log('🎨 Drawing colored segments...');
        const segments = groupByMovementState(locations);
        console.log('✓ Created', segments.length, 'route segments');

        let polylinesDrawn = 0;
        let circlesDrawn = 0;

        segments.forEach((segment, segIndex) => {
            if (segment.points.length < 1) {
                console.warn(`⚠️ Segment ${segIndex} has no points, skipping`);
                return;
            }

            const coords = segment.points.map(p => [p.latitude, p.longitude]);
            console.log(`📍 Segment ${segIndex}: ${segment.type}, ${coords.length} coords, color: ${segment.color}`);

            if (segment.type === 'stopped' && segment.points.length <= 3) {
                const centerLat = segment.points.reduce((sum, p) => sum + p.latitude, 0) / segment.points.length;
                const centerLng = segment.points.reduce((sum, p) => sum + p.longitude, 0) / segment.points.length;

                L.circle([centerLat, centerLng], {
                    radius: 15,
                    color: '#ef4444',
                    fillColor: '#ef4444',
                    fillOpacity: 0.3,
                    weight: 2
                }).addTo(routeLayer);
                circlesDrawn++;
            } else {
                const polyline = L.polyline(coords, {
                    color: segment.color,
                    weight: segment.type === 'stopped' ? 4 : 5,
                    opacity: segment.type === 'stopped' ? 0.6 : 0.8,
                    smoothFactor: 1.0,
                    lineCap: 'round',
                    lineJoin: 'round',
                    dashArray: segment.type === 'stopped' ? '5, 10' : null
                }).addTo(routeLayer);
                polylinesDrawn++;
            }
        });

        console.log(`✅ Route rendered: ${polylinesDrawn} polylines, ${circlesDrawn} circles`);
        console.log('✅ Route layer total layers:', routeLayer.getLayers().length);
        console.log('=================================================');
        hideLoading();
    }

    // STRICT GPS filtering - only accept high-quality points
    function filterGPSNoise(points) {
        if (!points || points.length === 0) return [];

        const filtered = [];

        for (let i = 0; i < points.length; i++) {
            const current = points[i];

            // 1. STRICT ACCURACY: Only accept GPS with accuracy < 20 meters
            if (current.accuracy && current.accuracy > 20) {
                console.log(`Filtered point ${i}: Poor accuracy ${current.accuracy.toFixed(1)}m`);
                continue;
            }

            // 2. Validate coordinates
            if (!current.latitude || !current.longitude ||
                current.latitude === 0 || current.longitude === 0) {
                console.log(`Filtered point ${i}: Invalid coordinates`);
                continue;
            }

            // 3. Check against previous point if exists
            if (filtered.length > 0) {
                const prev = filtered[filtered.length - 1];
                const distFromPrev = calculateDistance(
                    prev.latitude, prev.longitude,
                    current.latitude, current.longitude
                );

                // Skip if too close and stopped (GPS drift)
                if (distFromPrev < 0.003) { // < 3 meters
                    const speedKmh = (current.speed || 0) * 3.6;
                    if (speedKmh < 1) {
                        console.log(`Filtered point ${i}: GPS drift ${distFromPrev.toFixed(4)}km`);
                        continue;
                    }
                }

                // Filter impossible jumps (teleportation)
                // Max realistic: 80 km/h for 60s = 1.33 km
                if (distFromPrev > 1.5) { // > 1.5 km jump
                    console.warn(`Filtered point ${i}: Impossible jump ${distFromPrev.toFixed(3)}km`);
                    continue;
                }

                // Calculate implied speed
                const timeDiff = 60; // Assume max 60 seconds between points
                const impliedSpeedKmh = (distFromPrev / timeDiff) * 3600;

                // Filter impossible speeds (>100 km/h for delivery rider)
                if (impliedSpeedKmh > 100) {
                    console.warn(`Filtered point ${i}: Impossible speed ${impliedSpeedKmh.toFixed(1)} km/h`);
                    continue;
                }

                // Check for accuracy degradation
                if (prev.accuracy && current.accuracy &&
                    current.accuracy > prev.accuracy * 2.5) {
                    console.log(`Filtered point ${i}: Accuracy degraded from ${prev.accuracy.toFixed(1)}m to ${current.accuracy.toFixed(1)}m`);
                    continue;
                }
            }

            // Point passed all validation - keep it
            filtered.push(current);
        }

        console.log(`GPS Filtering: ${points.length} → ${filtered.length} points (${((1 - filtered.length/points.length) * 100).toFixed(1)}% filtered)`);
        return filtered;
    }

    // Simplify path using Douglas-Peucker algorithm
    function simplifyPath(points, tolerance) {
        if (!points || points.length < 3) return points;

        // Douglas-Peucker algorithm
        function douglasPeucker(points, tolerance) {
            if (!points || points.length < 3) return points;

            let maxDistance = 0;
            let maxIndex = 0;

            // Find point with maximum distance from line
            for (let i = 1; i < points.length - 1; i++) {
                const distance = perpendicularDistance(
                    points[i],
                    points[0],
                    points[points.length - 1]
                );

                if (distance > maxDistance) {
                    maxDistance = distance;
                    maxIndex = i;
                }
            }

            // If max distance is greater than tolerance, recursively simplify
            if (maxDistance > tolerance) {
                const left = douglasPeucker(points.slice(0, maxIndex + 1), tolerance);
                const right = douglasPeucker(points.slice(maxIndex), tolerance);

                return left.slice(0, -1).concat(right);
            } else {
                return [points[0], points[points.length - 1]];
            }
        }

        return douglasPeucker(points, tolerance);
    }

    // Calculate perpendicular distance from point to line
    function perpendicularDistance(point, lineStart, lineEnd) {
        const x = point.latitude;
        const y = point.longitude;
        const x1 = lineStart.latitude;
        const y1 = lineStart.longitude;
        const x2 = lineEnd.latitude;
        const y2 = lineEnd.longitude;

        const A = x - x1;
        const B = y - y1;
        const C = x2 - x1;
        const D = y2 - y1;

        const dot = A * C + B * D;
        const lenSq = C * C + D * D;
        let param = -1;

        if (lenSq !== 0) param = dot / lenSq;

        let xx, yy;

        if (param < 0) {
            xx = x1;
            yy = y1;
        } else if (param > 1) {
            xx = x2;
            yy = y2;
        } else {
            xx = x1 + param * C;
            yy = y1 + param * D;
        }

        const dx = x - xx;
        const dy = y - yy;

        return Math.sqrt(dx * dx + dy * dy);
    }

    // Group points by movement state
    function groupByMovementState(points) {
        if (points.length === 0) return [];

        const segments = [];
        let currentSegment = {
            type: 'moving',
            color: getSpeedColor(points[0]),
            points: [points[0]]
        };

        for (let i = 1; i < points.length; i++) {
            const point = points[i];
            const speedKmh = (point.speed || 0) * 3.6;
            const color = getSpeedColor(point);
            const isStopped = speedKmh < 2;

            // Check if we should start a new segment
            const typeChanged = (isStopped && currentSegment.type === 'moving') ||
                              (!isStopped && currentSegment.type === 'stopped');
            const colorChanged = !isStopped && color !== currentSegment.color;

            if (typeChanged || colorChanged) {
                // Save current segment
                if (currentSegment.points.length > 0) {
                    segments.push(currentSegment);
                }

                // Start new segment
                currentSegment = {
                    type: isStopped ? 'stopped' : 'moving',
                    color: color,
                    points: [point]
                };
            } else {
                currentSegment.points.push(point);
            }
        }

        // Add final segment
        if (currentSegment.points.length > 0) {
            segments.push(currentSegment);
        }

        return segments;
    }

    // Calculate distance between two GPS points (km)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    // VIBRANT COLOR PALETTE for AI-powered visualization (matches mobile app)
    function getSpeedColor(loc) {
        const speedKmh = (loc.speed || 0) * 3.6;
        if (speedKmh < 2) return '#DC2626';      // Bright Red - Stopped
        if (speedKmh < 10) return '#F59E0B';     // Amber - Slow
        if (speedKmh < 30) return '#10B981';     // Emerald - Normal
        if (speedKmh < 60) return '#3B82F6';     // Blue - Fast
        return '#8B5CF6';                         // Purple - Highway
    }

    function getActivityLabel(speedKmh) {
        if (speedKmh < 2) return 'Stopped';
        if (speedKmh < 10) return 'Slow (<10 km/h)';
        if (speedKmh < 30) return 'Normal (10-30 km/h)';
        if (speedKmh < 60) return 'Fast (30-60 km/h)';
        return 'Highway (>60 km/h)';
    }


    function showStopDetails(stop) {
        document.getElementById('stopModalContent').innerHTML = `
            <div class="stop-info-grid">
                <div class="stop-info-item">
                    <div class="stop-info-label">Started At</div>
                    <div class="stop-info-value">${formatTime(stop.started_at)}</div>
                </div>
                <div class="stop-info-item">
                    <div class="stop-info-label">Duration</div>
                    <div class="stop-info-value">${stop.duration_minutes} min</div>
                </div>
                <div class="stop-info-item">
                    <div class="stop-info-label">Ended At</div>
                    <div class="stop-info-value">${stop.ended_at ? formatTime(stop.ended_at) : 'Ongoing'}</div>
                </div>
                <div class="stop-info-item">
                    <div class="stop-info-label">Location</div>
                    <div class="stop-info-value" style="font-size: 0.875rem;">${stop.latitude.toFixed(6)}, ${stop.longitude.toFixed(6)}</div>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary btn-sm" onclick="map.flyTo([${stop.latitude}, ${stop.longitude}], 17); bootstrap.Modal.getInstance(document.getElementById('stopModal')).hide();">
                    <i class="bi bi-geo-alt-fill"></i> View on Map
                </button>
            </div>
        `;
        new bootstrap.Modal(document.getElementById('stopModal')).show();
    }

    function showVisitDetails(visit) {
        document.getElementById('stopModalContent').innerHTML = `
            <div class="stop-info-grid">
                <div class="stop-info-item" style="grid-column: 1 / -1;">
                    <div class="stop-info-label">Installation</div>
                    <div class="stop-info-value">${visit.installation_name}</div>
                </div>
                <div class="stop-info-item">
                    <div class="stop-info-label">Arrived</div>
                    <div class="stop-info-value">${formatTime(visit.arrived_at)}</div>
                </div>
                <div class="stop-info-item">
                    <div class="stop-info-label">Duration</div>
                    <div class="stop-info-value">${visit.duration_minutes} min</div>
                </div>
                <div class="stop-info-item">
                    <div class="stop-info-label">Status</div>
                    <div class="stop-info-value"><span class="badge bg-success">${visit.status}</span></div>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary btn-sm" onclick="map.flyTo([${visit.latitude}, ${visit.longitude}], 17); bootstrap.Modal.getInstance(document.getElementById('stopModal')).hide();">
                    <i class="bi bi-geo-alt-fill"></i> View on Map
                </button>
            </div>
        `;
        new bootstrap.Modal(document.getElementById('stopModal')).show();
    }

    function formatTime(datetime) {
        return new Date(datetime).toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' });
    }

    function formatDateTime(datetime) {
        return new Date(datetime).toLocaleString('en-MY', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function showLoading() {
        document.getElementById('mapEmptyState').style.display = 'none';
        document.getElementById('mapLoading').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('mapLoading').style.display = 'none';
    }

    function showEmptyState() {
        document.getElementById('mapEmptyState').style.display = 'flex';
        document.getElementById('mapLoading').style.display = 'none';
    }

    function hideEmptyState() {
        document.getElementById('mapEmptyState').style.display = 'none';
    }
</script>
@endpush
@endsection
