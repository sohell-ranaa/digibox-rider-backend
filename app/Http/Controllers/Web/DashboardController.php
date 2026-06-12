<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\DutySession;
use App\Models\StopRecord;
use App\Models\InstallationLocation;
use App\Models\InstallationVisit;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $now = Carbon::now();

        // =====================================================
        // SECTION 1: KEY METRICS (Top Cards)
        // =====================================================

        // Total active riders in system
        $totalRiders = Rider::where('is_active', true)->count();

        // Riders currently online RIGHT NOW (with location data in last 10 min)
        $onlineRiderIds = DB::table('location_batches')
            ->where('batch_end_time', '>=', Carbon::now()->subMinutes(10))
            ->distinct('rider_id')
            ->pluck('rider_id');
        $onlineRiders = Rider::where('is_active', true)
            ->whereIn('id', $onlineRiderIds)
            ->count();

        // Today's location points recorded (sum point_count from batches)
        $todayLocations = DB::table('location_batches')
            ->whereDate('batch_start_time', $today)
            ->sum('point_count');

        // Total installations in system
        $totalInstallations = InstallationLocation::where('is_active', true)->count();

        // =====================================================
        // SECTION 2: TODAY'S PERFORMANCE
        // =====================================================

        // Today's duty sessions (completed + active)
        $todaySessions = DutySession::whereDate('started_at', $today)->count();

        // Today's stops
        $todayStops = StopRecord::whereDate('started_at', $today)->count();

        // Today's installation visits
        $todayVisits = InstallationVisit::whereDate('arrived_at', $today)->count();

        // Total duty hours today
        $todayDutyMinutes = DutySession::whereDate('started_at', $today)
            ->sum('total_duration_minutes');
        $todayDutyHours = round($todayDutyMinutes / 60, 1);

        // Total distance covered today
        $todayDistance = DutySession::whereDate('started_at', $today)
            ->sum('total_distance_km');
        $todayDistance = round($todayDistance, 2);

        // =====================================================
        // SECTION 3: REAL-TIME ONLINE RIDERS & LIVE MAP
        // =====================================================

        // Get all riders with their latest location and online status
        $allRidersWithLocation = Rider::where('is_active', true)
            ->with(['locationPoints' => function($q) {
                $q->latest('recorded_at')->limit(1);
            }])
            ->with(['dutySessions' => function($q) {
                $q->where('status', 'active')->latest('started_at')->limit(1);
            }])
            ->get()
            ->map(function($rider) use ($now) {
                $latestLocation = $rider->locationPoints->first();
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
            ];
        });

        // =====================================================
        // SECTION 5: THIS WEEK'S TREND (7 days chart)
        // =====================================================

        $weekDays = [];
        $weekSessions = [];
        $weekVisits = [];
        $weekLocations = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $weekDays[] = $date->format('D, M j'); // "Mon, Jun 11"

            // Sessions per day
            $weekSessions[] = DutySession::whereDate('started_at', $date)->count();

            // Visits per day
            $weekVisits[] = InstallationVisit::whereDate('arrived_at', $date)->count();

            // Locations per day (sum point counts from batches)
            $weekLocations[] = DB::table('location_batches')
                ->whereDate('batch_start_time', $date)
                ->sum('point_count') ?: 0;
        }

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
        // SECTION 7: HOURLY ACTIVITY TODAY
        // =====================================================

        $hourlyActivity = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $count = DB::table('location_batches')
                ->whereDate('batch_start_time', $today)
                ->whereRaw('HOUR(batch_start_time) = ?', [$hour])
                ->sum('point_count');
            $hourlyActivity[] = $count ?: 0;
        }

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
        // SECTION 9: MONTH COMPARISON
        // =====================================================

        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // This month vs last month
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
}
