@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard - Real-Time Overview')

@section('breadcrumb')
<li class="breadcrumb-item active" aria-current="page">
    <i class="bi bi-speedometer2"></i> Dashboard
</li>
@endsection

@section('content')

{{-- ALERTS SECTION --}}
@if($longSessions > 0 || $inactiveRiders > 0)
<div class="row mb-3 g-3">
    @if($longSessions > 0)
    <div class="col-12 col-md-6">
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 24px;"></i>
            <div>
                <strong>{{$longSessions}}</strong> rider(s) have been on duty for over 12 hours.
                <a href="{{ route('riders.index') }}" class="alert-link ms-2">Check Now</a>
            </div>
        </div>
    </div>
    @endif

    @if($inactiveRiders > 0)
    <div class="col-12 col-md-6">
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="bi bi-info-circle-fill me-3" style="font-size: 24px;"></i>
            <div>
                <strong>{{$inactiveRiders}}</strong> rider(s) have had no activity in the last 7 days.
            </div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- KEY METRICS ROW --}}
<div class="row mb-4 g-2 g-md-3">
    <div class="col-6 col-md-4 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1 opacity-75">Total Riders</h6>
                    <h2 class="mb-0">{{ $totalRiders }}</h2>
                    <small class="text-success">
                        <i class="bi bi-check-circle-fill"></i> Active in system
                    </small>
                </div>
                <i class="bi bi-people-fill d-none d-md-block" style="font-size: 48px; opacity: 0.3;"></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-3">
        <div class="stat-card success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1 opacity-75">Online Now</h6>
                    <h2 class="mb-0">{{ $onlineRiders }}</h2>
                    <small class="text-white-50">
                        <i class="bi bi-circle-fill pulse" style="font-size: 8px;"></i> Currently online
                    </small>
                </div>
                <i class="bi bi-broadcast-pin d-none d-md-block" style="font-size: 48px; opacity: 0.3;"></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1 opacity-75">Today's Locations</h6>
                    <h2 class="mb-0">{{ number_format($todayLocations) }}</h2>
                    <small class="text-white-50">
                        <i class="bi bi-graph-up"></i> GPS points tracked
                    </small>
                </div>
                <i class="bi bi-geo-alt-fill d-none d-md-block" style="font-size: 48px; opacity: 0.3;"></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-3">
        <div class="stat-card info">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1 opacity-75">Installations</h6>
                    <h2 class="mb-0">{{ $totalInstallations }}</h2>
                    <small class="text-white-50">
                        <i class="bi bi-building"></i> Total sites
                    </small>
                </div>
                <i class="bi bi-building d-none d-md-block" style="font-size: 48px; opacity-0.3;"></i>
            </div>
        </div>
    </div>
</div>

