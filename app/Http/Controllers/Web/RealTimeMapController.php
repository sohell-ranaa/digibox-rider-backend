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

        // Enrich with rider data
        $riderIds = array_column($locations, 'rider_id');
        $riders = Rider::whereIn('id', $riderIds)->get()->keyBy('id');

        $enrichedLocations = array_map(function($location) use ($riders) {
            $rider = $riders[$location['rider_id']] ?? null;

            return [
                'rider_id' => $location['rider_id'],
                'rider_name' => $rider->name ?? 'Unknown',
                'rider_phone' => $rider->phone ?? null,
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'accuracy' => $location['accuracy'] ?? 0,
                'speed' => $location['speed'] ?? 0,
                'bearing' => $location['bearing'] ?? 0,
                'recorded_at' => $location['recorded_at'],
                'updated_at' => $location['updated_at'],
            ];
        }, $locations);

        return response()->json([
            'success' => true,
            'count' => count($enrichedLocations),
            'riders' => $enrichedLocations,
            'timestamp' => now()->toDateTimeString(),
        ]);
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
