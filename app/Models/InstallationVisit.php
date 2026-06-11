<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationVisit extends Model
{
    protected $fillable = [
        'rider_id',
        'duty_session_id',
        'installation_location_id',
        'arrived_at',
        'departed_at',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'departed_at' => 'datetime',
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

    public function installationLocation()
    {
        return $this->belongsTo(InstallationLocation::class);
    }

    /**
     * Alias for installationLocation() for backward compatibility
     * Some code uses ->installation instead of ->installationLocation
     */
    public function installation()
    {
        return $this->installationLocation();
    }
}