{{-- TODAY'S PERFORMANCE ROW --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card" style="background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-start align-items-sm-center mb-4 gap-2">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-check me-2 text-primary"></i>Today's Performance
                    </h5>
                    <span class="badge bg-primary" style="font-size: 13px; padding: 8px 16px;">
                        <i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, F j, Y') }}
                    </span>
                </div>

                <div class="row g-2 g-md-3">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="performance-metric text-center">
                            <i class="bi bi-play-circle-fill d-block mb-3" style="font-size: 42px; color: var(--digibox-blue);"></i>
                            <h3 class="mb-1">{{ $todaySessions }}</h3>
                            <small class="text-muted fw-semibold">Duty Sessions</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="performance-metric text-center">
                            <i class="bi bi-clock-fill d-block mb-3" style="font-size: 42px; color: var(--success-green);"></i>
                            <h3 class="mb-1">{{ $todayDutyHours }}h</h3>
                            <small class="text-muted fw-semibold">Total Hours</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="performance-metric text-center">
                            <i class="bi bi-signpost-fill d-block mb-3" style="font-size: 42px; color: var(--warning-orange);"></i>
                            <h3 class="mb-1">{{ $todayDistance }}km</h3>
                            <small class="text-muted fw-semibold">Distance</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="performance-metric text-center">
                            <i class="bi bi-pause-circle-fill d-block mb-3" style="font-size: 42px; color: var(--danger-red);"></i>
                            <h3 class="mb-1">{{ $todayStops }}</h3>
                            <small class="text-muted fw-semibold">Stops</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="performance-metric text-center">
                            <i class="bi bi-door-open-fill d-block mb-3" style="font-size: 42px; color: var(--info-cyan);"></i>
                            <h3 class="mb-1">{{ $todayVisits }}</h3>
                            <small class="text-muted fw-semibold">Visits</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="performance-metric text-center">
                            <i class="bi bi-geo-alt-fill d-block mb-3" style="font-size: 42px; color: var(--purple);"></i>
                            <h3 class="mb-1">{{ number_format($todayLocations) }}</h3>
                            <small class="text-muted fw-semibold">GPS Points</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- LIVE RIDERS MAP --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card" style="border-top: 4px solid var(--success-green);">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-start align-items-sm-center mb-3 mb-md-4 gap-2">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-map me-2" style="color: var(--success-green);"></i>Live Rider Locations
                    </h5>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge" style="background: var(--success-green); font-size: 13px; padding: 6px 14px;">
                            <i class="bi bi-circle-fill pulse me-1" style="font-size: 8px;"></i>{{ $onlineRiders }} Online
                        </span>
                        <span class="badge bg-secondary" style="font-size: 13px; padding: 6px 14px;">
                            {{ $totalRiders - $onlineRiders }} Offline
                        </span>
                    </div>
                </div>

                <div id="liveMap" style="height: 500px; border-radius: 12px; overflow: hidden;"></div>

                <div class="mt-3 d-flex gap-3 flex-wrap justify-content-center">
                    <small class="text-muted">
                        <i class="bi bi-circle-fill text-success"></i> Online (last 10 min)
                    </small>
                    <small class="text-muted">
                        <i class="bi bi-circle-fill text-secondary"></i> Offline
                    </small>
                    <small class="text-muted">
                        <i class="bi bi-circle-fill" style="color: var(--digibox-blue);"></i> On duty session
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CHARTS ROW --}}
<div class="row mb-4 g-3">
    {{-- Weekly Trend --}}
    <div class="col-12 col-lg-8">
        <div class="card" style="border-top: 4px solid var(--digibox-blue);">
            <div class="card-body">
                <div class="mb-3 mb-md-4">
                    <h5 class="card-title mb-1">
                        <i class="bi bi-graph-up me-2 text-primary"></i>7-Day Trend Analysis
                    </h5>
                    <small class="text-muted d-block d-sm-inline ms-sm-2" style="font-size: 13px;">Performance over the last week</small>
                </div>
                <canvas id="weeklyChart" height="80"></canvas>
            </div>
        </div>

        {{-- Hourly Activity --}}
        <div class="card mt-4" style="border-top: 4px solid var(--purple);">
            <div class="card-body">
                <div class="mb-3 mb-md-4">
                    <h5 class="card-title mb-1">
                        <i class="bi bi-clock-history me-2" style="color: var(--purple);"></i>Hourly Activity Pattern
                    </h5>
                    <small class="text-muted d-block d-sm-inline ms-sm-2" style="font-size: 13px;">Today's location tracking breakdown</small>
                </div>
                <canvas id="hourlyChart" height="60"></canvas>
            </div>
        </div>
    </div>

    {{-- Right Column --}}
    <div class="col-12 col-lg-4">
        {{-- Month Comparison --}}
        <div class="card" style="border-top: 4px solid var(--info-cyan);">
            <div class="card-body">
                <h5 class="card-title mb-3 mb-md-4">
                    <i class="bi bi-bar-chart-line me-2" style="color: var(--info-cyan);"></i>Month-over-Month
                </h5>

                <div class="mb-4 p-3" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <i class="bi bi-play-circle-fill text-primary me-2"></i>
                            <span class="fw-bold">Duty Sessions</span>
                        </div>
                        @if($sessionsChange >= 0)
                            <span class="badge" style="background: var(--success-green); padding: 8px 14px; font-size: 13px;">
                                <i class="bi bi-arrow-up-short" style="font-size: 18px;"></i>{{ number_format($sessionsChange) }}%
                            </span>
                        @else
                            <span class="badge" style="background: var(--danger-red); padding: 8px 14px; font-size: 13px;">
                                <i class="bi bi-arrow-down-short" style="font-size: 18px;"></i>{{ number_format(abs($sessionsChange)) }}%
                            </span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">
                            <i class="bi bi-calendar2-minus"></i> Last Month
                        </small>
                        <small class="fw-bold text-dark">{{ $lastMonthSessions }}</small>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <small class="text-muted">
                            <i class="bi bi-calendar2-check"></i> This Month
                        </small>
                        <small class="fw-bold text-primary">{{ $thisMonthSessions }}</small>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 10px;">
                        <div class="progress-bar" style="background: var(--digibox-blue); width: {{ $lastMonthSessions > 0 ? min(($thisMonthSessions / $lastMonthSessions) * 100, 100) : 100 }}%; border-radius: 10px;">
                        </div>
                    </div>
                </div>

                <div class="p-3" style="background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%); border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <i class="bi bi-building text-info me-2"></i>
                            <span class="fw-bold">Installation Visits</span>
                        </div>
                        @if($visitsChange >= 0)
                            <span class="badge" style="background: var(--success-green); padding: 8px 14px; font-size: 13px;">
                                <i class="bi bi-arrow-up-short" style="font-size: 18px;"></i>{{ number_format($visitsChange) }}%
                            </span>
                        @else
                            <span class="badge" style="background: var(--danger-red); padding: 8px 14px; font-size: 13px;">
                                <i class="bi bi-arrow-down-short" style="font-size: 18px;"></i>{{ number_format(abs($visitsChange)) }}%
                            </span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">
                            <i class="bi bi-calendar2-minus"></i> Last Month
                        </small>
                        <small class="fw-bold text-dark">{{ $lastMonthVisits }}</small>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <small class="text-muted">
                            <i class="bi bi-calendar2-check"></i> This Month
                        </small>
                        <small class="fw-bold" style="color: var(--info-cyan);">{{ $thisMonthVisits }}</small>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 10px;">
                        <div class="progress-bar" style="background: var(--info-cyan); width: {{ $lastMonthVisits > 0 ? min(($thisMonthVisits / $lastMonthVisits) * 100, 100) : 100 }}%; border-radius: 10px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Riders --}}
        <div class="card mt-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 mb-md-4">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-trophy-fill me-2" style="color: #fbbf24;"></i>Top Performers
                    </h5>
                    <span class="badge bg-secondary" style="font-size: 11px;">This Month</span>
                </div>
                @forelse($topRiders as $index => $rider)
                <div class="d-flex align-items-center mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="me-3">
                        <div class="performer-rank {{ $index == 0 ? 'gold' : ($index == 1 ? 'silver' : ($index == 2 ? 'bronze' : '')) }}">
                            {{ $index + 1 }}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-bold">{{ $rider->name }}</h6>
                        <div class="d-flex gap-3">
                            <small class="text-muted">
                                <i class="bi bi-calendar-check text-primary"></i> <strong>{{ $rider->duty_sessions_count }}</strong> sessions
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-clock text-success"></i> <strong>{{ $rider->total_hours }}h</strong>
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-building text-info"></i> <strong>{{ $rider->total_visits }}</strong> visits
                            </small>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 56px; opacity: 0.2;"></i>
                    <p class="mb-0 mt-3 fw-semibold">No data this month</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ONLINE RIDERS & RECENT ACTIVITY --}}
