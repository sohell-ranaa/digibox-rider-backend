@extends('layouts.app')

@section('title', 'Travel History')
@section('page-title', 'Travel History')

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Rider</label>
                        <select name="rider_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Select Rider</option>
                            @foreach($riders as $rider)
                                <option value="{{ $rider->id }}" {{ $selectedRider == $rider->id ? 'selected' : '' }}>
                                    {{ $rider->name }} ({{ $rider->username }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" onchange="this.form.submit()">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($selectedRider && count($dutySessions) > 0)
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div id="map" style="height: 600px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Duty Sessions</h5>
                @foreach($dutySessions as $session)
                <div class="mb-3 pb-3 border-bottom">
                    <h6>Session #{{ $session->id }}</h6>
                    <p class="mb-1"><strong>Started:</strong> {{ $session->started_at->format('h:i A') }}</p>
                    <p class="mb-1"><strong>Ended:</strong> {{ $session->ended_at ? $session->ended_at->format('h:i A') : 'Active' }}</p>
                    <p class="mb-1"><strong>Duration:</strong> {{ $session->total_duration_minutes ?? 'N/A' }} minutes</p>
                    <button onclick="viewRoute({{ $session->id }})" class="btn btn-sm btn-primary">View Route</button>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@elseif($selectedRider)
<div class="alert alert-info">No duty sessions found for the selected rider on this date.</div>
@else
<div class="alert alert-info">Please select a rider to view travel history.</div>
@endif

@push('scripts')
<script>
const map = L.map('map').setView([23.8103, 90.4125], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

function viewRoute(dutySessionId) {
    fetch(`{{ route('history.route') }}?duty_session_id=${dutySessionId}`)
        .then(response => response.json())
        .then(locations => {
            // Clear existing layers
            map.eachLayer(layer => {
                if (layer instanceof L.Polyline || layer instanceof L.Marker) {
                    map.removeLayer(layer);
                }
            });

            if (locations.length === 0) {
                alert('No location data found for this session');
                return;
            }

            const points = locations.map(loc => [loc.latitude, loc.longitude]);

            // Draw route
            L.polyline(points, {color: 'blue', weight: 3}).addTo(map);

            // Add start marker
            L.marker(points[0]).addTo(map).bindPopup('Start');

            // Add end marker if ended
            if (points.length > 1) {
                L.marker(points[points.length - 1]).addTo(map).bindPopup('End');
            }

            map.fitBounds(L.latLngBounds(points));
        });
}
</script>
@endpush
@endsection
