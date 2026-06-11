<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationPoint extends Model
{
    protected $fillable = [
        'rider_id',
        'duty_session_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'bearing',
        'altitude',
        'recorded_at',
        'uploaded_at',
        'is_synced',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'speed' => 'decimal:2',
        'bearing' => 'decimal:2',
        'altitude' => 'decimal:2',
        'recorded_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'is_synced' => 'boolean',
    ];

    // Relationships
    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function dutySession()
    {
        return $this->belongsTo(DutySession::class);
    }
}