<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card" style="border-left: 4px solid var(--success-green);">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-start align-items-sm-center mb-3 mb-md-4 gap-2">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-broadcast me-2" style="color: var(--success-green);"></i>Online Riders
                    </h5>
                    <span class="badge" style="background: var(--success-green); font-size: 13px; padding: 6px 14px;">
                        <i class="bi bi-circle-fill pulse me-1" style="font-size: 8px;"></i>{{ $onlineRidersList->count() }} Live
                    </span>
                </div>

                <div style="max-height: 500px; overflow-y: auto;">
                    @forelse($onlineRidersList as $rider)
                    <div class="d-flex align-items-start mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}" style="transition: all 0.3s;">
                        <div class="me-3">
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--success-green) 0%, #059669 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-2 fw-bold">{{ $rider->name }}</h6>
                            <div class="d-flex flex-column gap-1">
                                @if($rider->active_session)
                                <small class="text-muted">
                                    <i class="bi bi-clock-fill text-primary"></i> Duration: <strong class="text-dark">{{ $rider->session_duration }}</strong>
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-play-circle-fill text-success"></i> Started: <strong class="text-dark">{{ $rider->active_session->started_at->format('h:i A') }}</strong>
                                </small>
                                @endif
                                @if($rider->latest_location)
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt-fill text-danger"></i> Last ping: <strong class="text-dark">{{ $rider->latest_location->recorded_at->diffForHumans() }}</strong>
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-bullseye text-info"></i> Accuracy: <strong class="text-dark">{{ number_format($rider->latest_location->accuracy, 1) }}m</strong>
                                </small>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-2" style="align-self: flex-start;">
                            <span class="badge bg-success-subtle text-success" style="padding: 8px 12px; border-radius: 8px;">
                                <i class="bi bi-circle-fill" style="font-size: 6px;"></i> Online
                            </span>
                            @if($rider->active_session)
                            <span class="badge" style="background: var(--digibox-blue); padding: 6px 10px; border-radius: 8px; font-size: 11px;">
                                <i class="bi bi-briefcase-fill" style="font-size: 10px;"></i> On Duty
                            </span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-wifi-off" style="font-size: 64px; opacity: 0.2;"></i>
                        <p class="mb-0 mt-3 fw-semibold">No riders online right now</p>
                        <small>Riders will appear here when they go online</small>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card" style="border-left: 4px solid var(--digibox-blue);">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-start align-items-sm-center mb-3 mb-md-4 gap-2">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-activity me-2 text-primary"></i>Recent Activity
                    </h5>
                    <span class="badge bg-primary" style="font-size: 13px; padding: 6px 14px;">
                        <i class="bi bi-clock-history me-1"></i>Last 15 min
                    </span>
                </div>

                <div style="max-height: 500px; overflow-y: auto;">
                    @forelse($recentActivity as $location)
                    <div class="d-flex align-items-start mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}" style="transition: all 0.3s;">
                        <div class="me-3">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); display: flex; align-items: center; justify-content: center; color: var(--danger-red); font-size: 18px;">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 fw-bold">{{ $location->rider->name }}</h6>
                                <small class="badge bg-light text-dark">{{ $location->recorded_at->diffForHumans() }}</small>
                            </div>
                            <small class="text-muted d-block">
                                <i class="bi bi-pin-map"></i> {{ number_format($location->latitude, 6) }}, {{ number_format($location->longitude, 6) }}
                            </small>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-hourglass-split" style="font-size: 64px; opacity: 0.2;"></i>
                        <p class="mb-0 mt-3 fw-semibold">No recent activity</p>
                        <small>Location updates will appear here</small>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Mobile Optimized v3.1 - Scoped to content-wrapper only (not navbar) */

