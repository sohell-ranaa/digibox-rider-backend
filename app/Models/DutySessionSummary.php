<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutySessionSummary extends Model
{
    protected $fillable = [
        'duty_session_id',
        'rider_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'total_distance_km',
        'location_points_count',
        'installation_visits_count',
        'stops_count',
        'route_polyline',
        'key_locations',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'route_polyline' => 'array',
        'key_locations' => 'array',
        'total_distance_km' => 'float',
    ];

    public function dutySession()
    {
        return $this->belongsTo(DutySession::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }
}
