<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Rider;
use App\Models\DutySession;
use App\Models\StopRecord;
use App\Models\InstallationLocation;
use App\Models\InstallationVisit;

class DashboardCacheService
{
    // Cache TTLs (in seconds)
    const ONLINE_RIDERS_TTL = 30;        // 30 seconds - needs to be fresh
    const DASHBOARD_STATS_TTL = 300;     // 5 minutes - less critical
    const WEEKLY_DATA_TTL = 1800;        // 30 minutes - changes slowly
    const MONTHLY_DATA_TTL = 3600;       // 1 hour - changes very slowly

    /**
     * Get online riders count with caching
     */
    public function getOnlineRidersCount(): int
    {
        return Cache::remember('dashboard:online_riders_count', self::ONLINE_RIDERS_TTL, function () {
            $now = Carbon::now();
            $onlineRiderIds = DB::table('location_batches')
                ->where('batch_end_time', '>=', $now->copy()->subMinutes(10))
                ->distinct('rider_id')
                ->pluck('rider_id');

            return Rider::where('is_active', true)
                ->whereIn('id', $onlineRiderIds)
                ->count();
        });
    }

    /**
     * Get total active riders count with caching
     */
    public function getTotalActiveRiders(): int
    {
        return Cache::remember('dashboard:total_active_riders', self::DASHBOARD_STATS_TTL, function () {
            return Rider::where('is_active', true)->count();
        });
    }

    /**
     * Get total installations count with caching
     */
    public function getTotalInstallations(): int
    {
        return Cache::remember('dashboard:total_installations', self::DASHBOARD_STATS_TTL, function () {
            return InstallationLocation::where('is_active', true)->count();
        });
    }

    /**
     * Get today's location count with caching
     */
    public function getTodayLocations(): int
    {
        $cacheKey = 'dashboard:today_locations:' . Carbon::today()->format('Y-m-d');

        return Cache::remember($cacheKey, self::DASHBOARD_STATS_TTL, function () {
            return DB::table('location_batches')
                ->whereDate('batch_start_time', Carbon::today())
                ->sum('point_count');
        });
    }

    /**
     * Get today's performance metrics with caching
     */
    public function getTodayPerformance(): array
    {
        $cacheKey = 'dashboard:today_performance:' . Carbon::today()->format('Y-m-d');

        return Cache::remember($cacheKey, self::ONLINE_RIDERS_TTL, function () {
            $today = Carbon::today();
            $now = Carbon::now();

            // Sessions count
            $todaySessions = DutySession::whereDate('started_at', $today)->count();

            // Stops count
            $todayStops = StopRecord::whereDate('started_at', $today)->count();

            // Visits count
            $todayVisits = InstallationVisit::whereDate('arrived_at', $today)->count();

            // Calculate hours (completed + active)
            $completedMinutes = DutySession::whereDate('started_at', $today)
                ->where('status', 'completed')
                ->sum('total_duration_minutes');

            $activeSessions = DutySession::whereDate('started_at', $today)
                ->where('status', 'active')
                ->get();

            $activeMinutes = 0;
            foreach ($activeSessions as $session) {
                $activeMinutes += $session->started_at->diffInMinutes($now);
            }

            $todayDutyHours = round(($completedMinutes + $activeMinutes) / 60, 1);

            // Calculate distance (completed + active)
            $completedDistance = DutySession::whereDate('started_at', $today)
                ->where('status', 'completed')
                ->sum('total_distance_km');

            $activeDistance = 0;
            foreach ($activeSessions as $session) {
                $batches = DB::table('location_batches')
                    ->where('duty_session_id', $session->id)
                    ->orderBy('batch_start_time', 'asc')
                    ->get();

                $sessionDistance = 0;
                $previousPoint = null;

                foreach ($batches as $batch) {
                    $points = json_decode($batch->points, true);
                    foreach ($points as $point) {
                        if ($previousPoint) {
                            $sessionDistance += $this->haversineDistance(
                                $previousPoint['lat'],
                                $previousPoint['lng'],
                                $point['lat'],
                                $point['lng']
                            );
                        }
                        $previousPoint = $point;
                    }
                }

                $activeDistance += $sessionDistance / 1000;
            }

            $todayDistance = round($completedDistance + $activeDistance, 2);

            return [
                'sessions' => $todaySessions,
                'stops' => $todayStops,
                'visits' => $todayVisits,
                'hours' => $todayDutyHours,
                'distance' => $todayDistance,
            ];
        });
    }

