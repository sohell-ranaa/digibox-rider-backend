<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * AI-powered route processor (PHP Implementation)
 * Provides intelligent GPS processing with:
 * - Activity detection (stopped, slow, normal, fast, highway)
 * - Outlier detection and removal
 * - Douglas-Peucker path simplification
 * - Speed-based segmentation
 */
class AIRouteProcessor
{
    // Activity thresholds (speed in km/h)
    private const STOPPED_THRESHOLD = 2.0;
    private const SLOW_THRESHOLD = 10.0;
    private const NORMAL_THRESHOLD = 30.0;
    private const FAST_THRESHOLD = 60.0;

    // Outlier detection parameters
    private const MAX_ACCELERATION_MPS2 = 5.0; // Max acceleration 5 m/s²
    private const MAX_JUMP_DISTANCE_METERS = 500.0; // Max sudden jump in position

    // Douglas-Peucker simplification tolerance (meters)
    // Reduced from 5.0 to 2.0 for better route visualization on web
    private const SIMPLIFICATION_TOLERANCE = 2.0;

    /**
     * Detect activity type based on speed
     *
     * @param float $speedMps Speed in meters per second
     * @return string Activity type
     */
    public static function detectActivity(float $speedMps): string
    {
        $speedKmh = $speedMps * 3.6;

        if ($speedKmh < self::STOPPED_THRESHOLD) return 'stopped';
        if ($speedKmh < self::SLOW_THRESHOLD) return 'slow';
        if ($speedKmh < self::NORMAL_THRESHOLD) return 'normal';
        if ($speedKmh < self::FAST_THRESHOLD) return 'fast';
        return 'highway';
    }

    /**
     * Calculate distance between two GPS points using Haversine formula
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in meters
     */
    private static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
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
     * Detect if a point is an outlier
     *
     * @param array $points All points
     * @param int $index Index of point to check
     * @return bool True if outlier
     */
    public static function isOutlier(array $points, int $index): bool
    {
        if ($index < 1 || $index >= count($points) - 1) {
            return false;
        }

        $current = $points[$index];
        $previous = $points[$index - 1];
        $next = $points[$index + 1] ?? null;

        // Check distance jump from previous
        $distFromPrev = self::haversineDistance(
            $previous['latitude'],
            $previous['longitude'],
            $current['latitude'],
            $current['longitude']
        );

        if ($distFromPrev > self::MAX_JUMP_DISTANCE_METERS) {
            Log::warning("Outlier detected: Jump of {$distFromPrev}m from previous point");
            return true;
        }

        // Check if point deviates significantly from trajectory (if we have next point)
        if ($next !== null) {
            $distToNext = self::haversineDistance(
                $current['latitude'],
                $current['longitude'],
                $next['latitude'],
                $next['longitude']
            );

            // If distance to next is also very large, likely an outlier
            if ($distToNext > self::MAX_JUMP_DISTANCE_METERS) {
                return true;
            }

            // Calculate perpendicular distance from line (previous -> next)
            $perpendicularDist = self::perpendicularDistance(
                $current['latitude'],
                $current['longitude'],
                $previous['latitude'],
                $previous['longitude'],
                $next['latitude'],
                $next['longitude']
            );

            // If point is far from the trajectory, it's an outlier
            if ($perpendicularDist > 50.0) {
                Log::warning("Outlier detected: {$perpendicularDist}m deviation from trajectory");
                return true;
            }
        }

        return false;
    }

    /**
     * Remove outliers from a list of positions
     *
     * @param array $positions
     * @return array Filtered positions
     */
    public static function removeOutliers(array $positions): array
    {
        if (count($positions) < 3) {
            return $positions;
        }

        $filtered = [];

        for ($i = 0; $i < count($positions); $i++) {
            if (!self::isOutlier($positions, $i)) {
                $filtered[] = $positions[$i];
            }
        }

        $removed = count($positions) - count($filtered);
        Log::info("Outlier removal: " . count($positions) . " → " . count($filtered) . " points ($removed removed)");

        return $filtered;
    }

    /**
     * Simplify path using Douglas-Peucker algorithm
     *
     * @param array $positions
     * @param float|null $tolerance
     * @return array Simplified positions
     */
    public static function simplifyPath(array $positions, ?float $tolerance = null): array
    {
        if (count($positions) < 3) {
            return $positions;
        }

        $actualTolerance = $tolerance ?? self::SIMPLIFICATION_TOLERANCE;

        $simplified = self::douglasPeucker($positions, 0, count($positions) - 1, $actualTolerance);

        // Always include first and last points
        if (!in_array($positions[0], $simplified)) {
            array_unshift($simplified, $positions[0]);
        }
        if (!in_array($positions[count($positions) - 1], $simplified)) {
            $simplified[] = $positions[count($positions) - 1];
        }

        $reduction = (1 - count($simplified) / count($positions)) * 100;
        Log::info("Douglas-Peucker: " . count($positions) . " → " . count($simplified) . " points (" . number_format($reduction, 1) . "% reduction)");

        return $simplified;
    }