/* Scope box-sizing to page content only, not navbar */
.content-wrapper * {
    box-sizing: border-box;
}

/* Only apply to page content containers, not navbar */
.content-wrapper .container-fluid {
    max-width: 100%;
    overflow-x: hidden;
}

/* Alerts - Mobile Responsive */
@media (max-width: 767px) {
    .alert {
        padding: 14px 16px;
        font-size: 0.875rem;
    }
    .alert i {
        font-size: 20px !important;
    }
}

/* Stat Cards - Mobile */
@media (max-width: 991px) {
    .stat-card h2 {
        font-size: 1.75rem !important;
    }
    .stat-card h6 {
        font-size: 0.8rem;
    }
    .stat-card small {
        font-size: 0.75rem;
    }
}

@media (max-width: 767px) {
    .stat-card {
        padding: 16px !important;
    }
    .stat-card h2 {
        font-size: 1.5rem !important;
    }
    .stat-card h6 {
        font-size: 0.75rem;
    }
    .stat-card small {
        font-size: 0.7rem;
    }
}

@media (max-width: 575px) {
    .stat-card {
        padding: 14px !important;
    }
    .stat-card h2 {
        font-size: 1.35rem !important;
    }
    .stat-card h6 {
        font-size: 0.7rem;
    }
}

