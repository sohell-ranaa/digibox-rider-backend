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

    // Check if rider is online (has sent location data in last 10 minutes)
    public function getIsOnlineAttribute()
    {
        $latestBatch = \DB::table('location_batches')
            ->where('rider_id', $this->id)
            ->orderBy('batch_end_time', 'desc')
            ->first();

        if (!$latestBatch) {
            return false;
        }

        return \Carbon\Carbon::parse($latestBatch->batch_end_time)->diffInMinutes(now()) < 10;
    }

    // Check if rider has an active duty session
    public function getIsOnDutyAttribute()
    {
        $activeSession = $this->dutySessions()
            ->where('status', 'active')
            ->first();

        if (!$activeSession) {
            return false;
        }

        // Double-check that session isn't stale
        $lastLocation = $this->locationPoints()
            ->where('duty_session_id', $activeSession->id)
            ->latest('recorded_at')
            ->first();

        // If no location data for 10+ minutes, session should be considered inactive
        if ($lastLocation && $lastLocation->recorded_at->diffInMinutes(now()) >= 10) {
            return false;
        }

        // If session started more than 15 minutes ago with no location data at all, inactive
        if (!$lastLocation && $activeSession->started_at->diffInMinutes(now()) >= 15) {
            return false;
        }

        return true;
    }
}
