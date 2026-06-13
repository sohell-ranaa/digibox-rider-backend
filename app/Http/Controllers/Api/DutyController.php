<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DutySession;
use App\Services\Cache\DashboardCacheService;
use App\Services\Cache\RiderCacheService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DutyController extends Controller
{
    protected $dashboardCache;
    protected $riderCache;

    public function __construct(DashboardCacheService $dashboardCache, RiderCacheService $riderCache)
    {
        $this->dashboardCache = $dashboardCache;
        $this->riderCache = $riderCache;
    }
    public function start(Request $request)
    {
        $rider = $request->user();

        // Check if there's already an active session
        $activeSession = DutySession::where('rider_id', $rider->id)
            ->where('status', 'active')
            ->first();

        if ($activeSession) {
            // Check last batch timestamp for this session
            $lastBatch = DB::table('location_batches')
                ->where('rider_id', $rider->id)
                ->where('duty_session_id', $activeSession->id)
                ->orderBy('batch_end_time', 'desc')
                ->first();

            // AUTO-CLOSE ONLY IF NO DATA FOR 60+ MINUTES (internet outage)
            $shouldAutoClose = false;
            $reason = '';

            if (!$lastBatch) {
                // No location data yet - only auto-close if session is older than 60 minutes with no data
                if ($activeSession->started_at->diffInMinutes(now()) >= 60) {
                    $shouldAutoClose = true;
                    $reason = 'Internet outage - No location data for 60+ minutes';
                    Log::warning("⚠️ Auto-closing session {$activeSession->id} for rider {$rider->id}: No location data for 60+ minutes (internet outage)");
                }
            } else {
                $lastBatchTime = Carbon::parse($lastBatch->batch_end_time);
                $minutesSinceLastBatch = $lastBatchTime->diffInMinutes(now());
                if ($minutesSinceLastBatch >= 60) {
                    $shouldAutoClose = true;
                    $reason = "Internet outage - No location data for {$minutesSinceLastBatch} minutes";
                    Log::warning("⚠️ Auto-closing session {$activeSession->id} for rider {$rider->id}: Last batch was {$minutesSinceLastBatch} minutes ago (internet outage)");
                }
            }

            if ($shouldAutoClose) {
                // Auto-close due to internet outage (60+ minutes)
                $endedAt = $lastBatch ? Carbon::parse($lastBatch->batch_end_time)->addMinutes(60) : $activeSession->started_at->addMinutes(60);

                // VALIDATION: Prevent ended_at from being before started_at
                if ($endedAt->lt($activeSession->started_at)) {
                    Log::error("🚨 BUG DETECTED: Auto-close tried to set ended_at ({$endedAt}) before started_at ({$activeSession->started_at}) for session {$activeSession->id}");
                    $endedAt = $activeSession->started_at->copy()->addMinutes(60);
                }

                $activeSession->ended_at = $endedAt;
                $activeSession->total_duration_minutes = $activeSession->started_at->diffInMinutes($activeSession->ended_at);
                $activeSession->total_distance_km = $this->calculateSessionDistance($activeSession->id);
                $activeSession->status = 'completed';
                // Note: 'notes' column doesn't exist in duty_sessions table, logging reason instead
                $activeSession->save();

                Log::info("✅ Session {$activeSession->id} auto-closed. Started: {$activeSession->started_at}, Ended: {$endedAt}, Reason: {$reason}, Distance: {$activeSession->total_distance_km} km");

                // Clear dashboard cache after auto-closing session
                $this->dashboardCache->clearTodayPerformance();
            } else {
                // Session is still active - RESUME IT instead of showing error
                Log::info("Rider {$rider->id} attempted to start duty with existing active session {$activeSession->id}, resuming...");

                // Clear cache to refresh online status
                $this->riderCache->markRiderOnline($rider->id);

                // Return the active session with 200 OK (not 400 error)
                return response()->json([
                    'message' => 'Duty session resumed',
                    'duty_session' => $activeSession,
                ], 200);
            }
        }

        // Create new duty session
        $session = DutySession::create([
            'rider_id' => $rider->id,
            'started_at' => now(),
            'status' => 'active',
        ]);

        Log::info("New duty session {$session->id} started for rider {$rider->id}");

        // Clear dashboard cache after starting new session
        $this->dashboardCache->clearTodayPerformance();
        $this->riderCache->markRiderOnline($rider->id);

        return response()->json([
            'message' => 'Duty session started successfully',
            'duty_session' => $session,
        ], 201);
    }

    public function stop(Request $request)
    {
        $rider = $request->user();

        $session = DutySession::where('rider_id', $rider->id)
            ->where('status', 'active')
            ->first();

        if (! $session) {
            return response()->json([
                'message' => 'No active duty session found',
            ], 404);
        }

        // Calculate total distance from location points
        $totalDistance = $this->calculateSessionDistance($session->id);

        // Update session
        $endedAt = now();

        // VALIDATION: Prevent ended_at from being before started_at
        if ($endedAt->lt($session->started_at)) {
            Log::error("🚨 BUG DETECTED: Manual stop tried to set ended_at ({$endedAt}) before started_at ({$session->started_at}) for session {$session->id}");
            return response()->json([
                'message' => 'Error: End time cannot be before start time. Please contact support.',
                'error' => 'INVALID_TIME_RANGE',
            ], 500);
        }

        $session->ended_at = $endedAt;
        $session->total_duration_minutes = $session->started_at->diffInMinutes($session->ended_at);
        $session->total_distance_km = $totalDistance;
        $session->status = 'completed';
        $session->save();

        Log::info("Duty session {$session->id} stopped. Started: {$session->started_at}, Ended: {$endedAt}, Duration: {$session->total_duration_minutes} min, Distance: {$totalDistance} km");

        // Clear dashboard cache after stopping session
        $this->dashboardCache->clearTodayPerformance();

        return response()->json([
            'message' => 'Duty session ended successfully',
            'duty_session' => $session,
        ]);
    }

    public function current(Request $request)
    {
        $rider = $request->user();

        $session = DutySession::where('rider_id', $rider->id)
            ->where('status', 'active')
            ->first();

        if (! $session) {
            return response()->json([
                'duty_session' => null,
            ]);
        }

        // AUTO-CLOSE ONLY IF NO DATA FOR 60+ MINUTES (internet outage)
        $lastBatch = DB::table('location_batches')
            ->where('rider_id', $rider->id)
            ->where('duty_session_id', $session->id)
            ->orderBy('batch_end_time', 'desc')
            ->first();

        $shouldAutoClose = false;
        $reason = '';

        if (!$lastBatch) {
            // No location data yet - only auto-close if session is older than 60 minutes with no data
            if ($session->started_at->diffInMinutes(now()) >= 60) {
                $shouldAutoClose = true;
                $reason = 'Internet outage - No location data for 60+ minutes';
                Log::warning("⚠️ Auto-closing session {$session->id} in current() check: No location data for 60+ minutes (internet outage)");
            }
        } else {
            $lastBatchTime = Carbon::parse($lastBatch->batch_end_time);
            $minutesSinceLastBatch = $lastBatchTime->diffInMinutes(now());
            if ($minutesSinceLastBatch >= 60) {
                $shouldAutoClose = true;
                $reason = "Internet outage - No location data for {$minutesSinceLastBatch} minutes";
                Log::warning("⚠️ Auto-closing session {$session->id} in current() check: Last batch was {$minutesSinceLastBatch} minutes ago (internet outage)");
            }
        }

        if ($shouldAutoClose) {
            // Auto-close due to internet outage (60+ minutes)
            $endedAt = $lastBatch ? Carbon::parse($lastBatch->batch_end_time)->addMinutes(60) : $session->started_at->addMinutes(60);

            // VALIDATION: Prevent ended_at from being before started_at
            if ($endedAt->lt($session->started_at)) {
                Log::error("🚨 BUG DETECTED: Auto-close tried to set ended_at ({$endedAt}) before started_at ({$session->started_at}) for session {$session->id}");
                $endedAt = $session->started_at->copy()->addMinutes(60);
            }

            $session->ended_at = $endedAt;
            $session->total_duration_minutes = $session->started_at->diffInMinutes($session->ended_at);
            $session->total_distance_km = $this->calculateSessionDistance($session->id);
            $session->status = 'completed';
            // Note: 'notes' column doesn't exist in duty_sessions table, logging reason instead
            $session->save();

            Log::info("✅ Session {$session->id} auto-closed in current() check. Started: {$session->started_at}, Ended: {$endedAt}, Reason: {$reason}, Distance: {$session->total_distance_km} km");

            // Clear dashboard cache after auto-closing session
            $this->dashboardCache->clearTodayPerformance();

            return response()->json([
                'duty_session' => null,
            ]);
        }

        return response()->json([
            'duty_session' => $session,
        ]);
    }

    public function history(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $rider = $request->user();

        $query = DutySession::where('rider_id', $rider->id)
            ->where('status', 'completed')
            ->orderBy('started_at', 'desc');

        if ($request->from) {
            $query->where('started_at', '>=', $request->from);
        }

        if ($request->to) {
            $query->where('started_at', '<=', $request->to);
        }

        $sessions = $query->get();

        return response()->json([
            'sessions' => $sessions,
        ]);
    }

    /**
     * Calculate total distance traveled during a duty session
     * Sums distances from location batches
     *
     * @param int $sessionId
     * @return float Distance in kilometers
     */
    private function calculateSessionDistance($sessionId)
    {
        // Sum total distance from all batches for this session
        $totalDistanceMeters = DB::table('location_batches')
            ->where('duty_session_id', $sessionId)
            ->sum('total_distance_meters');

        // Convert meters to kilometers and round
        return round($totalDistanceMeters / 1000, 2);
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        // Haversine formula
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance;
    }
}
