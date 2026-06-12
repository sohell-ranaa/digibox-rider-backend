<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RiderController extends Controller
{
    public function index()
    {
        $riders = Rider::withCount('dutySessions')
            ->with(['dutySessions' => function($query) {
                $query->where('status', 'active')->latest('started_at')->limit(1);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Enhance each rider with computed stats
        $riders->each(function($rider) {
            // Note: is_online and is_on_duty are now computed via model accessors
            // which automatically handle stale session detection

            // Last seen - get from latest batch
            $latestBatch = DB::table('location_batches')
                ->where('rider_id', $rider->id)
                ->orderBy('batch_end_time', 'desc')
                ->first();

            if ($latestBatch) {
                $points = json_decode($latestBatch->points, true);
                $lastPoint = end($points);
                if ($lastPoint) {
                    $rider->last_seen = Carbon::parse(substr($latestBatch->batch_end_time, 0, 10) . ' ' . $lastPoint['ts']);
                } else {
                    $rider->last_seen = null;
                }
            } else {
                $rider->last_seen = null;
            }

            // Calculate total duty hours this week
            $weekStart = now()->startOfWeek();
            $rider->weekly_hours = $rider->dutySessions()
                ->where('started_at', '>=', $weekStart)
                ->where('status', 'completed')
                ->get()
                ->sum(function($session) {
                    if ($session->ended_at) {
                        return $session->started_at->diffInHours($session->ended_at);
                    }
                    return 0;
                });

            // Count completed sessions this month
            $monthStart = now()->startOfMonth();
            $rider->monthly_sessions = $rider->dutySessions()
                ->where('started_at', '>=', $monthStart)
                ->count();
        });

        return view('riders.index', compact('riders'));
    }

    public function create()
    {
        return view('riders.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:riders,username',
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'password' => 'required|string|min:6',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active');

        Rider::create($validated);

        return redirect()->route('riders.index')->with('success', 'Rider created successfully!');
    }

    public function show(Rider $rider)
    {
        // Get filter period from request (default: 30 days)
        $period = request()->get('period', 30);
        $startDate = now()->subDays($period)->startOfDay();

        // Load all duty sessions within the period
        $dutySessions = $rider->dutySessions()
            ->where('started_at', '>=', $startDate)
            ->orderBy('started_at', 'desc')
            ->get();

        // Get total location count from batches for these sessions
        $sessionIds = $dutySessions->pluck('id')->toArray();
        $totalLocationCount = DB::table('location_batches')
            ->whereIn('duty_session_id', $sessionIds)
            ->sum('point_count');

        // Calculate summary statistics
        $stats = [
            'total_sessions' => $dutySessions->count(),
            'completed_sessions' => $dutySessions->where('status', 'completed')->count(),
            'active_sessions' => $dutySessions->where('status', 'active')->count(),
            'total_hours' => $dutySessions->where('status', 'completed')->sum(function($session) {
                if ($session->ended_at) {
                    return $session->started_at->diffInHours($session->ended_at);
                }
                return 0;
            }),
            'total_distance' => $dutySessions->sum('total_distance_km'),
            'total_locations' => $totalLocationCount,
        ];

        // Group sessions by date
        $dailyData = $dutySessions->groupBy(function($session) {
            return $session->started_at->format('Y-m-d');
        })->map(function($sessions, $date) {
            $completedSessions = $sessions->where('status', 'completed');
            $sessionIds = $sessions->pluck('id')->toArray();
            $dayLocationCount = DB::table('location_batches')
                ->whereIn('duty_session_id', $sessionIds)
                ->sum('point_count');

            return [
                'date' => $date,
                'sessions' => $sessions->count(),
                'completed' => $completedSessions->count(),
                'hours' => $completedSessions->sum(function($session) {
                    if ($session->ended_at) {
                        return $session->started_at->diffInMinutes($session->ended_at) / 60;
                    }
                    return 0;
                }),
                'distance' => $sessions->sum('total_distance_km'),
                'locations' => $dayLocationCount,
                'first_start' => $sessions->min('started_at'),
                'last_end' => $sessions->where('status', 'completed')->max('ended_at'),
            ];
        })->sortKeysDesc();

        // Check current status
        $currentSession = $rider->dutySessions()->where('status', 'active')->latest('started_at')->first();

        // Get latest location from batches
        $latestBatch = DB::table('location_batches')
            ->where('rider_id', $rider->id)
            ->orderBy('batch_end_time', 'desc')
            ->first();

        $lastSeen = null;
        $isOnline = false;
        if ($latestBatch) {
            $points = json_decode($latestBatch->points, true);
            $lastPoint = end($points);
            if ($lastPoint) {
                $lastSeen = Carbon::parse(substr($latestBatch->batch_end_time, 0, 10) . ' ' . $lastPoint['ts']);
                $isOnline = $lastSeen->diffInMinutes(now()) <= 10;
            }
        }

        return view('riders.show', compact('rider', 'stats', 'dailyData', 'period', 'currentSession', 'isOnline', 'lastSeen'));
    }

    public function edit(Rider $rider)
    {
        $rider->loadCount('dutySessions');
        $latestSession = $rider->dutySessions()->latest('started_at')->first();
        return view('riders.edit', compact('rider', 'latestSession'));
    }

    public function update(Request $request, Rider $rider)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:riders,username,' . $rider->id,
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'password' => 'nullable|string|min:6',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active');

        $rider->update($validated);

        return redirect()->route('riders.index')->with('success', 'Rider updated successfully!');
    }

    public function destroy(Rider $rider)
    {
        // Deactivate instead of delete
        $rider->update(['is_active' => false]);
        return redirect()->route('riders.index')->with('success', 'Rider account has been deactivated successfully!');
    }
}
