<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\DutySession;
use App\Models\LocationPoint;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $riders = Rider::where('is_active', true)->orderBy('name')->get();
        $selectedRider = $request->rider_id;
        $selectedDate = $request->date ?? Carbon::today()->toDateString();

        $dutySessions = [];
        if ($selectedRider) {
            $dutySessions = DutySession::where('rider_id', $selectedRider)
                ->whereDate('started_at', $selectedDate)
                ->with('rider')
                ->orderBy('started_at', 'desc')
                ->get();
        }

        return view('history.index', compact('riders', 'selectedRider', 'selectedDate', 'dutySessions'));
    }

    public function getRoute(Request $request)
    {
        $dutySessionId = $request->duty_session_id;

        if (!$dutySessionId) {
            return response()->json(['error' => 'Duty session ID required'], 400);
        }

        $locations = LocationPoint::where('duty_session_id', $dutySessionId)
            ->orderBy('recorded_at')
            ->get()
            ->map(function($location) {
                return [
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'recorded_at' => $location->recorded_at->format('Y-m-d H:i:s'),
                    'speed' => $location->speed,
                ];
            });

        return response()->json($locations);
    }
}
