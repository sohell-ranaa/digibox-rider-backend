<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstallationLocation;
use App\Models\InstallationVisit;
use App\Models\DutySession;
use App\Services\Cache\RiderCacheService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected $riderCache;

    public function __construct(RiderCacheService $riderCache)
    {
        $this->riderCache = $riderCache;
    }
    public function record(Request $request)
    {
        $validated = $request->validate([
            'duty_session_id' => 'required|exists:duty_sessions,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'bearing' => 'nullable|numeric',
            'altitude' => 'nullable|numeric',
            'recorded_at' => 'required|date',
        ]);

        $rider = $request->user();

        // Mark rider as online in Redis cache
        $this->riderCache->markRiderOnline($rider->id, [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        // Check for installation visits (geofencing)
        $this->checkInstallationVisitsFromArray($rider->id, $validated['duty_session_id'], $validated);

        // Save to batch (single location becomes a batch of 1)
        $this->saveToBatches($rider->id, [$validated]);

        return response()->json([
            'message' => 'Location recorded successfully',
        ], 201);
    }

    public function bulk(Request $request)
    {
        // Log incoming request
        \Log::info('📥 [Bulk Upload] Received request', [
            'rider_id' => $request->user()->id,
            'locations_count' => count($request->input('locations', [])),
            'first_location' => $request->input('locations.0'),
        ]);

        // Validate basic structure
        try {
            $validated = $request->validate([
                'locations' => 'required|array',
                'locations.*.duty_session_id' => 'required|integer',
                'locations.*.latitude' => 'required|numeric|between:-90,90',
                'locations.*.longitude' => 'required|numeric|between:-180,180',
                'locations.*.accuracy' => 'nullable|numeric',
                'locations.*.speed' => 'nullable|numeric',
                'locations.*.bearing' => 'nullable|numeric',
                'locations.*.altitude' => 'nullable|numeric',
                'locations.*.recorded_at' => 'required|date',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ [Bulk Upload] Basic validation failed', [
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        $rider = $request->user();

        // Mark rider as online in Redis cache with first location
        if (!empty($validated['locations'])) {
            $firstLoc = $validated['locations'][0];
            $this->riderCache->markRiderOnline($rider->id, [
                'latitude' => $firstLoc['latitude'],
                'longitude' => $firstLoc['longitude'],
            ]);
        }

        $inserted = 0;
        $skipped = 0;
        $invalidSessionIds = [];
        $validLocations = []; // Track valid locations for batching

        foreach ($validated['locations'] as $loc) {
            // Check if duty session exists
            $sessionExists = DutySession::where('id', $loc['duty_session_id'])
                ->where('rider_id', $rider->id)
                ->exists();

            if (!$sessionExists) {
                $skipped++;
                if (!in_array($loc['duty_session_id'], $invalidSessionIds)) {
                    $invalidSessionIds[] = $loc['duty_session_id'];
                }
                continue; // Skip this location
            }

            // Check installation visits (geofencing)
            $this->checkInstallationVisitsFromArray($rider->id, $loc['duty_session_id'], $loc);
            $inserted++;

            // Store valid location for batching
            $validLocations[] = $loc;
        }

        // Save to location_batches (efficient storage)
        if (!empty($validLocations)) {
            $this->saveToBatches($rider->id, $validLocations);
        }

        if ($skipped > 0) {
            \Log::warning("⚠️ [Bulk Upload] Skipped $skipped locations with invalid session IDs: " . implode(', ', $invalidSessionIds));
        }

        \Log::info("✅ [Bulk Upload] Successfully inserted $inserted locations for rider {$rider->id}");

        $message = "$inserted locations recorded successfully";
        if ($skipped > 0) {
            $message .= " ($skipped old locations skipped)";
        }

        return response()->json([
            'message' => $message,
            'inserted' => $inserted,
            'skipped' => $skipped,
        ], 201);
    }

    public function myLatest(Request $request)
    {
        $limit = $request->get('limit', 50);
        $rider = $request->user();

        // Get latest batches and unpack points
        $batches = DB::table('location_batches')
            ->where('rider_id', $rider->id)
            ->orderBy('batch_end_time', 'desc')
            ->limit(10) // Get more batches to ensure we get enough points
            ->get();

        $allPoints = [];
        foreach ($batches as $batch) {
            $points = json_decode($batch->points, true);
            $batchDate = substr($batch->batch_start_time, 0, 10);

            foreach ($points as $point) {
                $allPoints[] = [
                    'id' => null, // Not stored anymore
                    'rider_id' => $batch->rider_id,
                    'duty_session_id' => $batch->duty_session_id,
                    'latitude' => $point['lat'],
                    'longitude' => $point['lng'],
                    'accuracy' => $point['acc'],
                    'speed' => $point['spd'],
                    'bearing' => null,
                    'altitude' => null,
                    'recorded_at' => $batchDate . ' ' . $point['ts'],
                ];
            }
        }

        // Sort by recorded_at desc and limit
        usort($allPoints, function($a, $b) {
            return strcmp($b['recorded_at'], $a['recorded_at']);
        });

        $locations = array_slice($allPoints, 0, $limit);

        return response()->json([
            'locations' => $locations,
        ]);
    }

    public function installations(Request $request)
    {
        $installations = InstallationLocation::where('is_active', true)->get();

        return response()->json([
            'installations' => $installations,
        ]);
    }

    /**
     * Check if rider is at any installation location (geofencing) - array version
     */
    private function checkInstallationVisitsFromArray($riderId, $dutySessionId, $locationArray)
    {
        $installations = InstallationLocation::where('is_active', true)->get();

        foreach ($installations as $installation) {
            $distance = $this->haversineDistance(
                $locationArray['latitude'],
                $locationArray['longitude'],
                $installation->latitude,
                $installation->longitude
            );

            // Convert km to meters
            $distanceMeters = $distance * 1000;

            // Check if rider is within geofence
            if ($distanceMeters <= $installation->geofence_radius_meters) {
                // Rider is at installation
                $this->handleInstallationEntryFromArray($riderId, $dutySessionId, $installation->id, $locationArray);
            } else {
                // Rider left installation (if there was an ongoing visit)
                $this->handleInstallationExitFromArray($riderId, $installation->id, $locationArray);
            }
        }
    }

    private function handleInstallationEntry($riderId, $dutySessionId, $installationId, $location)
    {
        // Check if there's an ongoing visit
        $ongoingVisit = InstallationVisit::where('rider_id', $riderId)
            ->where('installation_location_id', $installationId)
            ->where('status', 'ongoing')
            ->latest()
            ->first();

        if (!$ongoingVisit) {
            // Create new visit
            InstallationVisit::create([
                'rider_id' => $riderId,
                'duty_session_id' => $dutySessionId,
                'installation_location_id' => $installationId,
                'arrived_at' => $location->recorded_at,
                'status' => 'ongoing',
            ]);
        }
    }

    private function handleInstallationExit($riderId, $installationId, $location)
    {
        // Check if there's an ongoing visit to close
        $ongoingVisit = InstallationVisit::where('rider_id', $riderId)
            ->where('installation_location_id', $installationId)
            ->where('status', 'ongoing')
            ->latest()
            ->first();

        if ($ongoingVisit) {
            // Close the visit
            $ongoingVisit->departed_at = $location->recorded_at;
            $ongoingVisit->duration_minutes = $ongoingVisit->arrived_at->diffInMinutes($ongoingVisit->departed_at);
            $ongoingVisit->status = 'completed';
            $ongoingVisit->save();
        }
    }

    private function handleInstallationEntryFromArray($riderId, $dutySessionId, $installationId, $locationArray)
    {
        // Check if there's an ongoing visit
        $ongoingVisit = InstallationVisit::where('rider_id', $riderId)
            ->where('installation_location_id', $installationId)
            ->where('status', 'ongoing')
            ->latest()
            ->first();

        if (!$ongoingVisit) {
            // Create new visit
            InstallationVisit::create([
                'rider_id' => $riderId,
                'duty_session_id' => $dutySessionId,
                'installation_location_id' => $installationId,
                'arrived_at' => $locationArray['recorded_at'],
                'status' => 'ongoing',
            ]);
        }
    }

    private function handleInstallationExitFromArray($riderId, $installationId, $locationArray)
    {
        // Check if there's an ongoing visit to close
        $ongoingVisit = InstallationVisit::where('rider_id', $riderId)
            ->where('installation_location_id', $installationId)
            ->where('status', 'ongoing')
            ->latest()
            ->first();

        if ($ongoingVisit) {
            // Close the visit
            $departedAt = new \Carbon\Carbon($locationArray['recorded_at']);
            $ongoingVisit->departed_at = $departedAt;
            $ongoingVisit->duration_minutes = $ongoingVisit->arrived_at->diffInMinutes($departedAt);
            $ongoingVisit->status = 'completed';
            $ongoingVisit->save();
        }
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     * Returns distance in kilometers
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Save locations to batches table for efficient storage
     * Groups points by duty session and 2-minute time windows
     */
    private function saveToBatches($riderId, $locations)
    {
        try {
            // Group locations by duty_session_id and 2-minute time windows
            $batches = [];

            foreach ($locations as $loc) {
                $timestamp = strtotime($loc['recorded_at']);
                $windowStart = floor($timestamp / 120) * 120; // 120 seconds = 2 minutes

                $key = $loc['duty_session_id'] . '_' . $windowStart;

                if (!isset($batches[$key])) {
                    $batches[$key] = [
                        'duty_session_id' => $loc['duty_session_id'],
                        'start_time' => $windowStart,
                        'end_time' => $windowStart + 120,
                        'points' => [],
                    ];
                }

                $batches[$key]['points'][] = $loc;
            }

            // Save each batch
            foreach ($batches as $batch) {
                // Compress GPS points to compact JSON format
                $compactPoints = array_map(function($loc) {
                    return [
                        'lat' => round($loc['latitude'], 6),  // 6 decimals = ~0.1m accuracy
                        'lng' => round($loc['longitude'], 6),
                        'acc' => isset($loc['accuracy']) ? round($loc['accuracy'], 1) : null,
                        'spd' => isset($loc['speed']) ? round($loc['speed'], 1) : null,
                        'ts' => substr($loc['recorded_at'], 11, 8), // HH:MM:SS only
                    ];
                }, $batch['points']);

                // Calculate statistics
                $stats = $this->calculateBatchStats($batch['points']);

                // Check if batch already exists (prevent duplicates)
                $existing = \DB::table('location_batches')
                    ->where('duty_session_id', $batch['duty_session_id'])
                    ->where('batch_start_time', date('Y-m-d H:i:s', $batch['start_time']))
                    ->first();

                if (!$existing) {
                    \DB::table('location_batches')->insert([
                        'rider_id' => $riderId,
                        'duty_session_id' => $batch['duty_session_id'],
                        'batch_start_time' => date('Y-m-d H:i:s', $batch['start_time']),
                        'batch_end_time' => date('Y-m-d H:i:s', $batch['end_time']),
                        'point_count' => count($batch['points']),
                        'total_distance_meters' => $stats['distance'],
                        'avg_speed' => $stats['avg_speed'],
                        'avg_accuracy' => $stats['avg_accuracy'],
                        'points' => json_encode($compactPoints),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            \Log::info("✅ [Batch Storage] Created " . count($batches) . " batches from " . count($locations) . " points");

        } catch (\Exception $e) {
            // Don't fail the whole request if batching fails
            \Log::error("❌ [Batch Storage] Failed to create batches: " . $e->getMessage());
        }
    }

    /**
     * Calculate statistics for a batch of GPS points
     */
    private function calculateBatchStats($points)
    {
        $totalDistance = 0;
        $totalSpeed = 0;
        $totalAccuracy = 0;
        $speedCount = 0;
        $accuracyCount = 0;

        // Calculate distance between consecutive points
        for ($i = 1; $i < count($points); $i++) {
            $totalDistance += $this->haversineDistance(
                $points[$i-1]['latitude'],
                $points[$i-1]['longitude'],
                $points[$i]['latitude'],
                $points[$i]['longitude']
            ) * 1000; // Convert km to meters
        }

        // Calculate averages
        foreach ($points as $point) {
            if (isset($point['speed']) && $point['speed'] !== null) {
                $totalSpeed += $point['speed'];
                $speedCount++;
            }
            if (isset($point['accuracy']) && $point['accuracy'] !== null) {
                $totalAccuracy += $point['accuracy'];
                $accuracyCount++;
            }
        }

        return [
            'distance' => round($totalDistance, 2),
            'avg_speed' => $speedCount > 0 ? round($totalSpeed / $speedCount, 2) : 0,
            'avg_accuracy' => $accuracyCount > 0 ? round($totalAccuracy / $accuracyCount, 2) : 0,
        ];
    }
}
