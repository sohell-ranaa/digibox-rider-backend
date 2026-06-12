<?php

namespace App\Services;

/**
 * Kalman Filter for GPS data smoothing (PHP Implementation)
 * Reduces GPS noise and provides more accurate location estimates
 *
 * The Kalman filter works by:
 * 1. Predicting the next state based on previous velocity
 * 2. Measuring the actual GPS reading
 * 3. Combining prediction and measurement with optimal weights
 * 4. Continuously updating the estimate
 */
class KalmanGPSFilter
{
    // State variables
    private float $latitude = 0.0;
    private float $longitude = 0.0;
    private float $velocityLat = 0.0;
    private float $velocityLng = 0.0;

    // Error covariance (simplified 2D case)
    private float $errorLat = 1000.0;
    private float $errorLng = 1000.0;

    // Process noise (how much we trust the prediction model)
    private const PROCESS_NOISE = 0.5;

    // Measurement noise (GPS accuracy uncertainty)
    private float $measurementNoise = 10.0;

    // Time of last update
    private ?int $lastUpdateTime = null;

    // Has the filter been initialized?
    private bool $isInitialized = false;

    /**
     * Process a new GPS position through the Kalman filter
     * Returns smoothed position with reduced noise
     *
     * @param array $measurement ['latitude' => float, 'longitude' => float, 'accuracy' => float, 'timestamp' => int]
     * @return array Filtered position
     */
    public function filter(array $measurement): array
    {
        $now = time();

        // First measurement - initialize filter
        if (!$this->isInitialized) {
            $this->latitude = $measurement['latitude'];
            $this->longitude = $measurement['longitude'];
            $this->velocityLat = 0.0;
            $this->velocityLng = 0.0;
            $this->errorLat = $measurement['accuracy'] ?? 10.0;
            $this->errorLng = $measurement['accuracy'] ?? 10.0;
            $this->measurementNoise = $measurement['accuracy'] ?? 10.0;
            $this->lastUpdateTime = $now;
            $this->isInitialized = true;

            return $measurement; // Return original first measurement
        }

        // Calculate time delta
        $dt = $this->lastUpdateTime !== null
            ? $now - $this->lastUpdateTime
            : 1.0;

        // Clamp dt to reasonable values (0.1s to 60s)
        $deltaTime = max(0.1, min(60.0, $dt));

        // === PREDICTION STEP ===
        // Predict next state based on velocity
        $predictedLat = $this->latitude + ($this->velocityLat * $deltaTime);
        $predictedLng = $this->longitude + ($this->velocityLng * $deltaTime);

        // Predict error covariance (uncertainty grows over time)
        $predictedErrorLat = $this->errorLat + self::PROCESS_NOISE;
        $predictedErrorLng = $this->errorLng + self::PROCESS_NOISE;

        // === UPDATE STEP ===
        // Update measurement noise based on GPS accuracy
        $this->measurementNoise = max(5.0, min(50.0, $measurement['accuracy'] ?? 10.0));

        // Calculate Kalman gain (0 = trust prediction, 1 = trust measurement)
        $kalmanGainLat = $predictedErrorLat / ($predictedErrorLat + $this->measurementNoise);
        $kalmanGainLng = $predictedErrorLng / ($predictedErrorLng + $this->measurementNoise);

        // Update state estimate by blending prediction and measurement
        $this->latitude = $predictedLat + $kalmanGainLat * ($measurement['latitude'] - $predictedLat);
        $this->longitude = $predictedLng + $kalmanGainLng * ($measurement['longitude'] - $predictedLng);

        // Update velocity estimate
        $measuredVelocityLat = ($measurement['latitude'] - $predictedLat) / $deltaTime;
        $measuredVelocityLng = ($measurement['longitude'] - $predictedLng) / $deltaTime;

        $this->velocityLat = $this->velocityLat + $kalmanGainLat * ($measuredVelocityLat - $this->velocityLat);
        $this->velocityLng = $this->velocityLng + $kalmanGainLng * ($measuredVelocityLng - $this->velocityLng);

        // Update error covariance
        $this->errorLat = (1 - $kalmanGainLat) * $predictedErrorLat;
        $this->errorLng = (1 - $kalmanGainLng) * $predictedErrorLng;

        // Store update time
        $this->lastUpdateTime = $now;

        // Return smoothed position
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => sqrt($this->errorLat * $this->errorLat + $this->errorLng * $this->errorLng),
            'timestamp' => $measurement['timestamp'] ?? $now,
        ];
    }

    /**
     * Reset the filter (useful when starting new session)
     */
    public function reset(): void
    {
        $this->latitude = 0.0;
        $this->longitude = 0.0;
        $this->velocityLat = 0.0;
        $this->velocityLng = 0.0;
        $this->errorLat = 1000.0;
        $this->errorLng = 1000.0;
        $this->lastUpdateTime = null;
        $this->isInitialized = false;
    }

    /**
     * Get current filtered position estimate
     */
    public function getCurrentEstimate(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'velocityLat' => $this->velocityLat,
            'velocityLng' => $this->velocityLng,
            'errorLat' => $this->errorLat,
            'errorLng' => $this->errorLng,
        ];
    }

    /**
     * Check if filter is initialized
     */
    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }
}
