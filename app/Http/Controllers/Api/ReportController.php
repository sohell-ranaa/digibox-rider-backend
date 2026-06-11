<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DutySession;
use App\Models\StopRecord;
use App\Models\InstallationVisit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function daily(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $rider = $request->user();
        $date = $request->get('date', now()->toDateString());

        $sessions = DutySession::where('rider_id', $rider->id)
            ->whereDate('started_at', $date)
            ->with(['stopRecords', 'installationVisits.installationLocation'])
            ->get();

        $totalDutyHours = $sessions->sum('total_duration_minutes') / 60;
        $totalDistance = $sessions->sum('total_distance_km');
        $totalStops = $sessions->sum('total_stops');
        $totalInstallationVisits = InstallationVisit::where('rider_id', $rider->id)
            ->whereDate('arrived_at', $date)
            ->count();

        return response()->json([
            'date' => $date,
            'total_duty_hours' => round($totalDutyHours, 2),
            'total_distance_km' => round($totalDistance, 2),
            'total_stops' => $totalStops,
            'total_installation_visits' => $totalInstallationVisits,
            'sessions' => $sessions,
        ]);
    }

    public function installationVisits(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $rider = $request->user();

        $query = InstallationVisit::where('rider_id', $rider->id)
            ->with('installationLocation')
            ->orderBy('arrived_at', 'desc');

        if ($request->from) {
            $query->where('arrived_at', '>=', $request->from);
        }

        if ($request->to) {
            $query->where('arrived_at', '<=', $request->to);
        }

        $visits = $query->get();

        return response()->json([
            'visits' => $visits,
        ]);
    }
}
