<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\DutySession;
use App\Models\LocationPoint;
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

        // Riders currently on duty RIGHT NOW
        $activeRiders = DutySession::where('status', 'active')->count();

        // Today's location points recorded
        $todayLocations = LocationPoint::whereDate('recorded_at', $today)->count();

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
        // SECTION 3: REAL-TIME ACTIVE SESSIONS
        // =====================================================

        $activeSessions = DutySession::with('rider')
            ->where('status', 'active')
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(function($session) use ($now) {
                // Calculate current duration
                $duration = $session->started_at->diff($now);
                $session->current_duration = sprintf(
                    '%02d:%02d:%02d',
                    $duration->h + ($duration->days * 24),
                    $duration->i,
                    $duration->s
                );

                // Get latest location for this session
                $session->latest_location = LocationPoint::where('duty_session_id', $session->id)
                    ->orderBy('recorded_at', 'desc')
                    ->first();

                return $session;
            });

        // =====================================================
        // SECTION 4: RECENT ACTIVITY (Last 15 minutes)
        // =====================================================

        $recentActivity = LocationPoint::with(['rider', 'dutySession'])
            ->where('recorded_at', '>=', $now->copy()->subMinutes(15))
            ->orderBy('recorded_at', 'desc')
            ->take(20)
            ->get();

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

            // Locations per day
            $weekLocations[] = LocationPoint::whereDate('recorded_at', $date)->count();
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
            $hourlyActivity[] = LocationPoint::whereDate('recorded_at', $today)
                ->whereRaw('HOUR(recorded_at) = ?', [$hour])
                ->count();
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

        return view('dashboard.index', compact(
            // Key Metrics
            'totalRiders',
            'activeRiders',
            'todayLocations',
            'totalInstallations',

            // Today's Performance
            'todaySessions',
            'todayStops',
            'todayVisits',
            'todayDutyHours',
            'todayDistance',

            // Real-time Data
            'activeSessions',
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
            'visitsChange'
        ));
    }
}
