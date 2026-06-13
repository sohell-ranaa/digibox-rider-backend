<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class RiderCacheService
{
    const RIDER_ONLINE_TTL = 60;          // 1 minute
    const RIDER_LOCATION_TTL = 30;        // 30 seconds
    const RIDER_STATS_TTL = 300;          // 5 minutes

    /**
     * Mark rider as online in Redis
     */
    public function markRiderOnline(int $riderId, array $locationData = []): void
    {
        $key = "rider:online:$riderId";

        Redis::setex($key, self::RIDER_ONLINE_TTL, json_encode([
            'rider_id' => $riderId,
            'last_seen' => now()->toDateTimeString(),
            'latitude' => $locationData['latitude'] ?? null,
            'longitude' => $locationData['longitude'] ?? null,
        ]));

        // Add to online riders set
        Redis::sadd('riders:online:set', $riderId);
        Redis::expire('riders:online:set', self::RIDER_ONLINE_TTL);
    }

    /**
     * Get all online rider IDs
     */
    public function getOnlineRiderIds(): array
    {
        $members = Redis::smembers('riders:online:set');

        // Filter out riders whose individual keys have expired
        $onlineIds = [];
        foreach ($members as $riderId) {
            if (Redis::exists("rider:online:$riderId")) {
                $onlineIds[] = (int) $riderId;
            }
        }

        return $onlineIds;
    }

    /**
     * Check if rider is online
     */
    public function isRiderOnline(int $riderId): bool
    {
        return Redis::exists("rider:online:$riderId");
    }

    /**
     * Store rider's latest location in cache
     */
    public function cacheRiderLocation(int $riderId, array $location): void
    {
        $key = "rider:location:$riderId";
        Cache::put($key, $location, self::RIDER_LOCATION_TTL);
    }

    /**
     * Get rider's latest location from cache
     */
    public function getRiderLocation(int $riderId): ?array
    {
        return Cache::get("rider:location:$riderId");
    }

    /**
     * Cache rider statistics
     */
    public function cacheRiderStats(int $riderId, array $stats): void
    {
        $key = "rider:stats:$riderId";
        Cache::put($key, $stats, self::RIDER_STATS_TTL);
    }

    /**
     * Get rider statistics from cache
     */
    public function getRiderStats(int $riderId): ?array
    {
        return Cache::get("rider:stats:$riderId");
    }

    /**
     * Clear rider cache
     */
    public function clearRiderCache(int $riderId): void
    {
        Redis::del("rider:online:$riderId");
        Redis::srem('riders:online:set', $riderId);
        Cache::forget("rider:location:$riderId");
        Cache::forget("rider:stats:$riderId");
    }

    /**
     * Clear all rider caches
     */
    public function clearAllRiderCaches(): void
    {
        Redis::del('riders:online:set');
        Cache::flush();
    }
}