    /**
     * Get weekly trend data with caching
     */
    public function getWeeklyTrend(): array
    {
        $cacheKey = 'dashboard:weekly_trend:' . Carbon::today()->format('Y-W');

        return Cache::remember($cacheKey, self::WEEKLY_DATA_TTL, function () {
            $weekDays = [];
            $weekSessions = [];
            $weekVisits = [];
            $weekLocations = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $weekDays[] = $date->format('D, M j');

                $weekSessions[] = DutySession::whereDate('started_at', $date)->count();
                $weekVisits[] = InstallationVisit::whereDate('arrived_at', $date)->count();
                $weekLocations[] = DB::table('location_batches')
                    ->whereDate('batch_start_time', $date)
                    ->sum('point_count') ?: 0;
            }

            return [
                'days' => $weekDays,
                'sessions' => $weekSessions,
                'visits' => $weekVisits,
                'locations' => $weekLocations,
            ];
        });
    }

    /**
     * Get monthly comparison data with caching
     */
    public function getMonthlyComparison(): array
    {
        $cacheKey = 'dashboard:monthly_comparison:' . Carbon::now()->format('Y-m');

        return Cache::remember($cacheKey, self::MONTHLY_DATA_TTL, function () {
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            $thisMonthSessions = DutySession::where('started_at', '>=', $thisMonth)->count();
            $lastMonthSessions = DutySession::whereBetween('started_at', [$lastMonth, $lastMonthEnd])->count();
            $sessionsChange = $lastMonthSessions > 0
                ? round((($thisMonthSessions - $lastMonthSessions) / $lastMonthSessions) * 100, 1)
                : 0;

            $thisMonthVisits = InstallationVisit::where('arrived_at', '>=', $thisMonth)->count();
            $lastMonthVisits = InstallationVisit::whereBetween('arrived_at', [$lastMonth, $lastMonthEnd])->count();
            $visitsChange = $lastMonthVisits > 0
                ? round((($thisMonthVisits - $lastMonthVisits) / $lastMonthVisits) * 100, 1)
                : 0;

            return [
                'thisMonthSessions' => $thisMonthSessions,
                'lastMonthSessions' => $lastMonthSessions,
                'sessionsChange' => $sessionsChange,
                'thisMonthVisits' => $thisMonthVisits,
                'lastMonthVisits' => $lastMonthVisits,
                'visitsChange' => $visitsChange,
            ];
        });
    }

    /**
     * Get hourly activity for today with caching
     */
    public function getHourlyActivity(): array
    {
        $cacheKey = 'dashboard:hourly_activity:' . Carbon::today()->format('Y-m-d');

        return Cache::remember($cacheKey, self::DASHBOARD_STATS_TTL, function () {
            $today = Carbon::today();
            $hourlyActivity = [];

            for ($hour = 0; $hour < 24; $hour++) {
                $count = DB::table('location_batches')
                    ->whereDate('batch_start_time', $today)
                    ->whereRaw('HOUR(batch_start_time) = ?', [$hour])
                    ->sum('point_count');
                $hourlyActivity[] = $count ?: 0;
            }

            return $hourlyActivity;
        });
    }

    /**
     * Clear all dashboard caches (call this when data is updated)
     */
    public function clearAll(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $week = Carbon::today()->format('Y-W');
        $month = Carbon::now()->format('Y-m');

        Cache::forget('dashboard:online_riders_count');
        Cache::forget('dashboard:total_active_riders');
        Cache::forget('dashboard:total_installations');
        Cache::forget("dashboard:today_locations:$today");
        Cache::forget("dashboard:today_performance:$today");
        Cache::forget("dashboard:weekly_trend:$week");
        Cache::forget("dashboard:monthly_comparison:$month");
        Cache::forget("dashboard:hourly_activity:$today");
    }

    /**
     * Clear only today's performance cache (call when sessions change)
     */
    public function clearTodayPerformance(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        Cache::forget("dashboard:today_performance:$today");
        Cache::forget("dashboard:today_locations:$today");
        Cache::forget("dashboard:hourly_activity:$today");
    }

    /**
     * Haversine distance calculation
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
