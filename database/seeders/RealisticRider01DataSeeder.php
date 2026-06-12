<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Generate realistic GPS tracking data for rider01 - June 12, 2026
 * Full day with all scenarios: stopped, slow, normal, fast, highway
 */
class RealisticRider01DataSeeder extends Seeder
{
    private $riderId = 1; // rider01
    private $baseDate = '2026-06-12';
    private $sessionId;

    public function run(): void
    {
        echo "🧹 Cleaning existing data for rider01...\n";
        $this->cleanExistingData();

        echo "🎬 Creating duty session for June 12, 2026...\n";
        $this->createDutySession();

        echo "📍 Generating realistic GPS data...\n\n";

        // Full day journey simulation
        $this->generateMorningCommute();      // 8:00 - 8:30 AM
        $this->generateFirstDeliveryStop();   // 8:30 - 8:45 AM
        $this->generateSlowTraffic();         // 8:45 - 9:00 AM
        $this->generateNormalDeliveries();    // 9:00 - 11:00 AM
        $this->generateLunchBreak();          // 11:00 - 11:30 AM
        $this->generateHighwaySegment();      // 11:30 - 12:00 PM
        $this->generateAfternoonDeliveries(); // 12:00 - 3:00 PM
        $this->generateFastMovement();        // 3:00 - 3:30 PM
        $this->generateEveningRush();         // 3:30 - 4:30 PM
        $this->generateReturnJourney();       // 4:30 - 5:00 PM

        $this->updateSessionStats();

        echo "\n✅ Realistic data generation complete!\n";
        echo "📊 Statistics:\n";
        echo "   - Total GPS points: " . DB::table('location_points')->where('rider_id', $this->riderId)->count() . "\n";
        echo "   - Time span: 8:00 AM - 5:00 PM (9 hours)\n";
        echo "   - Scenarios: All speed ranges covered\n";
        echo "   - View on: Web tracking dashboard & Mobile app\n";
    }

    private function cleanExistingData(): void
    {
        DB::table('location_points')->where('rider_id', $this->riderId)->delete();
        DB::table('duty_sessions')->where('rider_id', $this->riderId)->delete();
        echo "   ✓ Cleaned old data\n";
    }

