@extends('layouts.app')

@section('title', 'Installation Visits Report')
@section('page-title', 'Installation Visits Report')

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
                        <label class="form-label">Installation</label>
                        <select name="installation_id" class="form-select">
                            <option value="">All Installations</option>
                            @foreach($installations as $installation)
                                <option value="{{ $installation->id }}" {{ request('installation_id') == $installation->id ? 'selected' : '' }}>
                                    {{ $installation->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">Filter</button>
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
                                <th>Installation</th>
                                <th>Arrived</th>
                                <th>Departed</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($visits as $visit)
                            <tr>
                                <td>{{ $visit->rider->name }}</td>
                                <td><strong>{{ $visit->installation->name }}</strong></td>
                                <td>{{ $visit->arrived_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $visit->departed_at ? $visit->departed_at->format('M d, Y h:i A') : 'Still there' }}</td>
                                <td>
                                    @if($visit->duration_minutes)
                                        <span class="badge bg-info">{{ $visit->duration_minutes }} min</span>
                                    @else
                                        <span class="badge bg-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($visit->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Completed</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No visits found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $visits->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
