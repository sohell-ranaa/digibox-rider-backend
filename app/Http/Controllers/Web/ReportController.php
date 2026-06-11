<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\StopRecord;
use App\Models\InstallationVisit;
use App\Models\DutySession;
use App\Models\LocationPoint;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function stops(Request $request)
    {
        $query = StopRecord::with(['rider', 'dutySession']);

        if ($request->rider_id) {
            $query->where('rider_id', $request->rider_id);
        }

        if ($request->date_from) {
            $query->whereDate('started_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('started_at', '<=', $request->date_to);
        }

        $stops = $query->orderBy('started_at', 'desc')->paginate(20);
        $riders = Rider::where('is_active', true)->orderBy('name')->get();

        return view('reports.stops', compact('stops', 'riders'));
    }

    public function visits(Request $request)
    {
        $query = InstallationVisit::with(['rider', 'installation', 'dutySession']);

        if ($request->rider_id) {
            $query->where('rider_id', $request->rider_id);
        }

        if ($request->installation_id) {
            $query->where('installation_id', $request->installation_id);
        }

        if ($request->date_from) {
            $query->whereDate('arrived_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('arrived_at', '<=', $request->date_to);
        }

        $visits = $query->orderBy('arrived_at', 'desc')->paginate(20);
        $riders = Rider::where('is_active', true)->orderBy('name')->get();
        $installations = \App\Models\InstallationLocation::where('is_active', true)->orderBy('name')->get();

        return view('reports.visits', compact('visits', 'riders', 'installations'));
    }

    public function analytics()
    {
        // Today's stats
        $today = Carbon::today();
        $todayLocations = LocationPoint::whereDate('recorded_at', $today)->count();
        $todayStops = StopRecord::whereDate('started_at', $today)->count();
        $todayVisits = InstallationVisit::whereDate('arrived_at', $today)->count();
        $activeDutySessions = DutySession::where('status', 'active')->count();

        // This month's stats
        $monthStart = Carbon::now()->startOfMonth();
        $monthLocations = LocationPoint::where('recorded_at', '>=', $monthStart)->count();
        $monthStops = StopRecord::where('started_at', '>=', $monthStart)->count();
        $monthVisits = InstallationVisit::where('arrived_at', '>=', $monthStart)->count();
        $monthDutySessions = DutySession::where('started_at', '>=', $monthStart)->count();

        // Last 7 days chart data
        $days = [];
        $locations = [];
        $stops = [];
        $visits = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->format('M d');
            $locations[] = LocationPoint::whereDate('recorded_at', $date)->count();
            $stops[] = StopRecord::whereDate('started_at', $date)->count();
            $visits[] = InstallationVisit::whereDate('arrived_at', $date)->count();
        }

        // Top riders by duty sessions
        $topRiders = Rider::withCount('dutySessions')
            ->orderBy('duty_sessions_count', 'desc')
            ->take(5)
            ->get();

        return view('reports.analytics', compact(
            'todayLocations', 'todayStops', 'todayVisits', 'activeDutySessions',
            'monthLocations', 'monthStops', 'monthVisits', 'monthDutySessions',
            'days', 'locations', 'stops', 'visits', 'topRiders'
        ));
    }
}
