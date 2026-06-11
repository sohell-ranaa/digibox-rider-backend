<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DutySession;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DutyController extends Controller
{
    public function start(Request $request)
    {
        $rider = $request->user();

        // Check if there's already an active session
        $activeSession = DutySession::where('rider_id', $rider->id)
            ->where('status', 'active')
            ->first();

        if ($activeSession) {
            return response()->json([
                'message' => 'You already have an active duty session',
                'duty_session' => $activeSession,
            ], 400);
        }

        // Create new duty session
        $session = DutySession::create([
            'rider_id' => $rider->id,
            'started_at' => now(),
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Duty session started successfully',
            'duty_session' => $session,
        ], 201);
    }

    public function stop(Request $request)
    {
        $rider = $request->user();

        $session = DutySession::where('rider_id', $rider->id)
            ->where('status', 'active')
            ->first();

        if (! $session) {
            return response()->json([
                'message' => 'No active duty session found',
            ], 404);
        }

        // Update session
        $session->ended_at = now();
        $session->total_duration_minutes = $session->started_at->diffInMinutes($session->ended_at);
        $session->status = 'completed';
        $session->save();

        return response()->json([
            'message' => 'Duty session ended successfully',
            'duty_session' => $session,
        ]);
    }

    public function current(Request $request)
    {
        $rider = $request->user();

        $session = DutySession::where('rider_id', $rider->id)
            ->where('status', 'active')
            ->first();

        if (! $session) {
            return response()->json([
                'duty_session' => null,
            ]);
        }

        return response()->json([
            'duty_session' => $session,
        ]);
    }

    public function history(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $rider = $request->user();

        $query = DutySession::where('rider_id', $rider->id)
            ->where('status', 'completed')
            ->orderBy('started_at', 'desc');

        if ($request->from) {
            $query->where('started_at', '>=', $request->from);
        }

        if ($request->to) {
            $query->where('started_at', '<=', $request->to);
        }

        $sessions = $query->get();

        return response()->json([
            'sessions' => $sessions,
        ]);
    }
}
