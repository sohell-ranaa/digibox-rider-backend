<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\DutySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RealTimeMapController extends Controller
{
    /**
     * Get all online riders with latest locations (SIMPLIFIED - uses database batches)
     */
    public function getOnlineRiders(Request $request)
    {
        \Log::info('📍 [Live Map] Fetching online riders from database batches');

        // Get latest batch for each rider in last 30 minutes (generous window for timezone issues)
        $tenMinutesAgo = Carbon::now()->subMinutes(30);

        $latestBatches = DB::table('location_batches as lb1')
            ->select('lb1.*')
            ->join(DB::raw('(SELECT rider_id, MAX(batch_end_time) as max_time
                             FROM location_batches
                             WHERE batch_end_time >= "' . $tenMinutesAgo->format('Y-m-d H:i:s') . '"
                             GROUP BY rider_id) as lb2'), function($join) {
                $join->on('lb1.rider_id', '=', 'lb2.rider_id')
                     ->on('lb1.batch_end_time', '=', 'lb2.max_time');
            })
            ->get();

        \Log::info("📍 [Live Map] Found {$latestBatches->count()} riders with recent batches");

        $enrichedLocations = [];

        foreach ($latestBatches as $batch) {
            // Get rider info
            $rider = Rider::find($batch->rider_id);
            if (!$rider) {
                continue;
            }

            // Get active duty session
            $activeSession = DutySession::where('rider_id', $rider->id)
                ->where('status', 'active')
                ->first();

            // Extract latest point from batch
            $points = json_decode($batch->points, true);
            if (empty($points)) {
                continue;
            }

            // Get the last point (most recent)
            $latestPoint = end($points);

            // Construct full timestamp
            $batchDate = substr($batch->batch_end_time, 0, 10);
            $recordedAt = Carbon::parse($batchDate . ' ' . $latestPoint['ts']);

            // Check if online:
            // 1. GPS within last 30 minutes (has recent location data)
            // 2. Has active duty session (not stopped)
            $hasRecentGPS = $recordedAt->diffInMinutes(now()) < 30;
            $isOnDuty = $activeSession !== null;

            // Rider is only "Online" if BOTH conditions are true
            $isOnline = $hasRecentGPS && $isOnDuty;

            $enrichedLocations[] = [
                'id' => $rider->id,
                'name' => $rider->name,
                'username' => $rider->username,
                'latitude' => (float) $latestPoint['lat'],
                'longitude' => (float) $latestPoint['lng'],
                'accuracy' => (float) ($latestPoint['acc'] ?? 0),
                'speed' => (float) ($latestPoint['spd'] ?? 0),
                'recorded_at' => $recordedAt->diffForHumans(),
                'recorded_at_full' => $recordedAt->format('Y-m-d H:i:s'),
                'is_online' => $isOnline,
                'is_on_duty' => $isOnDuty,
                'duty_started_at' => $activeSession ? $activeSession->started_at->format('h:i A') : null,
                'duty_duration' => $activeSession ? $activeSession->started_at->diffForHumans(null, true) : null,
                'status' => $isOnline ? 'Online' : 'Offline',
            ];
        }

        \Log::info("📍 [Live Map] Returning " . count($enrichedLocations) . " riders");

        return response()->json($enrichedLocations);
    }

    /**
     * Get single rider's location and trail (SIMPLIFIED - uses database batches)
     */
    public function getRiderLocation(Request $request, $riderId)
    {
        $rider = Rider::find($riderId);

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'Rider not found',
            ], 404);
        }

        // Get latest batch
        $latestBatch = DB::table('location_batches')
            ->where('rider_id', $riderId)
            ->orderBy('batch_end_time', 'desc')
            ->first();

        if (!$latestBatch) {
            return response()->json([
                'success' => false,
                'message' => 'No location data found',
            ], 404);
        }

        // Extract latest point
        $points = json_decode($latestBatch->points, true);
        $latestPoint = end($points);
        $batchDate = substr($latestBatch->batch_end_time, 0, 10);
        $recordedAt = Carbon::parse($batchDate . ' ' . $latestPoint['ts']);

        // Get trail (last 100 points from recent batches)
        $trail = $this->getRiderTrail($riderId);

        return response()->json([
            'success' => true,
            'rider' => [
                'id' => $riderId,
                'name' => $rider->name,
                'phone' => $rider->phone ?? null,
            ],
            'location' => [
                'latitude' => (float) $latestPoint['lat'],
                'longitude' => (float) $latestPoint['lng'],
                'accuracy' => (float) ($latestPoint['acc'] ?? 0),
                'speed' => (float) ($latestPoint['spd'] ?? 0),
                'recorded_at' => $recordedAt->format('Y-m-d H:i:s'),
            ],
            'trail' => $trail,
        ]);
    }

    /**
     * Get rider trail (last 100 points)
     */
    private function getRiderTrail($riderId)
    {
        $batches = DB::table('location_batches')
            ->where('rider_id', $riderId)
            ->orderBy('batch_end_time', 'desc')
            ->limit(20)
            ->get();

        $trail = [];
        foreach ($batches as $batch) {
            $points = json_decode($batch->points, true);
            $batchDate = substr($batch->batch_end_time, 0, 10);

            foreach ($points as $point) {
                $trail[] = [
                    'latitude' => (float) $point['lat'],
                    'longitude' => (float) $point['lng'],
                    'recorded_at' => $batchDate . ' ' . $point['ts'],
                ];

                if (count($trail) >= 100) {
                    break 2;
                }
            }
        }

        return $trail;
    }

    /**
     * Get online riders count (SIMPLIFIED - uses database batches)
     */
    public function getOnlineCount(Request $request)
    {
        $thirtyMinutesAgo = Carbon::now()->subMinutes(30);

        $count = DB::table('location_batches')
            ->where('batch_end_time', '>=', $thirtyMinutesAgo)
            ->distinct('rider_id')
            ->count('rider_id');

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