/* Performance Metrics - Mobile Grid */
@media (max-width: 991px) {
    .performance-metric {
        padding: 16px !important;
    }
    .performance-metric i {
        font-size: 36px !important;
    }
    .performance-metric h3 {
        font-size: 1.5rem !important;
    }
    .performance-metric small {
        font-size: 0.75rem;
    }
}

@media (max-width: 767px) {
    .performance-metric {
        padding: 14px 12px !important;
        margin-bottom: 0 !important;
    }
    .performance-metric i {
        font-size: 32px !important;
        margin-bottom: 8px !important;
    }
    .performance-metric h3 {
        font-size: 1.25rem !important;
        margin-bottom: 4px !important;
    }
    .performance-metric small {
        font-size: 0.7rem;
    }
}

@media (max-width: 575px) {
    .performance-metric {
        padding: 12px 10px !important;
    }
    .performance-metric i {
        font-size: 28px !important;
    }
    .performance-metric h3 {
        font-size: 1.1rem !important;
    }
    .performance-metric small {
        font-size: 0.65rem;
    }
}

/* Card Headers - Mobile */
@media (max-width: 767px) {
    .card-title {
        font-size: 1rem !important;
    }
    .card-title i {
        font-size: 1rem !important;
    }
    .card-title small {
        display: block !important;
        margin-top: 4px;
        margin-left: 0 !important;
        font-size: 0.7rem !important;
    }
    .badge {
        font-size: 0.7rem !important;
        padding: 4px 10px !important;
    }
}

/* Charts - Mobile Responsive */
@media (max-width: 991px) {
    canvas {
        max-height: 250px !important;
    }
}

@media (max-width: 767px) {
    canvas {
        max-height: 200px !important;
    }
}

/* Month Comparison Cards - Mobile */
@media (max-width: 767px) {
    .progress {
        height: 8px !important;
    }
}

/* Top Performers - Mobile */
@media (max-width: 767px) {
    .performer-rank {
        width: 36px !important;
        height: 36px !important;
        font-size: 14px !important;
    }
    .flex-grow-1 h6 {
        font-size: 0.9rem;
    }
    .flex-grow-1 small {
        font-size: 0.7rem !important;
    }
    /* Scope to page content only - don't affect navbar */
    .content-wrapper .d-flex.gap-3 {
        gap: 0.5rem !important;
        flex-wrap: wrap;
    }
}

@media (max-width: 575px) {
    /* Scope to page content only - don't affect navbar */
    .content-wrapper .d-flex.gap-3 {
        flex-direction: column !important;
        gap: 0.25rem !important;
    }
}

/* Active Riders & Recent Activity - Mobile */
@media (max-width: 767px) {
    .card-body > div[style*="max-height"] {
        max-height: 350px !important;
    }
    .d-flex.align-items-start h6 {
        font-size: 0.9rem;
    }
    .d-flex.align-items-start small {
        font-size: 0.7rem !important;
    }
    .d-flex.align-items-start > div[style*="width: 50px"] {
        width: 42px !important;
        height: 42px !important;
        font-size: 18px !important;
    }
    .d-flex.align-items-start > div[style*="width: 40px"] {
        width: 36px !important;
        height: 36px !important;
        font-size: 16px !important;
    }
}

