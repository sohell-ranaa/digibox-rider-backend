<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StopRecord extends Model
{
    protected $fillable = [
        'rider_id',
        'duty_session_id',
        'latitude',
        'longitude',
        'started_at',
        'ended_at',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
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
