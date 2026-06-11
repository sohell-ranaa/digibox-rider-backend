<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemEvent;
use Illuminate\Http\Request;

class SystemEventController extends Controller
{
    public function record(Request $request)
    {
        $validated = $request->validate([
            'event_type' => 'required|in:location_on,location_off,internet_on,internet_off',
            'occurred_at' => 'required|date',
            'duty_session_id' => 'nullable|exists:duty_sessions,id',
            'additional_data' => 'nullable|array',
        ]);

        $rider = $request->user();

        $event = SystemEvent::create([
            'rider_id' => $rider->id,
            'duty_session_id' => $validated['duty_session_id'] ?? null,
            'event_type' => $validated['event_type'],
            'occurred_at' => $validated['occurred_at'],
            'additional_data' => $validated['additional_data'] ?? null,
        ]);

        return response()->json([
            'message' => 'Event recorded successfully',
            'id' => $event->id,
        ], 201);
    }
}
