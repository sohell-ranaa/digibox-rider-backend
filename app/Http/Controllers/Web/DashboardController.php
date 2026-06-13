<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\DutySession;
use App\Models\StopRecord;
use App\Models\InstallationLocation;
use App\Models\InstallationVisit;
use App\Services\Cache\DashboardCacheService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $cacheService;

    public function __construct(DashboardCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index()
    {
        $today = Carbon::today();
        $now = Carbon::now();

        // =====================================================
        // SECTION 1: KEY METRICS (Top Cards) - CACHED
        // =====================================================

        // Use cached data for better performance
        $totalRiders = $this->cacheService->getTotalActiveRiders();
        $onlineRiders = $this->cacheService->getOnlineRidersCount();
        $todayLocations = $this->cacheService->getTodayLocations();
        $totalInstallations = $this->cacheService->getTotalInstallations();

        // =====================================================
        // SECTION 2: TODAY'S PERFORMANCE - CACHED
        // =====================================================

        // Use cached performance data
        $performance = $this->cacheService->getTodayPerformance();
        $todaySessions = $performance['sessions'];
        $todayStops = $performance['stops'];
        $todayVisits = $performance['visits'];
        $todayDutyHours = $performance['hours'];
        $todayDistance = $performance['distance'];

        // =====================================================
        // SECTION 3: REAL-TIME ONLINE RIDERS & LIVE MAP
        // =====================================================

        // Get all riders with their latest location and online status
        $allRidersWithLocation = Rider::where('is_active', true)
            ->with(['dutySessions' => function($q) {
                $q->where('status', 'active')->latest('started_at')->limit(1);
            }])
            ->get()
            ->map(function($rider) use ($now) {
                // Get latest location from batches
                $latestBatch = DB::table('location_batches')
                    ->where('rider_id', $rider->id)
                    ->orderBy('batch_end_time', 'desc')
                    ->first();

                $latestLocation = null;
                if ($latestBatch) {
                    $points = json_decode($latestBatch->points, true);
                    $lastPoint = end($points);
                    if ($lastPoint) {
                        $latestLocation = (object)[
                            'latitude' => $lastPoint['lat'],
                            'longitude' => $lastPoint['lng'],
                            'recorded_at' => Carbon::parse(substr($latestBatch->batch_end_time, 0, 10) . ' ' . $lastPoint['ts']),
                            'accuracy' => $lastPoint['acc'] ?? 0,
                            'speed' => $lastPoint['spd'] ?? 0,
                        ];
                    }
                }

                $activeSession = $rider->dutySessions->first();

                // Determine if truly online (location data in last 10 min)
                $isOnline = $latestLocation && $latestLocation->recorded_at->diffInMinutes($now) < 10;

                $rider->is_currently_online = $isOnline;
                $rider->latest_location = $latestLocation;
                $rider->active_session = $activeSession;

                if ($activeSession) {
                    $duration = $activeSession->started_at->diff($now);
                    $rider->session_duration = sprintf(
                        '%02d:%02d:%02d',
                        $duration->h + ($duration->days * 24),
                        $duration->i,
                        $duration->s
                    );
                }

                return $rider;
            });

        // Filter for online riders only (for the active riders list)
        $onlineRidersList = $allRidersWithLocation->filter(function($rider) {
            return $rider->is_currently_online;
        })->sortByDesc(function($rider) {
            return $rider->latest_location ? $rider->latest_location->recorded_at : null;
        })->values();

        // =====================================================
        // SECTION 4: RECENT ACTIVITY (Last 15 minutes)
        // =====================================================

        // Get recent batches from last 15 minutes
        $recentBatches = DB::table('location_batches')
            ->join('riders', 'location_batches.rider_id', '=', 'riders.id')
            ->join('duty_sessions', 'location_batches.duty_session_id', '=', 'duty_sessions.id')
            ->where('location_batches.batch_end_time', '>=', $now->copy()->subMinutes(15))
            ->orderBy('location_batches.batch_end_time', 'desc')
            ->take(20)
            ->select('location_batches.*', 'riders.name as rider_name', 'duty_sessions.started_at')
            ->get();

        // Convert to collection format for view compatibility
        $recentActivity = $recentBatches->map(function($batch) {
            $points = json_decode($batch->points, true);
            $lastPoint = end($points);
            return (object)[
                'rider' => (object)['name' => $batch->rider_name],
                'dutySession' => (object)['started_at' => Carbon::parse($batch->started_at)],
                'recorded_at' => Carbon::parse(substr($batch->batch_end_time, 0, 10) . ' ' . $lastPoint['ts']),
                'latitude' => $lastPoint['lat'] ?? 0,
                'longitude' => $lastPoint['lng'] ?? 0,
            ];
        });

        // =====================================================
        // SECTION 5: THIS WEEK'S TREND (7 days chart) - CACHED
        // =====================================================

        $weeklyTrend = $this->cacheService->getWeeklyTrend();
        $weekDays = $weeklyTrend['days'];
        $weekSessions = $weeklyTrend['sessions'];
        $weekVisits = $weeklyTrend['visits'];
        $weekLocations = $weeklyTrend['locations'];

        // =====================================================
        // SECTION 6: RIDER PERFORMANCE (Top 5)
        // =====================================================

        // Top riders by duty sessions this month
        $monthStart = Carbon::now()->startOfMonth();
        $topRiders = Rider::withCount([
                'dutySessions' => function($q) use ($monthStart) {
                    $q->where('started_at', '>=', $monthStart);
                }
            ])
            ->where('is_active', true)
            ->orderBy('duty_sessions_count', 'desc')
            ->take(5)
            ->get()
            ->map(function($rider) use ($monthStart) {
                // Calculate total hours this month
                $totalMinutes = DutySession::where('rider_id', $rider->id)
                    ->where('started_at', '>=', $monthStart)
                    ->sum('total_duration_minutes');
                $rider->total_hours = round($totalMinutes / 60, 1);

                // Calculate total visits this month
                $rider->total_visits = InstallationVisit::where('rider_id', $rider->id)
                    ->where('arrived_at', '>=', $monthStart)
                    ->count();

                return $rider;
            });

        // =====================================================
        // SECTION 7: HOURLY ACTIVITY TODAY - CACHED
        // =====================================================

        $hourlyActivity = $this->cacheService->getHourlyActivity();

        // =====================================================
        // SECTION 8: ALERTS & ISSUES
        // =====================================================

        // Sessions active for > 12 hours (potential issue)
        $longSessions = DutySession::where('status', 'active')
            ->where('started_at', '<', $now->copy()->subHours(12))
            ->count();

        // Riders with no activity in last 7 days
        $inactiveRiders = Rider::where('is_active', true)
            ->whereDoesntHave('dutySessions', function($q) {
                $q->where('started_at', '>=', Carbon::now()->subDays(7));
            })
            ->count();

        // =====================================================
        // SECTION 9: MONTH COMPARISON - CACHED
        // =====================================================

        $monthlyData = $this->cacheService->getMonthlyComparison();
        $thisMonthSessions = $monthlyData['thisMonthSessions'];
        $lastMonthSessions = $monthlyData['lastMonthSessions'];
        $sessionsChange = $monthlyData['sessionsChange'];
        $thisMonthVisits = $monthlyData['thisMonthVisits'];
        $lastMonthVisits = $monthlyData['lastMonthVisits'];
        $visitsChange = $monthlyData['visitsChange'];

        // =====================================================
        // SECTION 10: GPS TRACKING QUALITY METRICS
        // =====================================================

        // Average GPS accuracy today (lower is better) - weighted average from batches
        $todayAvgAccuracy = DB::table('location_batches')
            ->whereDate('batch_start_time', $today)
            ->avg('avg_accuracy');
        $todayAvgAccuracy = $todayAvgAccuracy ? round($todayAvgAccuracy, 1) : 0;

        // For high accuracy count, we approximate based on batches with good avg accuracy
        $todayHighAccuracyCount = DB::table('location_batches')
            ->whereDate('batch_start_time', $today)
            ->where('avg_accuracy', '<', 20)
            ->sum('point_count') ?: 0;
        $todayHighAccuracyPercent = $todayLocations > 0
            ? round(($todayHighAccuracyCount / $todayLocations) * 100, 1)
            : 0;

        return view('dashboard.index', compact(
            // Key Metrics
            'totalRiders',
            'onlineRiders',
            'todayLocations',
            'totalInstallations',

            // Today's Performance
            'todaySessions',
            'todayStops',
            'todayVisits',
            'todayDutyHours',
            'todayDistance',

            // Real-time Data
            'onlineRidersList',
            'allRidersWithLocation',
            'recentActivity',

            // Charts
            'weekDays',
            'weekSessions',
            'weekVisits',
            'weekLocations',
            'hourlyActivity',

            // Performance
            'topRiders',

            // Alerts
            'longSessions',
            'inactiveRiders',

            // Trends
            'thisMonthSessions',
            'lastMonthSessions',
            'sessionsChange',
            'thisMonthVisits',
            'lastMonthVisits',
            'visitsChange',

            // GPS Quality
            'todayAvgAccuracy',
            'todayHighAccuracyPercent'
        ));
    }

    /**
     * Calculate distance between two GPS points using Haversine formula
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
