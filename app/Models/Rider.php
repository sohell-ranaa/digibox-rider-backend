<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Rider extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'username',
        'password',
        'name',
        'phone',
        'email',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function dutySessions()
    {
        return $this->hasMany(DutySession::class);
    }

    public function locationPoints()
    {
        return $this->hasMany(LocationPoint::class);
    }

    public function stopRecords()
    {
        return $this->hasMany(StopRecord::class);
    }

    public function systemEvents()
    {
        return $this->hasMany(SystemEvent::class);
    }

    public function installationVisits()
    {
        return $this->hasMany(InstallationVisit::class);
    }

    public function currentDutySession()
    {
        return $this->hasOne(DutySession::class)->where('status', 'active')->latest();
    }

    public function latestLocation()
    {
        return $this->hasOne(LocationPoint::class)->latest('recorded_at');
    }
}
