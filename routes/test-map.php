<?php
// Simple test to verify data
Route::get('/test-map-data', function() {
    $today = \Carbon\Carbon::today()->format('Y-m-d');
    
    $locations = \App\Models\LocationPoint::whereDate('recorded_at', $today)
        ->orderBy('recorded_at', 'asc')
        ->take(10)
        ->get(['latitude', 'longitude', 'speed', 'recorded_at']);
    
    return response()->json([
        'success' => true,
        'count' => $locations->count(),
        'data' => $locations->map(function($loc) {
            return [
                'lat' => (float) $loc->latitude,
                'lng' => (float) $loc->longitude,
                'speed' => (float) $loc->speed,
                'time' => $loc->recorded_at->format('H:i:s')
            ];
        })
    ]);
});
