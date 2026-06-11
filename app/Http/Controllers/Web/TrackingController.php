<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DutySession;
use App\Models\InstallationLocation;
use App\Models\InstallationVisit;
use App\Models\LocationPoint;
use App\Models\Rider;
use App\Models\StopRecord;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index()
    {
        // Get all riders for dropdown filter
        $riders = Rider::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get all installations for map markers
        $installations = InstallationLocation::where('is_active', true)->get();

        return view('tracking.index', compact('riders', 'installations'));
    }

    public function getActiveRiders()
    {
        $activeSessions = DutySession::with(['rider', 'locationPoints' => function($query) {
            $query->orderBy('recorded_at', 'desc')->first();
        }])->where('status', 'active')->get();

        $riders = [];
        foreach ($activeSessions as $session) {
            $latestLocation = LocationPoint::where('duty_session_id', $session->id)
                ->orderBy('recorded_at', 'desc')
                ->first();

            if ($latestLocation) {
                $riders[] = [
                    'id' => $session->rider->id,
                    'name' => $session->rider->name,
                    'username' => $session->rider->username,
                    'latitude' => $latestLocation->latitude,
                    'longitude' => $latestLocation->longitude,
                    'recorded_at' => $latestLocation->recorded_at->diffForHumans(),
                    'recorded_at_full' => $latestLocation->recorded_at->format('Y-m-d H:i:s'),
                    'started_at' => $session->started_at->diffForHumans(),
                    'accuracy' => $latestLocation->accuracy,
                    'speed' => $latestLocation->speed,
                ];
            }
        }

        return response()->json($riders);
    }

    public function getAllRidersStatus()
    {
        $allRiders = Rider::where('is_active', true)->orderBy('name')->get();

        $ridersData = [];
        foreach ($allRiders as $rider) {
            // Get active duty session
            $activeSession = DutySession::where('rider_id', $rider->id)
                ->where('status', 'active')
                ->first();

            // Get latest location (regardless of duty status)
            $latestLocation = LocationPoint::where('rider_id', $rider->id)
                ->orderBy('recorded_at', 'desc')
                ->first();

            if ($latestLocation) {
                $ridersData[] = [
                    'id' => $rider->id,
                    'name' => $rider->name,
                    'username' => $rider->username,
                    'latitude' => (float) $latestLocation->latitude,
                    'longitude' => (float) $latestLocation->longitude,
                    'recorded_at' => $latestLocation->recorded_at->diffForHumans(),
                    'recorded_at_full' => $latestLocation->recorded_at->format('Y-m-d H:i:s'),
                    'is_on_duty' => $activeSession !== null,
                    'duty_started_at' => $activeSession ? $activeSession->started_at->format('h:i A') : null,
                    'duty_duration' => $activeSession ? $activeSession->started_at->diffForHumans(null, true) : null,
                    'status' => $activeSession ? 'On Duty' : 'Off Duty',
                ];
            } else {
                // Rider exists but no location data
                $ridersData[] = [
                    'id' => $rider->id,
                    'name' => $rider->name,
                    'username' => $rider->username,
                    'latitude' => null,
                    'longitude' => null,
                    'recorded_at' => 'Never',
                    'recorded_at_full' => null,
                    'is_on_duty' => false,
                    'duty_started_at' => null,
                    'duty_duration' => null,
                    'status' => 'No Data',
                ];
            }
        }

        return response()->json($ridersData);
    }

    public function getRiderLocation($riderId)
    {
        // Get latest location for specific rider
        $location = LocationPoint::where('rider_id', $riderId)
            ->orderBy('recorded_at', 'desc')
            ->first();

        $session = DutySession::where('rider_id', $riderId)
            ->where('status', 'active')
            ->first();

        $rider = Rider::find($riderId);

        if (!$location) {
            return response()->json([
                'error' => 'No location data found for this rider',
                'is_active' => false
            ], 404);
        }

        return response()->json([
            'id' => $rider->id,
            'name' => $rider->name,
            'username' => $rider->username,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'recorded_at' => $location->recorded_at->diffForHumans(),
            'recorded_at_full' => $location->recorded_at->format('Y-m-d H:i:s'),
            'started_at' => $session ? $session->started_at->diffForHumans() : 'Not on duty',
            'accuracy' => $location->accuracy,
            'speed' => $location->speed,
            'is_active' => $session !== null
        ]);
    }

    public function getHistoricalData(Request $request)
    {
        $riderId = $request->input('rider_id');
        $startDate = $request->input('start_date', now()->startOfDay());
        $endDate = $request->input('end_date', now()->endOfDay());

        if (!$riderId) {
            return response()->json(['error' => 'Rider ID is required'], 400);
        }

        // Get duty sessions within date range
        $sessions = DutySession::where('rider_id', $riderId)
            ->whereBetween('started_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(function($session) {
                return [
                    'id' => $session->id,
                    'started_at' => $session->started_at->format('Y-m-d H:i:s'),
                    'ended_at' => $session->ended_at ? $session->ended_at->format('Y-m-d H:i:s') : null,
                    'duration_minutes' => $session->total_duration_minutes,
                    'status' => $session->status,
                ];
            });

        // Get all location points for the rider within date range
        $locations = LocationPoint::where('rider_id', $riderId)
            ->whereBetween('recorded_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('recorded_at', 'asc')
            ->get()
            ->map(function($loc) {
                return [
                    'latitude' => (float) $loc->latitude,
                    'longitude' => (float) $loc->longitude,
                    'recorded_at' => $loc->recorded_at->format('Y-m-d H:i:s'),
                    'recorded_at_human' => $loc->recorded_at->format('M d, h:i A'),
                    'duty_session_id' => $loc->duty_session_id,
                ];
            });

        // Get installation visits
        $visits = InstallationVisit::with('installationLocation')
            ->where('rider_id', $riderId)
            ->whereBetween('arrived_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get()
            ->map(function($visit) {
                return [
                    'duty_session_id' => $visit->duty_session_id,
                    'installation_name' => $visit->installationLocation->name,
                    'installation_address' => $visit->installationLocation->address,
                    'latitude' => (float) $visit->installationLocation->latitude,
                    'longitude' => (float) $visit->installationLocation->longitude,
                    'arrived_at' => $visit->arrived_at->format('Y-m-d H:i:s'),
                    'departed_at' => $visit->departed_at ? $visit->departed_at->format('Y-m-d H:i:s') : null,
                    'duration_minutes' => $visit->duration_minutes,
                    'status' => $visit->status,
                ];
            });

        // Get stops
        $stops = StopRecord::where('rider_id', $riderId)
            ->whereBetween('started_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get()
            ->map(function($stop) {
                return [
                    'duty_session_id' => $stop->duty_session_id,
                    'latitude' => (float) $stop->latitude,
                    'longitude' => (float) $stop->longitude,
                    'started_at' => $stop->started_at->format('Y-m-d H:i:s'),
                    'ended_at' => $stop->ended_at ? $stop->ended_at->format('Y-m-d H:i:s') : null,
                    'duration_minutes' => $stop->duration_minutes,
                ];
            });

        return response()->json([
            'sessions' => $sessions,
            'locations' => $locations,
            'visits' => $visits,
            'stops' => $stops,
            'total_points' => $locations->count(),
        ]);
    }
}
