<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // Double-check that session isn't stale - get latest location from batches
        $lastBatch = DB::table('location_batches')
            ->where('duty_session_id', $activeSession->id)
            ->orderBy('batch_end_time', 'desc')
            ->first();

        if ($lastBatch) {
            $points = json_decode($lastBatch->points, true);
            $lastPoint = end($points);
            if ($lastPoint) {
                $lastLocationTime = Carbon::parse(substr($lastBatch->batch_end_time, 0, 10) . ' ' . $lastPoint['ts']);
                // If no location data for 10+ minutes, session should be considered inactive
                if ($lastLocationTime->diffInMinutes(now()) >= 10) {
                    return false;
                }
            }
        } else {
            // If session started more than 15 minutes ago with no location data at all, inactive
            if ($activeSession->started_at->diffInMinutes(now()) >= 15) {
                return false;
            }
        }

        return true;
    }
}
