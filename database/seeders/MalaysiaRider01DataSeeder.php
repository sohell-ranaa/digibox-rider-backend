<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Generate realistic GPS tracking data for rider01 - TODAY (Malaysia - Kuala Lumpur)
 * Full day with all scenarios: stopped, slow, normal, fast, highway
 */
class MalaysiaRider01DataSeeder extends Seeder
{
    private $riderId = 1; // rider01
    private $baseDate;
    private $sessionId;

    public function __construct()
    {
        // Use TODAY's date
        $this->baseDate = Carbon::now()->format('Y-m-d');
    }

    public function run(): void
    {
        echo "🧹 Cleaning ALL existing data...\n";
        $this->cleanAllData();

        echo "🇲🇾 Creating duty session for TODAY ({$this->baseDate}) - Kuala Lumpur, Malaysia...\n";
        $this->createDutySession();

        echo "📍 Generating realistic GPS data for Malaysia...\n\n";

        // Full day journey simulation (Malaysia coordinates)
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

        echo "\n✅ Malaysia data generation complete!\n";
        echo "📊 Statistics:\n";
        echo "   - Date: {$this->baseDate} (TODAY)\n";
        echo "   - Location: Kuala Lumpur, Malaysia\n";
        echo "   - Total GPS points: " . DB::table('location_points')->where('rider_id', $this->riderId)->count() . "\n";
        echo "   - Time span: 8:00 AM - 5:00 PM (9 hours)\n";
        echo "   - Scenarios: All speed ranges covered\n";
    }

