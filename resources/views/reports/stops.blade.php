@extends('layouts.app')

@section('title', 'Stops Report')
@section('page-title', 'Stops Report')

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Rider</label>
                        <select name="rider_id" class="form-select">
                            <option value="">All Riders</option>
                            @foreach($riders as $rider)
                                <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>
                                    {{ $rider->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rider</th>
                                <th>Location</th>
                                <th>Started</th>
                                <th>Ended</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stops as $stop)
                            <tr>
                                <td>{{ $stop->rider->name }}</td>
                                <td>{{ number_format($stop->latitude, 6) }}, {{ number_format($stop->longitude, 6) }}</td>
                                <td>{{ $stop->started_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $stop->ended_at ? $stop->ended_at->format('M d, Y h:i A') : 'Ongoing' }}</td>
                                <td><span class="badge bg-warning">{{ $stop->duration_minutes }} min</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No stops found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $stops->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
