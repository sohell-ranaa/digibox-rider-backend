<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationLocation extends Model
{
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'geofence_radius_meters',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function installationVisits()
    {
        return $this->hasMany(InstallationVisit::class);
    }
}