/* Empty States - Mobile */
@media (max-width: 767px) {
    .text-center i[style*="font-size: 64px"],
    .text-center i[style*="font-size: 56px"] {
        font-size: 48px !important;
    }
    .text-center.py-5 {
        padding-top: 2rem !important;
        padding-bottom: 2rem !important;
    }
}

/* Touch-friendly buttons - Scope to page content only, not navbar */
.content-wrapper .btn,
.content-wrapper .badge {
    min-height: 32px;
}

/* Card Body Padding - Mobile */
@media (max-width: 767px) {
    .card-body {
        padding: 16px !important;
    }
}

@media (max-width: 575px) {
    .card-body {
        padding: 14px !important;
    }
}

/* Disable hover effects on touch devices */
@media (hover: none) {
    .stat-card:hover,
    .performance-metric:hover,
    .card:hover {
        transform: none !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.pulse {
    animation: pulse 2s ease-in-out infinite;
}
</style>

@push('scripts')
<script>
// Define Digibox color palette
const colors = {
    digiboxBlue: '#2563EB',
    digiboxBlueDark: '#1e40af',
    successGreen: '#10b981',
    warningOrange: '#f59e0b',
    dangerRed: '#ef4444',
    infoCyan: '#06b6d4',
    purple: '#8b5cf6'
};

// Weekly Trend Chart
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: @json($weekDays),
        datasets: [
            {
                label: 'Duty Sessions',
                data: @json($weekSessions),
                borderColor: colors.digiboxBlue,
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: colors.digiboxBlue,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: colors.digiboxBlue,
                pointHoverBorderColor: '#fff'
            },
            {
                label: 'Installation Visits',
                data: @json($weekVisits),
                borderColor: colors.infoCyan,
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: colors.infoCyan,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: colors.infoCyan,
                pointHoverBorderColor: '#fff'
            },
            {
                label: 'Location Points',
                data: @json($weekLocations),
                borderColor: colors.successGreen,
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: colors.successGreen,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: colors.successGreen,
                pointHoverBorderColor: '#fff'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                position: window.innerWidth < 768 ? 'bottom' : 'top',
                labels: {
                    usePointStyle: true,
                    padding: window.innerWidth < 768 ? 10 : 20,
                    font: {
                        size: window.innerWidth < 768 ? 11 : 13,
                        weight: 600
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                cornerRadius: 8,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: {
                        size: window.innerWidth < 768 ? 10 : 12
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                }
            },
            x: {
                ticks: {
                    font: {
                        size: window.innerWidth < 768 ? 9 : 12
                    }
                },
                grid: {
                    display: false
                }
            }
        }
    }
});

// Hourly Activity Chart
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
const hourlyData = @json($hourlyActivity);
const maxValue = Math.max(...hourlyData);

new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: ['12am', '1am', '2am', '3am', '4am', '5am', '6am', '7am', '8am', '9am', '10am', '11am',
                 '12pm', '1pm', '2pm', '3pm', '4pm', '5pm', '6pm', '7pm', '8pm', '9pm', '10pm', '11pm'],
        datasets: [{
            label: 'Location Points',
            data: hourlyData,
            backgroundColor: hourlyData.map(value => {
                const intensity = maxValue > 0 ? value / maxValue : 0;
                return `rgba(139, 92, 246, ${0.3 + (intensity * 0.5)})`;
            }),
            borderColor: colors.purple,
            borderWidth: 2,
            borderRadius: 6,
            hoverBackgroundColor: colors.purple,
            hoverBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                cornerRadius: 8,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        return 'Activity: ' + context.parsed.y + ' points';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: {
                        size: window.innerWidth < 768 ? 10 : 12
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                }
            },
            x: {
                ticks: {
                    font: {
                        size: window.innerWidth < 768 ? 8 : 11
                    },
                    maxRotation: window.innerWidth < 768 ? 45 : 0,
                    minRotation: window.innerWidth < 768 ? 45 : 0
                },
                grid: {
                    display: false
                }
            }
        }
    }
});

// =====================================================
// LIVE RIDERS MAP
// =====================================================
const liveMap = L.map('liveMap').setView([23.8103, 90.4125], 12); // Default: Dhaka, Bangladesh

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
}).addTo(liveMap);