    private function createDutySession(): void
    {
        $this->sessionId = DB::table('duty_sessions')->insertGetId([
            'rider_id' => $this->riderId,
            'started_at' => $this->baseDate . ' 08:00:00',
            'ended_at' => $this->baseDate . ' 17:00:00',
            'status' => 'completed',
            'total_duration_minutes' => 540, // 9 hours
            'total_distance_km' => 0, // Will calculate later
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   ✓ Created duty session (8:00 AM - 5:00 PM)\n";
    }

    // Morning commute: 8:00-8:30 AM (Normal speed, some stops)
    private function generateMorningCommute(): void
    {
        echo "🌅 Morning commute (8:00-8:30 AM)...\n";

        $route = [
            ['lat' => 23.8103, 'lng' => 90.4125, 'speed' => 0],    // Start point (stopped)
            ['lat' => 23.8110, 'lng' => 90.4130, 'speed' => 5],    // Slow start
            ['lat' => 23.8125, 'lng' => 90.4140, 'speed' => 8],    // Accelerating
            ['lat' => 23.8145, 'lng' => 90.4155, 'speed' => 12],   // Normal speed
            ['lat' => 23.8165, 'lng' => 90.4170, 'speed' => 15],
            ['lat' => 23.8180, 'lng' => 90.4180, 'speed' => 18],
            ['lat' => 23.8195, 'lng' => 90.4195, 'speed' => 0],    // Traffic light stop
            ['lat' => 23.8195, 'lng' => 90.4195, 'speed' => 0],    // Still stopped
            ['lat' => 23.8200, 'lng' => 90.4200, 'speed' => 6],    // Moving again
            ['lat' => 23.8215, 'lng' => 90.4210, 'speed' => 14],
            ['lat' => 23.8230, 'lng' => 90.4225, 'speed' => 16],
        ];

        $this->insertRoutePoints($route, '08:00:00', 3); // 3-second intervals
    }

    // First delivery stop: 8:30-8:45 AM (Stopped for delivery)
    private function generateFirstDeliveryStop(): void
    {
        echo "📦 First delivery stop (8:30-8:45 AM)...\n";

        // Stopped at delivery location for 15 minutes
        $lat = 23.8230;
        $lng = 90.4225;

        $startTime = Carbon::parse($this->baseDate . ' 08:30:00');
        for ($i = 0; $i < 30; $i++) { // 30 points over 15 minutes
            $this->insertGPSPoint(
                $lat + (rand(-5, 5) / 100000), // Small GPS drift
                $lng + (rand(-5, 5) / 100000),
                0, // Stopped
                $startTime->copy()->addSeconds($i * 30)->format('Y-m-d H:i:s')
            );
        }
    }

    // Slow traffic: 8:45-9:00 AM (Slow movement)
    private function generateSlowTraffic(): void
    {
        echo "🐌 Slow traffic area (8:45-9:00 AM)...\n";

        $route = [
            ['lat' => 23.8230, 'lng' => 90.4225, 'speed' => 3],
            ['lat' => 23.8235, 'lng' => 90.4230, 'speed' => 4],
            ['lat' => 23.8240, 'lng' => 90.4235, 'speed' => 2],
            ['lat' => 23.8245, 'lng' => 90.4240, 'speed' => 5],
            ['lat' => 23.8250, 'lng' => 90.4245, 'speed' => 6],
            ['lat' => 23.8255, 'lng' => 90.4250, 'speed' => 4],
            ['lat' => 23.8260, 'lng' => 90.4255, 'speed' => 3],
            ['lat' => 23.8265, 'lng' => 90.4260, 'speed' => 7],
        ];

        $this->insertRoutePoints($route, '08:45:00', 10); // 10-second intervals (slow)
    }

    // Normal deliveries: 9:00-11:00 AM (Mix of normal speed and stops)
    private function generateNormalDeliveries(): void
    {
        echo "🚗 Normal deliveries (9:00-11:00 AM)...\n";

        $route = [
            // Moving to delivery 2
            ['lat' => 23.8265, 'lng' => 90.4260, 'speed' => 12],
            ['lat' => 23.8280, 'lng' => 90.4275, 'speed' => 15],
            ['lat' => 23.8295, 'lng' => 90.4290, 'speed' => 18],
            ['lat' => 23.8310, 'lng' => 90.4305, 'speed' => 20],

            // Delivery stop 2
            ['lat' => 23.8310, 'lng' => 90.4305, 'speed' => 0],
            ['lat' => 23.8310, 'lng' => 90.4305, 'speed' => 0],
            ['lat' => 23.8310, 'lng' => 90.4305, 'speed' => 0],

            // Moving to delivery 3
            ['lat' => 23.8320, 'lng' => 90.4315, 'speed' => 14],
            ['lat' => 23.8335, 'lng' => 90.4325, 'speed' => 17],
            ['lat' => 23.8350, 'lng' => 90.4340, 'speed' => 19],
            ['lat' => 23.8365, 'lng' => 90.4355, 'speed' => 21],

            // Delivery stop 3
            ['lat' => 23.8365, 'lng' => 90.4355, 'speed' => 0],
            ['lat' => 23.8365, 'lng' => 90.4355, 'speed' => 0],

            // Continue pattern
            ['lat' => 23.8375, 'lng' => 90.4365, 'speed' => 16],
            ['lat' => 23.8390, 'lng' => 90.4380, 'speed' => 18],
            ['lat' => 23.8405, 'lng' => 90.4395, 'speed' => 22],
            ['lat' => 23.8420, 'lng' => 90.4410, 'speed' => 24],

            // Delivery stop 4
            ['lat' => 23.8420, 'lng' => 90.4410, 'speed' => 0],
            ['lat' => 23.8420, 'lng' => 90.4410, 'speed' => 0],
        ];

        $this->insertRoutePoints($route, '09:00:00', 5); // 5-second intervals
    }

    // Lunch break: 11:00-11:30 AM (Long stop)
    private function generateLunchBreak(): void
    {
        echo "🍽️ Lunch break (11:00-11:30 AM)...\n";

        $lat = 23.8420;
        $lng = 90.4410;

        $startTime = Carbon::parse($this->baseDate . ' 11:00:00');
        for ($i = 0; $i < 60; $i++) { // 60 points over 30 minutes
            $this->insertGPSPoint(
                $lat + (rand(-3, 3) / 100000),
                $lng + (rand(-3, 3) / 100000),
                0,
                $startTime->copy()->addSeconds($i * 30)->format('Y-m-d H:i:s')
            );
        }
    }

    // Highway segment: 11:30-12:00 PM (High speed)
    private function generateHighwaySegment(): void
    {
        echo "🏍️ Highway segment (11:30 AM-12:00 PM)...\n";

        $route = [
            ['lat' => 23.8420, 'lng' => 90.4410, 'speed' => 0],    // Start
            ['lat' => 23.8425, 'lng' => 90.4415, 'speed' => 15],   // Accelerating
            ['lat' => 23.8435, 'lng' => 90.4425, 'speed' => 30],
            ['lat' => 23.8450, 'lng' => 90.4440, 'speed' => 45],
            ['lat' => 23.8470, 'lng' => 90.4460, 'speed' => 55],   // Highway speed
            ['lat' => 23.8495, 'lng' => 90.4485, 'speed' => 62],
            ['lat' => 23.8520, 'lng' => 90.4510, 'speed' => 68],
            ['lat' => 23.8550, 'lng' => 90.4540, 'speed' => 72],   // Peak highway
            ['lat' => 23.8580, 'lng' => 90.4570, 'speed' => 70],
            ['lat' => 23.8610, 'lng' => 90.4600, 'speed' => 65],
            ['lat' => 23.8635, 'lng' => 90.4625, 'speed' => 58],
            ['lat' => 23.8655, 'lng' => 90.4645, 'speed' => 45],   // Slowing down
            ['lat' => 23.8670, 'lng' => 90.4660, 'speed' => 30],
            ['lat' => 23.8680, 'lng' => 90.4670, 'speed' => 15],
        ];

        $this->insertRoutePoints($route, '11:30:00', 2); // 2-second intervals (fast)
    }

    // Afternoon deliveries: 12:00-3:00 PM
    private function generateAfternoonDeliveries(): void
    {
        echo "📦 Afternoon deliveries (12:00-3:00 PM)...\n";

        $route = [
            ['lat' => 23.8680, 'lng' => 90.4670, 'speed' => 14],
            ['lat' => 23.8690, 'lng' => 90.4680, 'speed' => 18],
            ['lat' => 23.8700, 'lng' => 90.4690, 'speed' => 20],
            ['lat' => 23.8700, 'lng' => 90.4690, 'speed' => 0], // Stop
            ['lat' => 23.8700, 'lng' => 90.4690, 'speed' => 0],

            ['lat' => 23.8710, 'lng' => 90.4700, 'speed' => 16],
            ['lat' => 23.8720, 'lng' => 90.4710, 'speed' => 19],
            ['lat' => 23.8730, 'lng' => 90.4720, 'speed' => 22],
            ['lat' => 23.8730, 'lng' => 90.4720, 'speed' => 0], // Stop

            ['lat' => 23.8740, 'lng' => 90.4730, 'speed' => 15],
            ['lat' => 23.8750, 'lng' => 90.4740, 'speed' => 18],
            ['lat' => 23.8760, 'lng' => 90.4750, 'speed' => 21],
            ['lat' => 23.8770, 'lng' => 90.4760, 'speed' => 23],
            ['lat' => 23.8770, 'lng' => 90.4760, 'speed' => 0], // Stop
            ['lat' => 23.8770, 'lng' => 90.4760, 'speed' => 0],
        ];

        $this->insertRoutePoints($route, '12:00:00', 6);
    }

    // Fast movement: 3:00-3:30 PM
    private function generateFastMovement(): void
    {
        echo "⚡ Fast movement (3:00-3:30 PM)...\n";

        $route = [
            ['lat' => 23.8770, 'lng' => 90.4760, 'speed' => 10],
            ['lat' => 23.8780, 'lng' => 90.4770, 'speed' => 25],
            ['lat' => 23.8795, 'lng' => 90.4785, 'speed' => 38],
            ['lat' => 23.8815, 'lng' => 90.4805, 'speed' => 48],
            ['lat' => 23.8840, 'lng' => 90.4830, 'speed' => 52],
            ['lat' => 23.8865, 'lng' => 90.4855, 'speed' => 55],
            ['lat' => 23.8890, 'lng' => 90.4880, 'speed' => 50],
            ['lat' => 23.8910, 'lng' => 90.4900, 'speed' => 42],
            ['lat' => 23.8925, 'lng' => 90.4915, 'speed' => 35],
        ];

        $this->insertRoutePoints($route, '15:00:00', 3);
    }

    // Evening rush: 3:30-4:30 PM (Mix of slow and normal)
    private function generateEveningRush(): void
    {
        echo "🌆 Evening rush hour (3:30-4:30 PM)...\n";

        $route = [
            ['lat' => 23.8925, 'lng' => 90.4915, 'speed' => 8],
            ['lat' => 23.8930, 'lng' => 90.4920, 'speed' => 5],
            ['lat' => 23.8935, 'lng' => 90.4925, 'speed' => 3],
            ['lat' => 23.8935, 'lng' => 90.4925, 'speed' => 0],
            ['lat' => 23.8940, 'lng' => 90.4930, 'speed' => 4],
            ['lat' => 23.8945, 'lng' => 90.4935, 'speed' => 7],
            ['lat' => 23.8950, 'lng' => 90.4940, 'speed' => 10],
            ['lat' => 23.8955, 'lng' => 90.4945, 'speed' => 6],
            ['lat' => 23.8960, 'lng' => 90.4950, 'speed' => 4],
            ['lat' => 23.8960, 'lng' => 90.4950, 'speed' => 0],
            ['lat' => 23.8965, 'lng' => 90.4955, 'speed' => 8],
        ];

        $this->insertRoutePoints($route, '15:30:00', 8);
    }

    // Return journey: 4:30-5:00 PM
    private function generateReturnJourney(): void
    {
        echo "🏠 Return journey (4:30-5:00 PM)...\n";

        $route = [
            ['lat' => 23.8965, 'lng' => 90.4955, 'speed' => 16],
            ['lat' => 23.8950, 'lng' => 90.4940, 'speed' => 19],
            ['lat' => 23.8930, 'lng' => 90.4920, 'speed' => 22],
            ['lat' => 23.8910, 'lng' => 90.4900, 'speed' => 20],
            ['lat' => 23.8890, 'lng' => 90.4880, 'speed' => 18],
            ['lat' => 23.8870, 'lng' => 90.4860, 'speed' => 16],
            ['lat' => 23.8850, 'lng' => 90.4840, 'speed' => 14],
            ['lat' => 23.8830, 'lng' => 90.4820, 'speed' => 12],
            ['lat' => 23.8815, 'lng' => 90.4805, 'speed' => 8],
            ['lat' => 23.8810, 'lng' => 90.4800, 'speed' => 4],
            ['lat' => 23.8808, 'lng' => 90.4798, 'speed' => 0], // Arrived
        ];

        $this->insertRoutePoints($route, '16:30:00', 4);
    }

    private function insertRoutePoints(array $route, string $startTime, int $intervalSeconds): void
    {
        $currentTime = Carbon::parse($this->baseDate . ' ' . $startTime);

        foreach ($route as $point) {
            $this->insertGPSPoint(
                $point['lat'],
                $point['lng'],
                $point['speed'],
                $currentTime->format('Y-m-d H:i:s'),
                rand(5, 15) // Accuracy between 5-15m
            );
            $currentTime->addSeconds($intervalSeconds);
        }
    }

    private function insertGPSPoint(float $lat, float $lng, float $speed, string $timestamp, float $accuracy = 10): void
    {
        DB::table('location_points')->insert([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => $accuracy,
            'speed' => $speed / 3.6, // Convert km/h to m/s
            'bearing' => $this->calculateBearing($lat, $lng),
            'altitude' => rand(5, 25),
            'recorded_at' => $timestamp,
            'is_synced' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function calculateBearing(float $lat, float $lng): float
    {
        // Simple bearing calculation (would be more sophisticated in production)
        return rand(0, 359);
    }

    private function updateSessionStats(): void
    {
        // Calculate total distance
        $points = DB::table('location_points')
            ->where('duty_session_id', $this->sessionId)
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude']);

        $totalDistance = 0;
        for ($i = 1; $i < count($points); $i++) {
            $totalDistance += $this->haversineDistance(
                $points[$i-1]->latitude,
                $points[$i-1]->longitude,
                $points[$i]->latitude,
                $points[$i]->longitude
            );
        }

        DB::table('duty_sessions')
            ->where('id', $this->sessionId)
            ->update([
                'total_distance_km' => round($totalDistance / 1000, 2),
                'updated_at' => now(),
            ]);
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
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
}
