<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rider;
use App\Models\DutySession;
use App\Models\LocationPoint;
use App\Models\InstallationLocation;
use App\Models\InstallationVisit;
use App\Models\StopRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates GPS data following REAL roads in Kuala Lumpur
 * Routes follow actual streets: Jalan Ampang, Jalan Tun Razak, etc.
 */
class RealKLRoadsSeeder extends Seeder
{
    private $riderId = 1;
    private $sessionId;
    private $currentTime;

    public function run(): void
    {
        echo "🧹 Cleaning existing data...\n";
        $this->cleanAllData();

        echo "📍 Creating installations at real locations...\n";
        $this->createRealInstallations();

        echo "👤 Creating duty session...\n";
        $this->createDutySession();

        echo "🗺️  Generating route following REAL KL roads...\n";
        $this->generateRealRoute();

        $total = LocationPoint::where('duty_session_id', $this->sessionId)->count();
        $visits = InstallationVisit::where('duty_session_id', $this->sessionId)->count();
        $stops = StopRecord::where('duty_session_id', $this->sessionId)->count();

        echo "✅ Generated {$total} GPS points following real roads!\n";
        echo "✅ Created {$visits} installation visits\n";
        echo "✅ Created {$stops} stop records\n";
    }

    private function cleanAllData(): void
    {
        DB::table('location_points')->delete();
        DB::table('stop_records')->delete();
        DB::table('installation_visits')->delete();
        DB::table('duty_sessions')->delete();
        DB::table('installation_locations')->where('latitude', '>', 3.14)->delete();
        echo "✅ Data cleaned\n";
    }

    private function createRealInstallations(): void
    {
        // Real locations in KL near major roads
        $installations = [
            [
                'name' => 'Pavilion KL - POS System',
                'address' => 'Jalan Bukit Bintang, Bukit Bintang, 55100 Kuala Lumpur',
                'latitude' => 3.1497,
                'longitude' => 101.7144,
                'geofence_radius_meters' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Suria KLCC - Digital Signage',
                'address' => 'Jalan Ampang, Kuala Lumpur City Centre, 50088 Kuala Lumpur',
                'latitude' => 3.1578,
                'longitude' => 101.7117,
                'geofence_radius_meters' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Ampang Park - Network Setup',
                'address' => 'Jalan Ampang, Kampung Datuk Keramat, 50450 Kuala Lumpur',
                'latitude' => 3.1600,
                'longitude' => 101.7165,
                'geofence_radius_meters' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Avenue K - Server Maintenance',
                'address' => '156, Jalan Ampang, Kuala Lumpur, 50450 Kuala Lumpur',
                'latitude' => 3.1583,
                'longitude' => 101.7145,
                'geofence_radius_meters' => 100,
                'is_active' => true,
            ],
        ];

        foreach ($installations as $data) {
            InstallationLocation::create($data);
        }

        echo "✅ Created " . count($installations) . " installations\n";
    }

    private function createDutySession(): void
    {
        $session = DutySession::create([
            'rider_id' => $this->riderId,
            'started_at' => Carbon::today()->setTime(8, 0, 0),
            'ended_at' => Carbon::today()->setTime(17, 0, 0),
            'total_duration_minutes' => 540,
            'status' => 'completed',
        ]);

        $this->sessionId = $session->id;
        $this->currentTime = Carbon::today()->setTime(8, 0, 0);
        echo "✅ Session ID: {$this->sessionId}\n";
    }

