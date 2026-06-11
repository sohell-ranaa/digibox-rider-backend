<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemEvent extends Model
{
    protected $fillable = [
        'rider_id',
        'duty_session_id',
        'event_type',
        'occurred_at',
        'additional_data',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'additional_data' => 'array',
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
