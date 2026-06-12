<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutySession extends Model
{
    protected $fillable = [
        'rider_id',
        'started_at',
        'ended_at',
        'total_duration_minutes',
        'total_distance_km',
        'total_stops',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_distance_km' => 'decimal:2',
    ];

    // Relationships
    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function stopRecords()
    {
        return $this->hasMany(StopRecord::class);
    }

    public function installationVisits()
    {
        return $this->hasMany(InstallationVisit::class);
    }

    public function systemEvents()
    {
        return $this->hasMany(SystemEvent::class);
    }

    public function summary()
    {
        return $this->hasOne(DutySessionSummary::class);
    }
}
