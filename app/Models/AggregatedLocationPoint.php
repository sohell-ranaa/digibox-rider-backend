<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AggregatedLocationPoint extends Model
{
    protected $fillable = [
        'rider_id',
        'duty_session_id',
        'latitude',
        'longitude',
        'time_bucket',
        'point_count',
        'avg_speed',
        'avg_accuracy',
        'distance_km',
    ];

    protected $casts = [
        'time_bucket' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'avg_speed' => 'float',
        'avg_accuracy' => 'float',
        'distance_km' => 'float',
    ];

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function dutySession()
    {
        return $this->belongsTo(DutySession::class);
    }
}