    private function cleanAllData(): void
    {
        // Delete in correct order to respect foreign keys
        DB::table('location_points')->delete();
        DB::table('stop_records')->delete();
        DB::table('installation_visits')->delete();
        DB::table('duty_sessions')->delete();
        echo "   ✓ Cleaned all GPS and session data\n";
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

    // MALAYSIA COORDINATES - Simfoni Tower, Kuala Lumpur
    // Base: 3.1480°N, 101.6990°E (Simfoni Tower location)

    private function generateMorningCommute(): void
    {
        echo "🌅 Morning commute (8:00-8:30 AM) - Starting from Simfoni Tower...\n";

        $route = [
            ['lat' => 3.1480, 'lng' => 101.6990, 'speed' => 0],    // Start (Simfoni Tower)
            ['lat' => 3.1485, 'lng' => 101.6995, 'speed' => 5],
            ['lat' => 3.1495, 'lng' => 101.7005, 'speed' => 8],
            ['lat' => 3.1510, 'lng' => 101.7020, 'speed' => 12],
            ['lat' => 3.1525, 'lng' => 101.7035, 'speed' => 15],
            ['lat' => 3.1540, 'lng' => 101.7050, 'speed' => 18],
            ['lat' => 3.1555, 'lng' => 101.7065, 'speed' => 0],    // Traffic light
            ['lat' => 3.1555, 'lng' => 101.7065, 'speed' => 0],
            ['lat' => 3.1560, 'lng' => 101.7070, 'speed' => 6],
            ['lat' => 3.1575, 'lng' => 101.7085, 'speed' => 14],
            ['lat' => 3.1590, 'lng' => 101.7100, 'speed' => 16],
        ];

        $this->insertRoutePoints($route, '08:00:00', 3);
    }

    private function generateFirstDeliveryStop(): void
    {
        echo "📦 First delivery stop (8:30-8:45 AM)...\n";

        $lat = 3.1500;
        $lng = 101.6980;

        $startTime = Carbon::parse($this->baseDate . ' 08:30:00');
        for ($i = 0; $i < 30; $i++) {
            $this->insertGPSPoint(
                $lat + (rand(-5, 5) / 100000),
                $lng + (rand(-5, 5) / 100000),
                0,
                $startTime->copy()->addSeconds($i * 30)->format('Y-m-d H:i:s')
            );
        }
    }

    private function generateSlowTraffic(): void
    {
        echo "🐌 Slow traffic (8:45-9:00 AM)...\n";

        $route = [
            ['lat' => 3.1500, 'lng' => 101.6980, 'speed' => 3],
            ['lat' => 3.1505, 'lng' => 101.6985, 'speed' => 4],
            ['lat' => 3.1510, 'lng' => 101.6990, 'speed' => 2],
            ['lat' => 3.1515, 'lng' => 101.6995, 'speed' => 5],
            ['lat' => 3.1520, 'lng' => 101.7000, 'speed' => 6],
            ['lat' => 3.1525, 'lng' => 101.7005, 'speed' => 4],
            ['lat' => 3.1530, 'lng' => 101.7010, 'speed' => 3],
            ['lat' => 3.1535, 'lng' => 101.7015, 'speed' => 7],
        ];

        $this->insertRoutePoints($route, '08:45:00', 10);
    }

    private function generateNormalDeliveries(): void
    {
        echo "🚗 Normal deliveries (9:00-11:00 AM)...\n";

        $route = [
            ['lat' => 3.1535, 'lng' => 101.7015, 'speed' => 12],
            ['lat' => 3.1550, 'lng' => 101.7030, 'speed' => 15],
            ['lat' => 3.1565, 'lng' => 101.7045, 'speed' => 18],
            ['lat' => 3.1580, 'lng' => 101.7060, 'speed' => 20],

            ['lat' => 3.1580, 'lng' => 101.7060, 'speed' => 0], // Stop
            ['lat' => 3.1580, 'lng' => 101.7060, 'speed' => 0],
            ['lat' => 3.1580, 'lng' => 101.7060, 'speed' => 0],

            ['lat' => 3.1590, 'lng' => 101.7070, 'speed' => 14],
            ['lat' => 3.1605, 'lng' => 101.7085, 'speed' => 17],
            ['lat' => 3.1620, 'lng' => 101.7100, 'speed' => 19],
            ['lat' => 3.1635, 'lng' => 101.7115, 'speed' => 21],

            ['lat' => 3.1635, 'lng' => 101.7115, 'speed' => 0], // Stop
            ['lat' => 3.1635, 'lng' => 101.7115, 'speed' => 0],

            ['lat' => 3.1645, 'lng' => 101.7125, 'speed' => 16],
            ['lat' => 3.1660, 'lng' => 101.7140, 'speed' => 18],
            ['lat' => 3.1675, 'lng' => 101.7155, 'speed' => 22],
            ['lat' => 3.1690, 'lng' => 101.7170, 'speed' => 24],

            ['lat' => 3.1690, 'lng' => 101.7170, 'speed' => 0], // Stop
            ['lat' => 3.1690, 'lng' => 101.7170, 'speed' => 0],
        ];

        $this->insertRoutePoints($route, '09:00:00', 5);
    }

    private function generateLunchBreak(): void
    {
        echo "🍽️ Lunch break (11:00-11:30 AM)...\n";

        $lat = 3.1690;
        $lng = 101.7170;

        $startTime = Carbon::parse($this->baseDate . ' 11:00:00');
        for ($i = 0; $i < 60; $i++) {
            $this->insertGPSPoint(
                $lat + (rand(-3, 3) / 100000),
                $lng + (rand(-3, 3) / 100000),
                0,
                $startTime->copy()->addSeconds($i * 30)->format('Y-m-d H:i:s')
            );
        }
    }

    private function generateHighwaySegment(): void
    {
        echo "🏍️ Highway segment (11:30 AM-12:00 PM) - North-South Expressway...\n";

        $route = [
            ['lat' => 3.1690, 'lng' => 101.7170, 'speed' => 0],
            ['lat' => 3.1695, 'lng' => 101.7175, 'speed' => 15],
            ['lat' => 3.1705, 'lng' => 101.7185, 'speed' => 30],
            ['lat' => 3.1720, 'lng' => 101.7200, 'speed' => 45],
            ['lat' => 3.1740, 'lng' => 101.7220, 'speed' => 55],
            ['lat' => 3.1765, 'lng' => 101.7245, 'speed' => 62],
            ['lat' => 3.1790, 'lng' => 101.7270, 'speed' => 68],
            ['lat' => 3.1820, 'lng' => 101.7300, 'speed' => 72],
            ['lat' => 3.1850, 'lng' => 101.7330, 'speed' => 70],
            ['lat' => 3.1880, 'lng' => 101.7360, 'speed' => 65],
            ['lat' => 3.1905, 'lng' => 101.7385, 'speed' => 58],
            ['lat' => 3.1925, 'lng' => 101.7405, 'speed' => 45],
            ['lat' => 3.1940, 'lng' => 101.7420, 'speed' => 30],
            ['lat' => 3.1950, 'lng' => 101.7430, 'speed' => 15],
        ];

        $this->insertRoutePoints($route, '11:30:00', 2);
    }

    private function generateAfternoonDeliveries(): void
    {
        echo "📦 Afternoon deliveries (12:00-3:00 PM)...\n";

        $route = [
            ['lat' => 3.1950, 'lng' => 101.7430, 'speed' => 14],
            ['lat' => 3.1960, 'lng' => 101.7440, 'speed' => 18],
            ['lat' => 3.1970, 'lng' => 101.7450, 'speed' => 20],
            ['lat' => 3.1970, 'lng' => 101.7450, 'speed' => 0],
            ['lat' => 3.1970, 'lng' => 101.7450, 'speed' => 0],

            ['lat' => 3.1980, 'lng' => 101.7460, 'speed' => 16],
            ['lat' => 3.1990, 'lng' => 101.7470, 'speed' => 19],
            ['lat' => 3.2000, 'lng' => 101.7480, 'speed' => 22],
            ['lat' => 3.2000, 'lng' => 101.7480, 'speed' => 0],

            ['lat' => 3.2010, 'lng' => 101.7490, 'speed' => 15],
            ['lat' => 3.2020, 'lng' => 101.7500, 'speed' => 18],
            ['lat' => 3.2030, 'lng' => 101.7510, 'speed' => 21],
            ['lat' => 3.2040, 'lng' => 101.7520, 'speed' => 23],
            ['lat' => 3.2040, 'lng' => 101.7520, 'speed' => 0],
            ['lat' => 3.2040, 'lng' => 101.7520, 'speed' => 0],
        ];

        $this->insertRoutePoints($route, '12:00:00', 6);
    }

    private function generateFastMovement(): void
    {
        echo "⚡ Fast movement (3:00-3:30 PM)...\n";

        $route = [
            ['lat' => 3.2040, 'lng' => 101.7520, 'speed' => 10],
            ['lat' => 3.2050, 'lng' => 101.7530, 'speed' => 25],
            ['lat' => 3.2065, 'lng' => 101.7545, 'speed' => 38],
            ['lat' => 3.2085, 'lng' => 101.7565, 'speed' => 48],
            ['lat' => 3.2110, 'lng' => 101.7590, 'speed' => 52],
            ['lat' => 3.2135, 'lng' => 101.7615, 'speed' => 55],
            ['lat' => 3.2160, 'lng' => 101.7640, 'speed' => 50],
            ['lat' => 3.2180, 'lng' => 101.7660, 'speed' => 42],
            ['lat' => 3.2195, 'lng' => 101.7675, 'speed' => 35],
        ];

        $this->insertRoutePoints($route, '15:00:00', 3);
    }

    private function generateEveningRush(): void
    {
        echo "🌆 Evening rush (3:30-4:30 PM)...\n";

        $route = [
            ['lat' => 3.2195, 'lng' => 101.7675, 'speed' => 8],
            ['lat' => 3.2200, 'lng' => 101.7680, 'speed' => 5],
            ['lat' => 3.2205, 'lng' => 101.7685, 'speed' => 3],
            ['lat' => 3.2205, 'lng' => 101.7685, 'speed' => 0],
            ['lat' => 3.2210, 'lng' => 101.7690, 'speed' => 4],
            ['lat' => 3.2215, 'lng' => 101.7695, 'speed' => 7],
            ['lat' => 3.2220, 'lng' => 101.7700, 'speed' => 10],
            ['lat' => 3.2225, 'lng' => 101.7705, 'speed' => 6],
            ['lat' => 3.2230, 'lng' => 101.7710, 'speed' => 4],
            ['lat' => 3.2230, 'lng' => 101.7710, 'speed' => 0],
            ['lat' => 3.2235, 'lng' => 101.7715, 'speed' => 8],
        ];

        $this->insertRoutePoints($route, '15:30:00', 8);
    }

    private function generateReturnJourney(): void
    {
        echo "🏠 Return journey (4:30-5:00 PM)...\n";

        $route = [
            ['lat' => 3.2235, 'lng' => 101.7715, 'speed' => 16],
            ['lat' => 3.2220, 'lng' => 101.7700, 'speed' => 19],
            ['lat' => 3.2200, 'lng' => 101.7680, 'speed' => 22],
            ['lat' => 3.2180, 'lng' => 101.7660, 'speed' => 20],
            ['lat' => 3.2160, 'lng' => 101.7640, 'speed' => 18],
            ['lat' => 3.2140, 'lng' => 101.7620, 'speed' => 16],
            ['lat' => 3.2120, 'lng' => 101.7600, 'speed' => 14],
            ['lat' => 3.2100, 'lng' => 101.7580, 'speed' => 12],
            ['lat' => 3.2085, 'lng' => 101.7565, 'speed' => 8],
            ['lat' => 3.2080, 'lng' => 101.7560, 'speed' => 4],
            ['lat' => 3.2078, 'lng' => 101.7558, 'speed' => 0],
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
                rand(5, 15)
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
            'bearing' => rand(0, 359),
            'altitude' => rand(20, 60),
            'recorded_at' => $timestamp,
            'is_synced' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function updateSessionStats(): void
    {
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
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
