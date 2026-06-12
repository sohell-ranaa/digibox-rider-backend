<?php

namespace App\Console\Commands;

use App\Models\DutySession;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseStaleSessions extends Command
{
    protected $signature = 'sessions:close-stale';
    protected $description = 'Close duty sessions that have no GPS data for more than 10 minutes';

    public function handle()
    {
        $threshold = Carbon::now()->subMinutes(10);

        // Get all active sessions
        $activeSessions = DutySession::where('status', 'active')->get();

        $closedCount = 0;

        foreach ($activeSessions as $session) {
            // Get the latest batch for this session
            $latestBatch = DB::table('location_batches')
                ->where('duty_session_id', $session->id)
                ->orderBy('batch_end_time', 'desc')
                ->first();

            // If no location data, or last batch is older than 10 minutes, close the session
            if (!$latestBatch || Carbon::parse($latestBatch->batch_end_time) < $threshold) {
                $session->status = 'completed';
                $session->ended_at = $latestBatch ? Carbon::parse($latestBatch->batch_end_time) : Carbon::now();

                // Calculate total duration
                if ($session->started_at && $session->ended_at) {
                    $session->total_duration_minutes = $session->started_at->diffInMinutes($session->ended_at);
                }

                $session->save();

                $this->info("Closed stale session #{$session->id} for rider #{$session->rider_id}");
                $closedCount++;
            }
        }

        if ($closedCount > 0) {
            $this->info("Total: Closed {$closedCount} stale session(s)");
        } else {
            $this->info("No stale sessions found");
        }

        return 0;
    }
}
