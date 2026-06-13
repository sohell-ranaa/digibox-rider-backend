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

            // If no location data OR last batch is older than 10 minutes, auto-close the session
            $shouldAutoClose = false;
            $reason = '';

            if (!$lastBatch) {
                $shouldAutoClose = true;
                $reason = 'No location data received';
                Log::info("Auto-closing stale session {$activeSession->id} for rider {$rider->id}: No location data");
            } else {
                $lastBatchTime = Carbon::parse($lastBatch->batch_end_time);
                $minutesSinceLastBatch = $lastBatchTime->diffInMinutes(now());
                if ($minutesSinceLastBatch >= 10) {
                    $shouldAutoClose = true;
                    $reason = "No location data for {$minutesSinceLastBatch} minutes";
                    Log::info("Auto-closing stale session {$activeSession->id} for rider {$rider->id}: Last batch was {$minutesSinceLastBatch} minutes ago");
                }
            }

            if ($shouldAutoClose) {
                // Auto-close the stale session
                $activeSession->ended_at = $lastBatch ? Carbon::parse($lastBatch->batch_end_time)->addMinutes(10) : $activeSession->started_at->addMinutes(10);
                $activeSession->total_duration_minutes = $activeSession->started_at->diffInMinutes($activeSession->ended_at);
                $activeSession->total_distance_km = $this->calculateSessionDistance($activeSession->id);
                $activeSession->status = 'completed';
                $activeSession->save();

                Log::info("Session {$activeSession->id} auto-closed successfully. Reason: {$reason}. Distance: {$activeSession->total_distance_km} km");

                // Clear dashboard cache after auto-closing session
                $this->dashboardCache->clearTodayPerformance();
            } else {
                // Session is still active and receiving data
                return response()->json([
                    'message' => 'You already have an active duty session',
                    'duty_session' => $activeSession,
                ], 400);
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
        $session->ended_at = now();
        $session->total_duration_minutes = $session->started_at->diffInMinutes($session->ended_at);
        $session->total_distance_km = $totalDistance;
        $session->status = 'completed';
        $session->save();

        Log::info("Duty session {$session->id} stopped. Duration: {$session->total_duration_minutes} min, Distance: {$totalDistance} km");

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

        // Check if session should be auto-closed due to inactivity
        $lastBatch = DB::table('location_batches')
            ->where('rider_id', $rider->id)
            ->where('duty_session_id', $session->id)
            ->orderBy('batch_end_time', 'desc')
            ->first();

        $shouldAutoClose = false;
        $reason = '';

        if (!$lastBatch) {
            // No location data yet - keep session active (they just started)
            // Only auto-close if session is older than 15 minutes with no data
            if ($session->started_at->diffInMinutes(now()) >= 15) {
                $shouldAutoClose = true;
                $reason = 'No location data received for 15 minutes';
            }
        } else {
            $lastBatchTime = Carbon::parse($lastBatch->batch_end_time);
            $minutesSinceLastBatch = $lastBatchTime->diffInMinutes(now());
            if ($minutesSinceLastBatch >= 10) {
                $shouldAutoClose = true;
                $reason = "No location data for {$minutesSinceLastBatch} minutes";
            }
        }

        if ($shouldAutoClose) {
            // Auto-close the stale session
            $session->ended_at = $lastBatch ? Carbon::parse($lastBatch->batch_end_time)->addMinutes(10) : $session->started_at->addMinutes(10);
            $session->total_duration_minutes = $session->started_at->diffInMinutes($session->ended_at);
            $session->total_distance_km = $this->calculateSessionDistance($session->id);
            $session->status = 'completed';
            $session->save();

            Log::info("Session {$session->id} auto-closed in current() check. Reason: {$reason}. Distance: {$session->total_distance_km} km");

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
