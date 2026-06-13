<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Cache\RealTimeLocationService;
use App\Models\Rider;
use Illuminate\Http\Request;

class RealTimeMapController extends Controller
{
    protected $realTimeLocation;

    public function __construct(RealTimeLocationService $realTimeLocation)
    {
        $this->realTimeLocation = $realTimeLocation;
    }

    /**
     * Get all online riders with real-time locations (for AJAX polling)
     */
    public function getOnlineRiders(Request $request)
    {
        // Get real-time locations from Redis
        $locations = $this->realTimeLocation->getAllOnlineRiders();

        // Enrich with rider data and active duty sessions
        $riderIds = array_column($locations, 'rider_id');
        $riders = Rider::whereIn('id', $riderIds)->get()->keyBy('id');

        $enrichedLocations = array_map(function($location) use ($riders) {
            $rider = $riders[$location['rider_id']] ?? null;

            if (!$rider) {
                return null; // Skip if rider not found
            }

            // Get active duty session
            $activeSession = \App\Models\DutySession::where('rider_id', $rider->id)
                ->where('status', 'active')
                ->first();

            // Parse recorded_at time
            $recordedAt = \Carbon\Carbon::parse($location['recorded_at']);
            $isOnline = $recordedAt->diffInMinutes(now()) < 10; // Online if within 10 min

            return [
                'id' => $rider->id,
                'name' => $rider->name,
                'username' => $rider->username,
                'latitude' => (float) $location['latitude'],
                'longitude' => (float) $location['longitude'],
                'accuracy' => $location['accuracy'] ?? 0,
                'speed' => $location['speed'] ?? 0,
                'recorded_at' => $recordedAt->diffForHumans(),
                'recorded_at_full' => $recordedAt->format('Y-m-d H:i:s'),
                'is_online' => $isOnline,
                'is_on_duty' => $activeSession !== null,
                'duty_started_at' => $activeSession ? $activeSession->started_at->format('h:i A') : null,
                'duty_duration' => $activeSession ? $activeSession->started_at->diffForHumans(null, true) : null,
                'status' => $isOnline ? 'Online' : 'Offline',
            ];
        }, $locations);

        // Filter out nulls
        $enrichedLocations = array_filter($enrichedLocations);

        return response()->json(array_values($enrichedLocations));
    }

    /**
     * Get single rider's location and trail
     */
    public function getRiderLocation(Request $request, $riderId)
    {
        $location = $this->realTimeLocation->getRiderLocation($riderId);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Rider not found or offline',
            ], 404);
        }

        $trail = $this->realTimeLocation->getRiderTrail($riderId);
        $rider = Rider::find($riderId);

        return response()->json([
            'success' => true,
            'rider' => [
                'id' => $riderId,
                'name' => $rider->name ?? 'Unknown',
                'phone' => $rider->phone ?? null,
            ],
            'location' => $location,
            'trail' => $trail,
        ]);
    }

    /**
     * Get online riders count
     */
    public function getOnlineCount(Request $request)
    {
        $count = $this->realTimeLocation->getOnlineRidersCount();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
