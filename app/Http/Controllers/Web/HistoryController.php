<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\DutySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Get batches for this session
        $batches = DB::table('location_batches')
            ->where('duty_session_id', $dutySessionId)
            ->orderBy('batch_start_time')
            ->get();

        $locations = [];
        foreach ($batches as $batch) {
            $points = json_decode($batch->points, true);
            $batchDate = substr($batch->batch_start_time, 0, 10);

            foreach ($points as $point) {
                $locations[] = [
                    'latitude' => $point['lat'],
                    'longitude' => $point['lng'],
                    'recorded_at' => $batchDate . ' ' . $point['ts'],
                    'speed' => $point['spd'],
                ];
            }
        }

        return response()->json($locations);
    }
}
