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

class RouteWithInstallationsSeeder extends Seeder
{
    private $riderId = 1;
    private $sessionId;
    private $currentTime;

    public function run(): void
    {
        echo "🧹 Cleaning existing data...\n";
        $this->cleanAllData();

        echo "📍 Creating installations along route...\n";
        $this->createInstallationsAlongRoute();

        echo "👤 Creating duty session...\n";
        $this->createDutySession();

        echo "🗺️ Generating route with visits and stops...\n";
        $this->generateCompleteRoute();

        $total = LocationPoint::where('duty_session_id', $this->sessionId)->count();
        $visits = InstallationVisit::where('duty_session_id', $this->sessionId)->count();
        $stops = StopRecord::where('duty_session_id', $this->sessionId)->count();

        echo "✅ Generated {$total} GPS points\n";
        echo "✅ Created {$visits} installation visits\n";
        echo "✅ Created {$stops} stop records\n";
    }

    private function cleanAllData(): void
    {
        DB::table('location_points')->delete();
        DB::table('stop_records')->delete();
        DB::table('installation_visits')->delete();
        DB::table('duty_sessions')->delete();
        DB::table('installation_locations')->where('latitude', '>', 3.14)->delete(); // Only KL area
        echo "✅ Data cleaned\n";
    }

    private function createInstallationsAlongRoute(): void
    {
        $installations = [
            [
                'name' => 'KLCC Suria Mall - POS System',
                'address' => 'Kuala Lumpur City Centre, Jalan Ampang',
                'latitude' => 3.1572,
                'longitude' => 101.7048,
                'geofence_radius_meters' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Ampang Point - Digital Signage',
                'address' => 'Jalan Mamanda, Ampang',
                'latitude' => 3.1612,
                'longitude' => 101.7024,
                'geofence_radius_meters' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Wangsa Walk Mall - Network Setup',
                'address' => 'Jalan Wangsa Perdana, Wangsa Maju',
                'latitude' => 3.1768,
                'longitude' => 101.7169,
                'geofence_radius_meters' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Avenue K Mall - Server Maintenance',
                'address' => 'Jalan Ampang, KLCC',
                'latitude' => 3.1578,
                'longitude' => 101.7051,
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

    private function generateCompleteRoute(): void
    {
        // Morning commute
        $this->addRoute([
            ['lat' => 3.1480, 'lng' => 101.6990, 'speed' => 0], // Start
            ['lat' => 3.1490, 'lng' => 101.6995, 'speed' => 5],
            ['lat' => 3.1505, 'lng' => 101.7000, 'speed' => 15],
            ['lat' => 3.1520, 'lng' => 101.7005, 'speed' => 20],
            ['lat' => 3.1535, 'lng' => 101.6995, 'speed' => 12],
            ['lat' => 3.1555, 'lng' => 101.6975, 'speed' => 22],
        ], 3);

        // First Installation Visit - KLCC Suria Mall
        $this->addRoute([
            ['lat' => 3.1570, 'lng' => 101.6995, 'speed' => 15],
            ['lat' => 3.1572, 'lng' => 101.7040, 'speed' => 10],
            ['lat' => 3.1572, 'lng' => 101.7048, 'speed' => 0],
        ], 3);

        $visit1Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1572, 101.7048, 20); // 20 min installation
        $visit1End = $this->currentTime->copy();

        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => InstallationLocation::where('name', 'LIKE', '%KLCC%')->first()->id,
            'arrived_at' => $visit1Start,
            'departed_at' => $visit1End,
            'duration_minutes' => 20,
            'status' => 'completed',
        ]);

        // Traffic jam area
        $this->addRoute([
            ['lat' => 3.1565, 'lng' => 101.7035, 'speed' => 3],
            ['lat' => 3.1562, 'lng' => 101.7032, 'speed' => 0],
            ['lat' => 3.1560, 'lng' => 101.7030, 'speed' => 5],
            ['lat' => 3.1556, 'lng' => 101.7026, 'speed' => 7],
        ], 10);

        // Second Installation Visit - Ampang Point
        $this->addRoute([
            ['lat' => 3.1605, 'lng' => 101.7028, 'speed' => 22],
            ['lat' => 3.1610, 'lng' => 101.7025, 'speed' => 8],
            ['lat' => 3.1612, 'lng' => 101.7024, 'speed' => 0],
        ], 3);

        $visit2Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1612, 101.7024, 15); // 15 min installation
        $visit2End = $this->currentTime->copy();

        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => InstallationLocation::where('name', 'LIKE', '%Ampang Point%')->first()->id,
            'arrived_at' => $visit2Start,
            'departed_at' => $visit2End,
            'duration_minutes' => 15,
            'status' => 'completed',
        ]);

        // Coffee break (stop record)
        $this->addRoute([
            ['lat' => 3.1615, 'lng' => 101.7022, 'speed' => 10],
            ['lat' => 3.1618, 'lng' => 101.7020, 'speed' => 5],
            ['lat' => 3.1620, 'lng' => 101.7020, 'speed' => 0],
        ], 3);

        $breakStart = $this->currentTime->copy();
        $this->addStoppedPoints(3.1620, 101.7020, 10); // 10 min coffee break
        $breakEnd = $this->currentTime->copy();

        StopRecord::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'latitude' => 3.1620,
            'longitude' => 101.7020,
            'started_at' => $breakStart,
            'ended_at' => $breakEnd,
            'duration_minutes' => 10,
        ]);

        // Highway segment
        $this->addRoute([
            ['lat' => 3.1625, 'lng' => 101.7025, 'speed' => 20],
            ['lat' => 3.1650, 'lng' => 101.7055, 'speed' => 55],
            ['lat' => 3.1680, 'lng' => 101.7090, 'speed' => 68],
            ['lat' => 3.1710, 'lng' => 101.7125, 'speed' => 72],
            ['lat' => 3.1740, 'lng' => 101.7160, 'speed' => 65],
            ['lat' => 3.1765, 'lng' => 101.7190, 'speed' => 58],
        ], 1);

        // Third Installation Visit - Wangsa Walk Mall
        $this->addRoute([
            ['lat' => 3.1770, 'lng' => 101.7172, 'speed' => 22],
            ['lat' => 3.1768, 'lng' => 101.7170, 'speed' => 10],
            ['lat' => 3.1768, 'lng' => 101.7169, 'speed' => 0],
        ], 3);

        $visit3Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1768, 101.7169, 25); // 25 min installation
        $visit3End = $this->currentTime->copy();

        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => InstallationLocation::where('name', 'LIKE', '%Wangsa Walk%')->first()->id,
            'arrived_at' => $visit3Start,
            'departed_at' => $visit3End,
            'duration_minutes' => 25,
            'status' => 'completed',
        ]);

        // Lunch break (stop record)
        $this->addRoute([
            ['lat' => 3.1770, 'lng' => 101.7185, 'speed' => 20],
            ['lat' => 3.1772, 'lng' => 101.7200, 'speed' => 10],
            ['lat' => 3.1775, 'lng' => 101.7205, 'speed' => 0],
        ], 3);

        $lunchStart = $this->currentTime->copy();
        $this->addStoppedPoints(3.1775, 101.7205, 30); // 30 min lunch
        $lunchEnd = $this->currentTime->copy();

        StopRecord::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'latitude' => 3.1775,
            'longitude' => 101.7205,
            'started_at' => $lunchStart,
            'ended_at' => $lunchEnd,
            'duration_minutes' => 30,
        ]);

        // Fourth Installation Visit - Avenue K
        $this->addRoute([
            ['lat' => 3.1750, 'lng' => 101.7180, 'speed' => 20],
            ['lat' => 3.1650, 'lng' => 101.7100, 'speed' => 24],
            ['lat' => 3.1580, 'lng' => 101.7052, 'speed' => 18],
            ['lat' => 3.1578, 'lng' => 101.7051, 'speed' => 0],
        ], 3);

        $visit4Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1578, 101.7051, 18); // 18 min installation
        $visit4End = $this->currentTime->copy();

        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => InstallationLocation::where('name', 'LIKE', '%Avenue K%')->first()->id,
            'arrived_at' => $visit4Start,
            'departed_at' => $visit4End,
            'duration_minutes' => 18,
            'status' => 'completed',
        ]);

        // Fast road segment
        $this->addRoute([
            ['lat' => 3.1600, 'lng' => 101.7070, 'speed' => 35],
            ['lat' => 3.1650, 'lng' => 101.7120, 'speed' => 48],
            ['lat' => 3.1700, 'lng' => 101.7170, 'speed' => 52],
        ], 1);

        // Evening traffic
        $this->addRoute([
            ['lat' => 3.1680, 'lng' => 101.7150, 'speed' => 8],
            ['lat' => 3.1660, 'lng' => 101.7130, 'speed' => 3],
            ['lat' => 3.1660, 'lng' => 101.7130, 'speed' => 0],
            ['lat' => 3.1640, 'lng' => 101.7110, 'speed' => 5],
        ], 10);

        // Return journey
        $this->addRoute([
            ['lat' => 3.1600, 'lng' => 101.7075, 'speed' => 22],
            ['lat' => 3.1550, 'lng' => 101.7040, 'speed' => 20],
            ['lat' => 3.1500, 'lng' => 101.7005, 'speed' => 18],
            ['lat' => 3.1485, 'lng' => 101.6992, 'speed' => 8],
            ['lat' => 3.1482, 'lng' => 101.6991, 'speed' => 0],
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
        $count = $minutes / 5; // One point every 5 minutes
        for ($i = 0; $i < $count; $i++) {
            LocationPoint::create([
                'rider_id' => $this->riderId,
                'duty_session_id' => $this->sessionId,
                'latitude' => $lat + (rand(-10, 10) / 100000), // Small GPS drift
                'longitude' => $lng + (rand(-10, 10) / 100000),
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
