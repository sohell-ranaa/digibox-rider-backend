@extends('layouts.app')

@section('title', 'Rider Details')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}"><i class="bi bi-house-door"></i> Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('riders.index') }}"><i class="bi bi-people-fill"></i> Riders</a></li>
<li class="breadcrumb-item active" aria-current="page"><i class="bi bi-person-badge"></i> {{ $rider->name }}</li>
@endsection

@push('styles')
<style>
    .page-header {
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .rider-profile-card {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }

    .rider-header {
        display: flex;
        align-items: center;
        gap: 24px;
        padding-bottom: 24px;
        border-bottom: 2px solid #f3f4f6;
        margin-bottom: 24px;
    }

    .rider-avatar-large {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563EB, #1d4ed8);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        font-weight: 700;
        border: 5px solid #eff6ff;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        position: relative;
        flex-shrink: 0;
    }

    .online-indicator-large {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 4px solid white;
    }

    .online-indicator-large.online {
        background: #10b981;
        box-shadow: 0 0 16px rgba(16, 185, 129, 0.8);
        animation: pulse 2s infinite;
    }

    .online-indicator-large.offline {
        background: #9ca3af;
    }

    .rider-info {
        flex: 1;
    }

    .rider-name-large {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .rider-username-large {
        font-size: 1rem;
        color: #6b7280;
        font-family: monospace;
        margin-bottom: 12px;
    }

    .status-badges {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .status-badge-large {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .status-badge-large.active {
        background: #d1fae5;
        color: #065f46;
    }

    .status-badge-large.inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-badge-large.on-duty {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: #065f46;
        border: 1px solid #6ee7b7;
    }

    .status-badge-large.off-duty {
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .status-badge-large.on-duty-offline {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #92400e;
        border: 1px solid #fbbf24;
    }

    .status-badge-large .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .status-badge-large.active .dot {
        background: #10b981;
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    }

    .status-badge-large.inactive .dot {
        background: #ef4444;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .stat-card {
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        transition: all 0.3s;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.1);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .stat-card:hover::before {
        opacity: 1;
    }

    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.25);
    }

    .stat-card .icon-box {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 16px;
    }

    .stat-card .label {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 8px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card .value {
        font-size: 2rem;
        font-weight: 800;
        color: #111827;
        line-height: 1.2;
    }

    .stat-card .subtext {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 6px;
        font-weight: 500;
    }

    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }

    .filter-tabs {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .filter-tab {
        padding: 10px 20px;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        background: white;
        color: #6b7280;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .filter-tab:hover {
        border-color: #2563EB;
        color: #2563EB;
    }

    .filter-tab.active {
        background: #2563EB;
        border-color: #2563EB;
        color: white;
    }

    .performance-table {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }

    .table {
        margin-bottom: 0;
    }

    .table thead th {
        background: #f9fafb;
        color: #374151;
        font-weight: 700;
        border-bottom: 2px solid #e5e7eb;
        padding: 16px;
    }

    .table tbody tr {
        transition: all 0.2s;
    }

    .table tbody tr:hover {
        background: #f9fafb;
    }

    .table tbody td {
        padding: 16px;
        vertical-align: middle;
    }

    .badge-metric {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .badge-metric.sessions {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-metric.hours {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-metric.distance {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-metric.locations {
        background: #e0e7ff;
        color: #3730a3;
    }

    .danger-zone {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 2px solid #fee2e2;
    }

    .danger-zone h5 {
        color: #991b1b;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .btn-deactivate {
        background: #ef4444;
        color: white;
        border: none;
        padding: 12px 32px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-deactivate:hover {
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .btn-deactivate:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #9ca3af;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 16px;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 991px) {
        .page-header {
            padding: 20px 16px;
        }
        .page-header .d-flex {
            flex-direction: column;
            gap: 16px;
            align-items: flex-start !important;
        }
        .page-header .btn {
            width: 100%;
        }
        .rider-header {
            flex-direction: column;
            text-align: center;
            gap: 16px;
        }
        .rider-info {
            width: 100%;
        }
        .status-badges {
            justify-content: center;
        }
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .stat-card {
            padding: 20px;
        }
        .stat-card .value {
            font-size: 1.75rem;
        }
        .filter-section {
            padding: 16px;
        }
        .filter-tabs {
            justify-content: center;
        }
        .filter-tab {
            flex: 1;
            text-align: center;
            padding: 8px 12px;
            font-size: 0.875rem;
        }
        .performance-table {
            padding: 16px;
        }
        .table {
            font-size: 0.875rem;
        }
        .table thead th {
            padding: 12px 8px;
            font-size: 0.8rem;
        }
        .table tbody td {
            padding: 12px 8px;
        }
        .badge-metric {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
        .danger-zone {
            padding: 20px 16px;
        }
        .btn-deactivate {
            width: 100%;
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
        .rider-profile-card {
            padding: 20px 16px;
        }
        .rider-avatar-large {
            width: 80px;
            height: 80px;
            font-size: 2rem;
        }
        .online-indicator-large {
            width: 20px;
            height: 20px;
            border: 3px solid white;
        }
        .rider-name-large {
            font-size: 1.25rem;
        }
        .rider-username-large {
            font-size: 0.875rem;
        }
        .status-badge-large {
            padding: 6px 12px;
            font-size: 0.75rem;
        }
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .stat-card {
            padding: 16px;
        }
        .stat-card .icon-box {
            width: 48px;
            height: 48px;
            font-size: 20px;
        }
        .stat-card .value {
            font-size: 1.5rem;
        }
        .stat-card .label {
            font-size: 0.75rem;
        }
        .stat-card .subtext {
            font-size: 0.7rem;
        }
        .filter-tab {
            font-size: 0.8rem;
            padding: 6px 10px;
        }
        .performance-table {
            padding: 12px;
        }
        .table {
            font-size: 0.75rem;
        }
        .table thead th {
            padding: 10px 6px;
            font-size: 0.7rem;
        }
        .table tbody td {
            padding: 10px 6px;
        }
        .badge-metric {
            padding: 3px 6px;
            font-size: 0.7rem;
        }
        .badge-metric i {
            font-size: 0.7rem;
        }
    }

    @media (max-width: 575px) {
        .rider-profile-card {
            padding: 16px 12px;
        }
        .rider-header {
            padding-bottom: 16px;
            margin-bottom: 16px;
        }
        .stat-card .icon-box {
            width: 40px;
            height: 40px;
            font-size: 18px;
            margin-bottom: 12px;
        }
        .filter-tabs {
            flex-direction: column;
        }
        .filter-tab {
            width: 100%;
        }
        /* Hide some table columns on very small screens */
        .table thead th:nth-child(6),
        .table tbody td:nth-child(6),
        .table thead th:nth-child(7),
        .table tbody td:nth-child(7) {
            display: none;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <a href="{{ route('riders.index') }}" class="btn btn-light me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="bi bi-person-badge text-primary"></i> Rider Details
                    </h4>
                    <p class="text-muted mb-0">Performance overview and activity history</p>
                </div>
            </div>
            <a href="{{ route('riders.edit', $rider) }}" class="btn btn-primary">
                <i class="bi bi-pencil-fill"></i> Edit Rider
            </a>
        </div>
    </div>

    <!-- Rider Profile Card -->
    <div class="rider-profile-card">
        <div class="rider-header">
            <div class="rider-avatar-large">
                {{ strtoupper(substr($rider->name, 0, 1)) }}
                <span class="online-indicator-large {{ $isOnline ? 'online' : 'offline' }}"
                      title="{{ $isOnline ? 'Online' : 'Offline' }}"></span>
            </div>
            <div class="rider-info">
                <div class="rider-name-large">{{ $rider->name }}</div>
                <div class="rider-username-large">{{ '@' . $rider->username }}</div>
                <div class="status-badges">
                    @if($rider->is_active)
                        <span class="status-badge-large active">
                            <span class="dot"></span> Active Account
                        </span>
                    @else
                        <span class="status-badge-large inactive">
                            <span class="dot"></span> Inactive Account
                        </span>
                    @endif

                    @if($isOnline)
                        <span class="status-badge-large on-duty" title="Connected">
                            <i class="bi bi-wifi" style="font-size: 12px;"></i> Online
                        </span>
                    @else
                        <span class="status-badge-large off-duty" title="Disconnected">
                            <i class="bi bi-wifi-off" style="font-size: 12px;"></i> Offline
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-telephone-fill text-primary"></i>
                    <div>
                        <small class="text-muted d-block">Phone</small>
                        <strong>{{ $rider->phone ?? 'Not provided' }}</strong>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-envelope-fill text-primary"></i>
                    <div>
                        <small class="text-muted d-block">Email</small>
                        <strong style="word-break: break-all; font-size: 0.9rem;">{{ $rider->email ?? 'Not provided' }}</strong>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i>
                    <div>
                        <small class="text-muted d-block">Last Online</small>
                        @if($lastSeen)
                            <strong>{{ $lastSeen->format('M d, h:i A') }}</strong>
                            <small class="text-muted d-block" style="font-size: 0.75rem;">{{ $lastSeen->diffForHumans() }}</small>
                        @else
                            <strong class="text-muted">Never</strong>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-check text-primary"></i>
                    <div>
                        <small class="text-muted d-block">Member Since</small>
                        <strong>{{ $rider->created_at->format('M d, Y') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info: Active Duty Session but Offline -->
    @if($currentSession && !$isOnline)
    <div class="alert" style="background: #f9fafb; border-left: 3px solid #d1d5db; border-radius: 8px; padding: 12px 16px;">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-info-circle" style="font-size: 1.2rem; color: #6b7280;"></i>
            <div>
                <small style="color: #6b7280;">
                    Active duty session - Currently offline (last seen: {{ $lastSeen ? $lastSeen->diffForHumans() : 'Never' }})
                </small>
            </div>
        </div>
    </div>
    @endif

    <!-- Filter Tabs -->
    <div class="filter-section">
        <h6 class="fw-bold mb-3">
            <i class="bi bi-funnel text-primary me-2"></i>Performance Period
        </h6>
        <div class="filter-tabs">
            <a href="{{ route('riders.show', ['rider' => $rider, 'period' => 30]) }}"
               class="filter-tab {{ $period == 30 ? 'active' : '' }}">
                Last 30 Days
            </a>
            <a href="{{ route('riders.show', ['rider' => $rider, 'period' => 60]) }}"
               class="filter-tab {{ $period == 60 ? 'active' : '' }}">
                Last 60 Days
            </a>
            <a href="{{ route('riders.show', ['rider' => $rider, 'period' => 90]) }}"
               class="filter-tab {{ $period == 90 ? 'active' : '' }}">
                Last 90 Days
            </a>
        </div>
    </div>

    <!-- Summary Statistics -->
    <h5 class="fw-bold mb-3">
        <i class="bi bi-graph-up text-primary me-2"></i>Performance Summary (Last {{ $period }} Days)
    </h5>

    <div class="stats-grid">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="icon-box" style="background: rgba(255, 255, 255, 0.2); color: white; backdrop-filter: blur(10px);">
                <i class="bi bi-briefcase-fill"></i>
            </div>
            <div class="label" style="color: rgba(255, 255, 255, 0.9);">Total Sessions</div>
            <div class="value" style="color: white;">{{ $stats['total_sessions'] }}</div>
            <div class="subtext" style="color: rgba(255, 255, 255, 0.8);">{{ $stats['completed_sessions'] }} completed</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="icon-box" style="background: rgba(255, 255, 255, 0.2); color: white; backdrop-filter: blur(10px);">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="label" style="color: rgba(255, 255, 255, 0.9);">Total Hours</div>
            <div class="value" style="color: white;">{{ number_format($stats['total_hours'], 1) }}h</div>
            <div class="subtext" style="color: rgba(255, 255, 255, 0.8);">Working time</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="icon-box" style="background: rgba(255, 255, 255, 0.2); color: white; backdrop-filter: blur(10px);">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
            <div class="label" style="color: rgba(255, 255, 255, 0.9);">Total Distance</div>
            <div class="value" style="color: white;">{{ number_format($stats['total_distance'], 1) }}</div>
            <div class="subtext" style="color: rgba(255, 255, 255, 0.8);">Kilometers traveled</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="icon-box" style="background: rgba(255, 255, 255, 0.2); color: white; backdrop-filter: blur(10px);">
                <i class="bi bi-pin-map-fill"></i>
            </div>
            <div class="label" style="color: rgba(255, 255, 255, 0.9);">Location Points</div>
            <div class="value" style="color: white;">{{ number_format($stats['total_locations']) }}</div>
            <div class="subtext" style="color: rgba(255, 255, 255, 0.8);">Tracked positions</div>
        </div>
    </div>

    <!-- Daily Performance Table -->
    <div class="performance-table">
        <h5 class="fw-bold mb-3">
            <i class="bi bi-calendar-range text-primary me-2"></i>Day-by-Day Performance
        </h5>

        @if($dailyData->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-center">Sessions</th>
                        <th class="text-center">Working Hours</th>
                        <th class="text-center">Distance (km)</th>
                        <th class="text-center">Locations</th>
                        <th>First Start</th>
                        <th>Last End</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyData as $data)
                    <tr>
                        <td>
                            <strong>{{ \Carbon\Carbon::parse($data['date'])->format('D, M d, Y') }}</strong>
                        </td>
                        <td class="text-center">
                            <span class="badge-metric sessions">
                                <i class="bi bi-briefcase"></i>
                                {{ $data['sessions'] }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge-metric hours">
                                <i class="bi bi-clock"></i>
                                {{ number_format($data['hours'], 1) }}h
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge-metric distance">
                                <i class="bi bi-geo-alt"></i>
                                {{ number_format($data['distance'], 1) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge-metric locations">
                                <i class="bi bi-pin-map"></i>
                                {{ $data['locations'] }}
                            </span>
                        </td>
                        <td>
                            @if($data['first_start'])
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($data['first_start'])->format('h:i A') }}
                                </small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td>
                            @if($data['last_end'])
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($data['last_end'])->format('h:i A') }}
                                </small>
                            @else
                                <small class="text-muted">Active</small>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <h5 class="fw-bold mb-2">No Activity Found</h5>
            <p class="text-muted">No duty sessions recorded in the last {{ $period }} days.</p>
        </div>
        @endif
    </div>

    <!-- Danger Zone -->
    @if($rider->is_active)
    <div class="danger-zone">
        <h5>
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone
        </h5>
        <p class="text-muted mb-3">
            Deactivating this rider will prevent them from logging into the mobile app.
            All their data and history will be preserved, and you can reactivate them later from the edit page.
        </p>
        <form action="{{ route('riders.destroy', $rider) }}" method="POST"
              onsubmit="return confirm('Are you sure you want to deactivate {{ $rider->name }}?\n\nThey will not be able to log in to the mobile app, but all their data will be preserved.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-deactivate">
                <i class="bi bi-x-circle-fill"></i> Deactivate Rider Account
            </button>
        </form>
    </div>
    @else
    <div class="danger-zone" style="border-color: #fef3c7;">
        <h5 style="color: #92400e;">
            <i class="bi bi-info-circle-fill me-2"></i>Account Inactive
        </h5>
        <p class="text-muted mb-3">
            This rider account is currently inactive. They cannot log in to the mobile app.
            You can reactivate them by enabling the "Active Account" option on the edit page.
        </p>
        <a href="{{ route('riders.edit', $rider) }}" class="btn btn-warning">
            <i class="bi bi-arrow-clockwise"></i> Go to Edit Page to Reactivate
        </a>
    </div>
    @endif
</div>
@endsection