    private function generateRealRoute(): void
    {
        // Start at Simfoni Tower, go north on Jalan Ampang
        $this->addRoute([
            ['lat' => 3.1480, 'lng' => 101.6990, 'speed' => 0],   // Simfoni Tower (start)
            ['lat' => 3.1485, 'lng' => 101.6993, 'speed' => 5],
            ['lat' => 3.1492, 'lng' => 101.6998, 'speed' => 15],
            ['lat' => 3.1500, 'lng' => 101.7005, 'speed' => 20],
            ['lat' => 3.1510, 'lng' => 101.7015, 'speed' => 22],
            ['lat' => 3.1520, 'lng' => 101.7025, 'speed' => 25],  // On Jalan Ampang heading north
        ], 3);

        // Continue on Jalan Ampang toward KLCC
        $this->addRoute([
            ['lat' => 3.1530, 'lng' => 101.7035, 'speed' => 18],
            ['lat' => 3.1540, 'lng' => 101.7045, 'speed' => 20],
            ['lat' => 3.1548, 'lng' => 101.7055, 'speed' => 15],  // Approaching Pavilion
            ['lat' => 3.1550, 'lng' => 101.7065, 'speed' => 10],
            ['lat' => 3.1550, 'lng' => 101.7075, 'speed' => 8],
        ], 3);

        // Turn onto Jalan Bukit Bintang toward Pavilion
        $this->addRoute([
            ['lat' => 3.1520, 'lng' => 101.7095, 'speed' => 12],
            ['lat' => 3.1505, 'lng' => 101.7115, 'speed' => 10],
            ['lat' => 3.1497, 'lng' => 101.7135, 'speed' => 5],
            ['lat' => 3.1497, 'lng' => 101.7144, 'speed' => 0],   // Pavilion KL arrival
        ], 3);

        // VISIT 1: Pavilion KL
        $visit1Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1497, 101.7144, 20);
        $visit1End = $this->currentTime->copy();
        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => InstallationLocation::where('name', 'LIKE', '%Pavilion%')->first()->id,
            'arrived_at' => $visit1Start,
            'departed_at' => $visit1End,
            'duration_minutes' => 20,
            'status' => 'completed',
        ]);

        // Leave Pavilion, head north on Jalan Raja Chulan
        $this->addRoute([
            ['lat' => 3.1500, 'lng' => 101.7145, 'speed' => 8],
            ['lat' => 3.1510, 'lng' => 101.7145, 'speed' => 15],
            ['lat' => 3.1525, 'lng' => 101.7145, 'speed' => 20],
            ['lat' => 3.1540, 'lng' => 101.7145, 'speed' => 18],
        ], 3);

        // Turn right onto Jalan Ampang toward KLCC
        $this->addRoute([
            ['lat' => 3.1548, 'lng' => 101.7140, 'speed' => 15],
            ['lat' => 3.1555, 'lng' => 101.7135, 'speed' => 12],
            ['lat' => 3.1563, 'lng' => 101.7127, 'speed' => 10],
            ['lat' => 3.1570, 'lng' => 101.7120, 'speed' => 8],
            ['lat' => 3.1578, 'lng' => 101.7117, 'speed' => 0],   // Suria KLCC arrival
        ], 3);

        // VISIT 2: Suria KLCC
        $visit2Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1578, 101.7117, 25);
        $visit2End = $this->currentTime->copy();
        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => InstallationLocation::where('name', 'LIKE', '%KLCC%')->first()->id,
            'arrived_at' => $visit2Start,
            'departed_at' => $visit2End,
            'duration_minutes' => 25,
            'status' => 'completed',
        ]);

        // Coffee break nearby
        $this->addRoute([
            ['lat' => 3.1580, 'lng' => 101.7120, 'speed' => 5],
            ['lat' => 3.1582, 'lng' => 101.7125, 'speed' => 3],
            ['lat' => 3.1583, 'lng' => 101.7128, 'speed' => 0],
        ], 3);

        $breakStart = $this->currentTime->copy();
        $this->addStoppedPoints(3.1583, 101.7128, 15);
        $breakEnd = $this->currentTime->copy();
        StopRecord::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'latitude' => 3.1583,
            'longitude' => 101.7128,
            'started_at' => $breakStart,
            'ended_at' => $breakEnd,
            'duration_minutes' => 15,
        ]);

        // Continue north on Jalan Ampang
        $this->addRoute([
            ['lat' => 3.1585, 'lng' => 101.7135, 'speed' => 12],
            ['lat' => 3.1590, 'lng' => 101.7145, 'speed' => 18],
            ['lat' => 3.1595, 'lng' => 101.7155, 'speed' => 20],
            ['lat' => 3.1600, 'lng' => 101.7165, 'speed' => 5],   // Ampang Park arrival
        ], 3);

        // VISIT 3: Ampang Park
        $visit3Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1600, 101.7165, 18);
        $visit3End = $this->currentTime->copy();
        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => InstallationLocation::where('name', 'LIKE', '%Ampang Park%')->first()->id,
            'arrived_at' => $visit3Start,
            'departed_at' => $visit3End,
            'duration_minutes' => 18,
            'status' => 'completed',
        ]);

        // Head back south on Jalan Ampang
        $this->addRoute([
            ['lat' => 3.1595, 'lng' => 101.7160, 'speed' => 15],
            ['lat' => 3.1590, 'lng' => 101.7155, 'speed' => 20],
            ['lat' => 3.1585, 'lng' => 101.7150, 'speed' => 22],
            ['lat' => 3.1583, 'lng' => 101.7145, 'speed' => 5],   // Avenue K arrival
        ], 3);

        // VISIT 4: Avenue K
        $visit4Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1583, 101.7145, 22);
        $visit4End = $this->currentTime->copy();
        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => InstallationLocation::where('name', 'LIKE', '%Avenue K%')->first()->id,
            'arrived_at' => $visit4Start,
            'departed_at' => $visit4End,
            'duration_minutes' => 22,
            'status' => 'completed',
        ]);

        // Lunch break
        $this->addRoute([
            ['lat' => 3.1580, 'lng' => 101.7140, 'speed' => 8],
            ['lat' => 3.1578, 'lng' => 101.7135, 'speed' => 5],
            ['lat' => 3.1575, 'lng' => 101.7133, 'speed' => 0],
        ], 3);

        $lunchStart = $this->currentTime->copy();
        $this->addStoppedPoints(3.1575, 101.7133, 30);
        $lunchEnd = $this->currentTime->copy();
        StopRecord::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'latitude' => 3.1575,
            'longitude' => 101.7133,
            'started_at' => $lunchStart,
            'ended_at' => $lunchEnd,
            'duration_minutes' => 30,
        ]);

        // Continue south on Jalan Ampang back toward start
        $this->addRoute([
            ['lat' => 3.1570, 'lng' => 101.7125, 'speed' => 18],
            ['lat' => 3.1560, 'lng' => 101.7110, 'speed' => 20],
            ['lat' => 3.1550, 'lng' => 101.7095, 'speed' => 22],
            ['lat' => 3.1540, 'lng' => 101.7080, 'speed' => 20],
            ['lat' => 3.1530, 'lng' => 101.7065, 'speed' => 18],
            ['lat' => 3.1520, 'lng' => 101.7050, 'speed' => 20],
            ['lat' => 3.1510, 'lng' => 101.7035, 'speed' => 18],
            ['lat' => 3.1500, 'lng' => 101.7020, 'speed' => 15],
            ['lat' => 3.1490, 'lng' => 101.7005, 'speed' => 12],
            ['lat' => 3.1485, 'lng' => 101.6995, 'speed' => 8],
            ['lat' => 3.1482, 'lng' => 101.6991, 'speed' => 0],   // Back near Simfoni Tower
        ], 3);
    }

    private function addRoute(array $waypoints, int $interval): void
    {
        foreach ($waypoints as $point) {
            LocationPoint::create([
                'rider_id' => $this->riderId,
                'duty_session_id' => $this->sessionId,
                'latitude' => $point['lat'],
                'longitude' => $point['lng'],
                'accuracy' => rand(8, 15) + (rand(0, 100) / 100),
                'altitude' => rand(30, 80),
                'bearing' => rand(0, 359),
                'speed' => $point['speed'] / 3.6,
                'recorded_at' => $this->currentTime,
            ]);

            $this->currentTime->addSeconds($point['speed'] == 0 ? 30 : $interval);
        }
    }

    private function addStoppedPoints(float $lat, float $lng, int $minutes): void
    {
        $count = $minutes / 5;
        for ($i = 0; $i < $count; $i++) {
            LocationPoint::create([
                'rider_id' => $this->riderId,
                'duty_session_id' => $this->sessionId,
                'latitude' => $lat + (rand(-5, 5) / 1000000),
                'longitude' => $lng + (rand(-5, 5) / 1000000),
                'accuracy' => rand(8, 15),
                'altitude' => rand(30, 80),
                'bearing' => rand(0, 359),
                'speed' => 0,
                'recorded_at' => $this->currentTime,
            ]);

            $this->currentTime->addMinutes(5);
        }
    }
}
