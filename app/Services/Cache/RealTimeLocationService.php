<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class RealTimeLocationService
{
    const LOCATION_TTL = 300; // 5 minutes - locations expire after 5 min of no updates
    const TRAIL_TTL = 1800; // 30 minutes - keep trail for 30 minutes

    /**
     * Store real-time location in Redis for live map
     */
    public function storeRealTimeLocation(int $riderId, array $locationData): void
    {
        $key = "realtime:rider:location:$riderId";

        // Store current location
        Redis::setex($key, self::LOCATION_TTL, json_encode([
            'rider_id' => $riderId,
            'latitude' => $locationData['latitude'],
            'longitude' => $locationData['longitude'],
            'accuracy' => $locationData['accuracy'] ?? null,
            'speed' => $locationData['speed'] ?? null,
            'bearing' => $locationData['bearing'] ?? null,
            'recorded_at' => $locationData['recorded_at'] ?? now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]));

        // Add to online riders set (for quick lookup)
        Redis::sadd('realtime:riders:online', $riderId);
        Redis::expire('realtime:riders:online', self::LOCATION_TTL);

        // Store in trail (last 100 points for route visualization)
        $trailKey = "realtime:rider:trail:$riderId";
        Redis::lpush($trailKey, json_encode([
            'lat' => $locationData['latitude'],
            'lng' => $locationData['longitude'],
            'ts' => now()->toDateTimeString(),
            'acc' => $locationData['accuracy'] ?? null,
        ]));
        Redis::ltrim($trailKey, 0, 99); // Keep only last 100 points
        Redis::expire($trailKey, self::TRAIL_TTL);
    }

    /**
     * Get all online riders with their real-time locations
     */
    public function getAllOnlineRiders(): array
    {
        $onlineRiderIds = Redis::smembers('realtime:riders:online');
        $riders = [];

        foreach ($onlineRiderIds as $riderId) {
            $location = $this->getRiderLocation($riderId);
            if ($location) {
                $riders[] = $location;
            }
        }

        return $riders;
    }

    /**
     * Get single rider's current location
     */
    public function getRiderLocation(int $riderId): ?array
    {
        $key = "realtime:rider:location:$riderId";
        $data = Redis::get($key);

        if (!$data) {
            return null;
        }

        return json_decode($data, true);
    }

    /**
     * Get rider's location trail (last 100 points)
     */
    public function getRiderTrail(int $riderId): array
    {
        $trailKey = "realtime:rider:trail:$riderId";
        $trail = Redis::lrange($trailKey, 0, -1);

        return array_map(function($point) {
            return json_decode($point, true);
        }, $trail);
    }

    /**
     * Get count of online riders
     */
    public function getOnlineRidersCount(): int
    {
        return Redis::scard('realtime:riders:online') ?: 0;
    }

    /**
     * Remove rider from online list (when duty stops)
     */
    public function removeRider(int $riderId): void
    {
        Redis::del("realtime:rider:location:$riderId");
        Redis::srem('realtime:riders:online', $riderId);
        // Keep trail for historical view
    }

    /**
     * Clear all real-time data
     */
    public function clearAll(): void
    {
        $onlineRiderIds = Redis::smembers('realtime:riders:online');

        foreach ($onlineRiderIds as $riderId) {
            Redis::del("realtime:rider:location:$riderId");
            Redis::del("realtime:rider:trail:$riderId");
        }

        Redis::del('realtime:riders:online');
    }
}