// Rider data from backend
const ridersData = @json($allRidersWithLocation);

// Track markers for real-time updates
const riderMarkers = {};

// Add riders to map
ridersData.forEach(rider => {
    if (!rider.latest_location) return; // Skip if no location

    const lat = rider.latest_location.latitude;
    const lng = rider.latest_location.longitude;
    const isOnline = rider.is_currently_online;
    const hasActiveSession = rider.active_session !== null;

    // Determine marker color
    let markerColor, markerIcon, statusText, statusClass;
    if (isOnline && hasActiveSession) {
        markerColor = '#2563EB'; // Blue for online with active session
        markerIcon = 'bi-briefcase-fill';
        statusText = 'Online - On Duty';
        statusClass = 'primary';
    } else if (isOnline) {
        markerColor = '#10b981'; // Green for online
        markerIcon = 'bi-broadcast-pin';
        statusText = 'Online';
        statusClass = 'success';
    } else {
        markerColor = '#6b7280'; // Grey for offline
        markerIcon = 'bi-wifi-off';
        statusText = 'Offline';
        statusClass = 'secondary';
    }

    // Create custom icon
    const iconHtml = `
        <div style="position: relative;">
            <div style="
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: ${markerColor};
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                border: 3px solid white;
            ">
                <i class="bi ${markerIcon}" style="color: white; font-size: 18px;"></i>
            </div>
            ${isOnline ? `<div style="
                position: absolute;
                top: -2px;
                right: -2px;
                width: 12px;
                height: 12px;
                background: #10b981;
                border: 2px solid white;
                border-radius: 50%;
                animation: pulse 2s infinite;
            "></div>` : ''}
        </div>
    `;

    const customIcon = L.divIcon({
        html: iconHtml,
        className: 'custom-rider-marker',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });

    // Create marker
    const marker = L.marker([lat, lng], { icon: customIcon }).addTo(liveMap);

    // Popup content
    const lastSeen = rider.latest_location ? new Date(rider.latest_location.recorded_at).toLocaleString() : 'N/A';
    const accuracy = rider.latest_location ? rider.latest_location.accuracy.toFixed(1) : 'N/A';
    const speed = rider.latest_location && rider.latest_location.speed ? (rider.latest_location.speed * 3.6).toFixed(1) : '0.0';

    const popupContent = `
        <div style="min-width: 200px;">
            <h6 class="fw-bold mb-2">${rider.name}</h6>
            <div class="mb-2">
                <span class="badge bg-${statusClass}">${statusText}</span>
            </div>
            <small class="d-block mb-1">
                <i class="bi bi-clock text-muted"></i> Last seen: <strong>${lastSeen}</strong>
            </small>
            <small class="d-block mb-1">
                <i class="bi bi-bullseye text-info"></i> Accuracy: <strong>${accuracy}m</strong>
            </small>
            <small class="d-block mb-1">
                <i class="bi bi-speedometer text-warning"></i> Speed: <strong>${speed} km/h</strong>
            </small>
            ${hasActiveSession ? `
                <small class="d-block mb-1">
                    <i class="bi bi-briefcase text-primary"></i> Session: <strong>${rider.session_duration}</strong>
                </small>
            ` : ''}
            <hr class="my-2">
            <small class="text-muted">
                <i class="bi bi-geo-alt"></i> ${lat.toFixed(6)}, ${lng.toFixed(6)}
            </small>
        </div>
    `;

    marker.bindPopup(popupContent);
    riderMarkers[rider.id] = marker;
});

// Fit map to show all riders
if (Object.keys(riderMarkers).length > 0) {
    const group = L.featureGroup(Object.values(riderMarkers));
    liveMap.fitBounds(group.getBounds().pad(0.1));
}

// Auto-refresh every 30 seconds with fade effect
setTimeout(function() {
    document.body.style.opacity = '0.7';
    setTimeout(function() {
        location.reload();
    }, 300);
}, 30000);
</script>
@endpush
@endsection