    /**
     * Douglas-Peucker recursive algorithm
     */
    private static function douglasPeucker(array $positions, int $startIndex, int $endIndex, float $tolerance): array
    {
        $maxDistance = 0.0;
        $maxIndex = 0;

        // Find point with maximum distance from line
        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            $distance = self::perpendicularDistance(
                $positions[$i]['latitude'],
                $positions[$i]['longitude'],
                $positions[$startIndex]['latitude'],
                $positions[$startIndex]['longitude'],
                $positions[$endIndex]['latitude'],
                $positions[$endIndex]['longitude']
            );

            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $maxIndex = $i;
            }
        }

        // If max distance is greater than tolerance, recursively simplify
        if ($maxDistance > $tolerance) {
            // Recursive call for both segments
            $left = self::douglasPeucker($positions, $startIndex, $maxIndex, $tolerance);
            $right = self::douglasPeucker($positions, $maxIndex, $endIndex, $tolerance);

            // Combine results (remove duplicate middle point)
            return array_merge($left, array_slice($right, 1));
        } else {
            // Base case: just return endpoints
            return [$positions[$startIndex], $positions[$endIndex]];
        }
    }

    /**
     * Calculate perpendicular distance from point to line
     */
    private static function perpendicularDistance(
        float $pointLat,
        float $pointLng,
        float $line1Lat,
        float $line1Lng,
        float $line2Lat,
        float $line2Lng
    ): float {
        // Convert to radians
        $lat1 = deg2rad($line1Lat);
        $lng1 = deg2rad($line1Lng);
        $lat2 = deg2rad($line2Lat);
        $lng2 = deg2rad($line2Lng);
        $latP = deg2rad($pointLat);
        $lngP = deg2rad($pointLng);

        // Earth radius in meters
        $R = 6371000.0;

        // Convert to 3D cartesian coordinates
        $x1 = $R * cos($lat1) * cos($lng1);
        $y1 = $R * cos($lat1) * sin($lng1);
        $z1 = $R * sin($lat1);

        $x2 = $R * cos($lat2) * cos($lng2);
        $y2 = $R * cos($lat2) * sin($lng2);
        $z2 = $R * sin($lat2);

        $xP = $R * cos($latP) * cos($lngP);
        $yP = $R * cos($latP) * sin($lngP);
        $zP = $R * sin($latP);

        // Vector from line1 to line2
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $dz = $z2 - $z1;

        // Vector from line1 to point
        $dxP = $xP - $x1;
        $dyP = $yP - $y1;
        $dzP = $zP - $z1;

        // Cross product
        $crossX = $dyP * $dz - $dzP * $dy;
        $crossY = $dzP * $dx - $dxP * $dz;
        $crossZ = $dxP * $dy - $dyP * $dx;

        // Magnitude of cross product
        $crossMag = sqrt($crossX * $crossX + $crossY * $crossY + $crossZ * $crossZ);

        // Magnitude of line vector
        $lineMag = sqrt($dx * $dx + $dy * $dy + $dz * $dz);

        // Perpendicular distance
        return $lineMag > 0 ? $crossMag / $lineMag : 0.0;
    }

    /**
     * Process raw GPS data through full AI pipeline
     *
     * @param array $rawPositions
     * @return array Processed route data
     */
    public static function processRoute(array $rawPositions): array
    {
        Log::info("🤖 Starting AI route processing: " . count($rawPositions) . " raw points");

        // Step 1: Remove outliers
        $cleanedPositions = self::removeOutliers($rawPositions);

        // Step 2: Simplify path (only if we have MANY points to avoid over-compression)
        // Changed threshold from 50 to 150 to preserve more route detail
        $simplifiedPositions = count($cleanedPositions) > 150
            ? self::simplifyPath($cleanedPositions)
            : $cleanedPositions;

        // Calculate statistics
        $totalDistance = 0.0;
        for ($i = 1; $i < count($simplifiedPositions); $i++) {
            $totalDistance += self::haversineDistance(
                $simplifiedPositions[$i - 1]['latitude'],
                $simplifiedPositions[$i - 1]['longitude'],
                $simplifiedPositions[$i]['latitude'],
                $simplifiedPositions[$i]['longitude']
            );
        }

        $compressionRatio = count($simplifiedPositions) / count($rawPositions);

        Log::info("🤖 AI processing complete: " . count($simplifiedPositions) . " optimized points, " .
                 number_format($totalDistance / 1000, 2) . " km, " .
                 number_format((1 - $compressionRatio) * 100, 1) . "% compression");

        return [
            'originalPoints' => $rawPositions,
            'processedPoints' => $simplifiedPositions,
            'totalDistanceKm' => $totalDistance / 1000,
            'compressionRatio' => $compressionRatio,
            'pointsRemoved' => count($rawPositions) - count($simplifiedPositions),
        ];
    }
}
