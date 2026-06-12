<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DutySession;
use App\Models\InstallationLocation;
use App\Models\InstallationVisit;
use App\Models\Rider;
use App\Models\StopRecord;
use App\Services\AIRouteProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $activeSessions = DutySession::with('rider')
            ->where('status', 'active')
            ->get();

        $riders = [];
        foreach ($activeSessions as $session) {
            // Get latest batch for this session
            $latestBatch = DB::table('location_batches')
                ->where('duty_session_id', $session->id)
                ->orderBy('batch_end_time', 'desc')
                ->first();

            if ($latestBatch) {
                // Extract last point from batch
                $points = json_decode($latestBatch->points, true);
                $lastPoint = end($points);
                $latestLocation = (object)[
                    'latitude' => $lastPoint['lat'],
                    'longitude' => $lastPoint['lng'],
                    'recorded_at' => Carbon::parse(substr($latestBatch->batch_end_time, 0, 10) . ' ' . $lastPoint['ts']),
                    'accuracy' => $lastPoint['acc'] ?? 0,
                    'speed' => $lastPoint['spd'] ?? 0,
                ];
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
            $latestBatch = DB::table('location_batches')
                ->where('rider_id', $rider->id)
                ->orderBy('batch_end_time', 'desc')
                ->first();

            // ONLY include riders who have location data
            // Skip riders who have never sent any GPS data
            if ($latestBatch) {
                // Extract last point from batch
                $points = json_decode($latestBatch->points, true);
                $lastPoint = end($points);
                $latestLocation = (object)[
                    'latitude' => $lastPoint['lat'],
                    'longitude' => $lastPoint['lng'],
                    'recorded_at' => Carbon::parse(substr($latestBatch->batch_end_time, 0, 10) . ' ' . $lastPoint['ts']),
                    'accuracy' => $lastPoint['acc'] ?? 0,
                    'speed' => $lastPoint['spd'] ?? 0,
                ];
                // Determine if online (last location within 10 minutes)
                $isOnline = $latestLocation->recorded_at->diffInMinutes(now()) < 10;

                $ridersData[] = [
                    'id' => $rider->id,
                    'name' => $rider->name,
                    'username' => $rider->username,
                    'latitude' => (float) $latestLocation->latitude,
                    'longitude' => (float) $latestLocation->longitude,
                    'recorded_at' => $latestLocation->recorded_at->diffForHumans(),
                    'recorded_at_full' => $latestLocation->recorded_at->format('Y-m-d H:i:s'),
                    'is_online' => $isOnline,
                    'has_active_session' => $activeSession !== null,
                    'duty_started_at' => $activeSession ? $activeSession->started_at->format('h:i A') : null,
                    'duty_duration' => $activeSession ? $activeSession->started_at->diffForHumans(null, true) : null,
                    'status' => $isOnline ? 'Online' : 'Offline',
                ];
            }
            // Skip riders with no location data - don't show them on the map
        }

        return response()->json($ridersData);
    }

    public function getRiderLocation($riderId)
    {
        // Get latest location for specific rider
        $latestBatch = DB::table('location_batches')
            ->where('rider_id', $riderId)
            ->orderBy('batch_end_time', 'desc')
            ->first();

        if (!$latestBatch) {
            return response()->json(null);
        }

        // Extract last point from batch
        $points = json_decode($latestBatch->points, true);
        $lastPoint = end($points);
        $location = (object)[
            'latitude' => $lastPoint['lat'],
            'longitude' => $lastPoint['lng'],
            'recorded_at' => Carbon::parse(substr($latestBatch->batch_end_time, 0, 10) . ' ' . $lastPoint['ts']),
        ];

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
        $rawLocations = $this->getLocationPointsFromBatches($riderId, $startDate, $endDate);

        // Convert to array format for AI processing
        $rawPoints = $rawLocations->map(function($loc) {
            return [
                'latitude' => (float) $loc->latitude,
                'longitude' => (float) $loc->longitude,
                'speed' => (float) ($loc->speed ?? 0),
                'accuracy' => (float) ($loc->accuracy ?? 10),
                'timestamp' => $loc->recorded_at->timestamp,
                'recorded_at' => $loc->recorded_at->format('Y-m-d H:i:s'),
                'recorded_at_human' => $loc->recorded_at->format('M d, h:i A'),
                'duty_session_id' => $loc->duty_session_id,
            ];
        })->toArray();

        // === AI PROCESSING ===
        // For web display, use MINIMAL processing to preserve route detail
        // Only remove obvious outliers, NO path simplification
        $processedRoute = [];
        $aiStats = [];

        if (count($rawPoints) > 0) {
            Log::info("🤖 [Web AI] Processing route: " . count($rawPoints) . " points");

            // Only remove outliers, skip simplification for web
            $cleanedPoints = AIRouteProcessor::removeOutliers($rawPoints);

            // Calculate distance for stats
            $totalDistance = 0.0;
            for ($i = 1; $i < count($cleanedPoints); $i++) {
                $lat1 = $cleanedPoints[$i - 1]['latitude'];
                $lon1 = $cleanedPoints[$i - 1]['longitude'];
                $lat2 = $cleanedPoints[$i]['latitude'];
                $lon2 = $cleanedPoints[$i]['longitude'];

                $totalDistance += $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
            }

            // Format processed points for frontend
            $locations = collect($cleanedPoints);

            $aiStats = [
                'original_points' => count($rawPoints),
                'processed_points' => count($cleanedPoints),
                'points_removed' => count($rawPoints) - count($cleanedPoints),
                'compression_percentage' => number_format((1 - count($cleanedPoints) / count($rawPoints)) * 100, 1),
                'total_distance_km' => number_format($totalDistance / 1000, 2),
            ];

            Log::info("🤖 [Web AI] Result: " . count($cleanedPoints) . " points (outlier removal only, no simplification)");
        } else {
            $locations = collect([]);
        }

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

        // Get all active installations to show on map
        $allInstallations = InstallationLocation::where('is_active', true)
            ->get()
            ->map(function($installation) {
                return [
                    'id' => $installation->id,
                    'name' => $installation->name,
                    'address' => $installation->address,
                    'latitude' => (float) $installation->latitude,
                    'longitude' => (float) $installation->longitude,
                ];
            });

        return response()->json([
            'sessions' => $sessions,
            'locations' => $locations,
            'visits' => $visits,
            'stops' => $stops,
            'installations' => $allInstallations,
            'total_points' => $locations->count(),
            'ai_processing' => $aiStats,
        ]);
    }

    /**
     * Calculate distance between two GPS points using Haversine formula
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get location points from batches
     * Converts JSON batches back to individual points for compatibility
     */
    private function getLocationPointsFromBatches($riderId, $startDate, $endDate)
    {
        $batches = DB::table('location_batches')
            ->where('rider_id', $riderId)
            ->whereBetween('batch_start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('batch_start_time', 'asc')
            ->get();

        $allPoints = [];

        foreach ($batches as $batch) {
            $points = json_decode($batch->points, true);
            $batchDate = substr($batch->batch_start_time, 0, 10);

            foreach ($points as $point) {
                $allPoints[] = (object)[
                    'rider_id' => $batch->rider_id,
                    'duty_session_id' => $batch->duty_session_id,
                    'latitude' => $point['lat'],
                    'longitude' => $point['lng'],
                    'accuracy' => $point['acc'],
                    'speed' => $point['spd'],
                    'bearing' => null,
                    'altitude' => null,
                    'recorded_at' => Carbon::parse($batchDate . ' ' . $point['ts']),
                ];
            }
        }

        return collect($allPoints);
    }
}
