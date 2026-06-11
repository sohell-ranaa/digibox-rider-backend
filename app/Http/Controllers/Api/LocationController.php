<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationPoint;
use App\Models\InstallationLocation;
use App\Models\InstallationVisit;
use App\Models\DutySession;
use Illuminate\Http\Request;

class LocationController extends Controller
{
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

        // Create location point
        $location = LocationPoint::create([
            'rider_id' => $rider->id,
            'duty_session_id' => $validated['duty_session_id'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
            'speed' => $validated['speed'] ?? null,
            'bearing' => $validated['bearing'] ?? null,
            'altitude' => $validated['altitude'] ?? null,
            'recorded_at' => $validated['recorded_at'],
            'is_synced' => true,
        ]);

        // Check for installation visits (geofencing)
        $this->checkInstallationVisits($rider->id, $validated['duty_session_id'], $location);

        return response()->json([
            'message' => 'Location recorded successfully',
            'id' => $location->id,
        ], 201);
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'locations' => 'required|array',
            'locations.*.duty_session_id' => 'required|exists:duty_sessions,id',
            'locations.*.latitude' => 'required|numeric|between:-90,90',
            'locations.*.longitude' => 'required|numeric|between:-180,180',
            'locations.*.accuracy' => 'nullable|numeric',
            'locations.*.speed' => 'nullable|numeric',
            'locations.*.bearing' => 'nullable|numeric',
            'locations.*.altitude' => 'nullable|numeric',
            'locations.*.recorded_at' => 'required|date',
        ]);

        $rider = $request->user();
        $inserted = 0;

        foreach ($validated['locations'] as $loc) {
            $location = LocationPoint::create([
                'rider_id' => $rider->id,
                'duty_session_id' => $loc['duty_session_id'],
                'latitude' => $loc['latitude'],
                'longitude' => $loc['longitude'],
                'accuracy' => $loc['accuracy'] ?? null,
                'speed' => $loc['speed'] ?? null,
                'bearing' => $loc['bearing'] ?? null,
                'altitude' => $loc['altitude'] ?? null,
                'recorded_at' => $loc['recorded_at'],
                'is_synced' => true,
            ]);

            // Check installation visits for each location
            $this->checkInstallationVisits($rider->id, $loc['duty_session_id'], $location);
            $inserted++;
        }

        return response()->json([
            'message' => "$inserted locations recorded successfully",
        ], 201);
    }

    public function myLatest(Request $request)
    {
        $limit = $request->get('limit', 50);
        $rider = $request->user();

        $locations = LocationPoint::where('rider_id', $rider->id)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();

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
     * Check if rider is at any installation location (geofencing)
     */
    private function checkInstallationVisits($riderId, $dutySessionId, $location)
    {
        $installations = InstallationLocation::where('is_active', true)->get();

        foreach ($installations as $installation) {
            $distance = $this->haversineDistance(
                $location->latitude,
                $location->longitude,
                $installation->latitude,
                $installation->longitude
            );

            // Convert meters to meters (haversine returns km)
            $distanceMeters = $distance * 1000;

            // Check if rider is within geofence
            if ($distanceMeters <= $installation->geofence_radius_meters) {
                // Rider is at installation
                $this->handleInstallationEntry($riderId, $dutySessionId, $installation->id, $location);
            } else {
                // Rider left installation (if there was an ongoing visit)
                $this->handleInstallationExit($riderId, $installation->id, $location);
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
}
