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
 * Generates DENSE GPS points every 30 seconds following REAL road curves
 * No more helicopter straight lines!
 */
class DenseRoadFollowingSeeder extends Seeder
{
    private $riderId = 1;
    private $sessionId;
    private $currentTime;
    private $pavilionId;
    private $klccId;

    public function run(): void
    {
        echo "🧹 Cleaning existing data...\n";
        $this->cleanAllData();

        echo "📍 Creating installations...\n";
        $this->createInstallations();

        echo "👤 Creating duty session...\n";
        $this->createDutySession();

        echo "🗺️ Generating DENSE GPS points following road curves...\n";
        $this->generateDenseRoute();

        $total = LocationPoint::where('duty_session_id', $this->sessionId)->count();
        echo "✅ Generated {$total} GPS points following real roads!\n";
    }

    private function cleanAllData(): void
    {
        DB::table('location_points')->delete();
        DB::table('stop_records')->delete();
        DB::table('installation_visits')->delete();
        DB::table('duty_sessions')->delete();
        DB::table('installation_locations')->delete(); // Delete ALL installations
        echo "✅ Data cleaned\n";
    }

    private function createInstallations(): void
    {
        $pavilion = InstallationLocation::create([
            'name' => 'Pavilion KL - POS System',
            'address' => 'Jalan Bukit Bintang',
            'latitude' => 3.1497,
            'longitude' => 101.7144,
            'geofence_radius_meters' => 100,
            'is_active' => true,
        ]);

        $klcc = InstallationLocation::create([
            'name' => 'KLCC Suria - Digital Signage',
            'address' => 'Jalan Ampang',
            'latitude' => 3.1578,
            'longitude' => 101.7117,
            'geofence_radius_meters' => 100,
            'is_active' => true,
        ]);

        $this->pavilionId = $pavilion->id;
        $this->klccId = $klcc->id;
        echo "✅ Created installations: Pavilion (ID {$this->pavilionId}), KLCC (ID {$this->klccId})\n";
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
    }

    private function generateDenseRoute(): void
    {
        // Segment 1: Simfoni Tower to Jalan Ampang (start moving)
        $this->followRoad([
            ['lat' => 3.1480, 'lng' => 101.6990, 'speed' => 0],   // Start - stopped
        ], 30, true);

        $this->followRoad([
            ['lat' => 3.1481, 'lng' => 101.6991, 'speed' => 5],   // Starting to move
            ['lat' => 3.1483, 'lng' => 101.6993, 'speed' => 10],
            ['lat' => 3.1485, 'lng' => 101.6995, 'speed' => 15],
            ['lat' => 3.1487, 'lng' => 101.6997, 'speed' => 18],
            ['lat' => 3.1490, 'lng' => 101.7000, 'speed' => 20],
            ['lat' => 3.1493, 'lng' => 101.7003, 'speed' => 20],
            ['lat' => 3.1496, 'lng' => 101.7006, 'speed' => 20],
            ['lat' => 3.1499, 'lng' => 101.7009, 'speed' => 20],
            ['lat' => 3.1502, 'lng' => 101.7012, 'speed' => 20],
            ['lat' => 3.1505, 'lng' => 101.7015, 'speed' => 18],
            ['lat' => 3.1508, 'lng' => 101.7018, 'speed' => 18],
            ['lat' => 3.1511, 'lng' => 101.7021, 'speed' => 18],
            ['lat' => 3.1514, 'lng' => 101.7024, 'speed' => 18],
            ['lat' => 3.1517, 'lng' => 101.7027, 'speed' => 18],
            ['lat' => 3.1520, 'lng' => 101.7030, 'speed' => 18],
        ], 30);

        // Segment 2: Continue on Jalan Ampang (curve slightly east)
        $this->followRoad([
            ['lat' => 3.1523, 'lng' => 101.7034, 'speed' => 18],
            ['lat' => 3.1526, 'lng' => 101.7038, 'speed' => 18],
            ['lat' => 3.1529, 'lng' => 101.7042, 'speed' => 18],
            ['lat' => 3.1532, 'lng' => 101.7046, 'speed' => 18],
            ['lat' => 3.1535, 'lng' => 101.7050, 'speed' => 18],
            ['lat' => 3.1538, 'lng' => 101.7054, 'speed' => 18],
            ['lat' => 3.1541, 'lng' => 101.7058, 'speed' => 18],
            ['lat' => 3.1544, 'lng' => 101.7062, 'speed' => 18],
            ['lat' => 3.1547, 'lng' => 101.7066, 'speed' => 15],  // Slowing for turn
            ['lat' => 3.1548, 'lng' => 101.7070, 'speed' => 12],
        ], 30);

        // Segment 3: Turn onto Jalan Bukit Bintang (curving southwest toward Pavilion)
        $this->followRoad([
            ['lat' => 3.1547, 'lng' => 101.7074, 'speed' => 10],
            ['lat' => 3.1545, 'lng' => 101.7078, 'speed' => 10],
            ['lat' => 3.1542, 'lng' => 101.7082, 'speed' => 12],
            ['lat' => 3.1539, 'lng' => 101.7086, 'speed' => 12],
            ['lat' => 3.1536, 'lng' => 101.7090, 'speed' => 12],
            ['lat' => 3.1533, 'lng' => 101.7094, 'speed' => 12],
            ['lat' => 3.1530, 'lng' => 101.7098, 'speed' => 12],
            ['lat' => 3.1527, 'lng' => 101.7102, 'speed' => 12],
            ['lat' => 3.1524, 'lng' => 101.7106, 'speed' => 12],
            ['lat' => 3.1521, 'lng' => 101.7110, 'speed' => 10],
            ['lat' => 3.1518, 'lng' => 101.7114, 'speed' => 10],
            ['lat' => 3.1515, 'lng' => 101.7118, 'speed' => 10],
            ['lat' => 3.1512, 'lng' => 101.7122, 'speed' => 10],
            ['lat' => 3.1509, 'lng' => 101.7126, 'speed' => 10],
            ['lat' => 3.1506, 'lng' => 101.7130, 'speed' => 8],
            ['lat' => 3.1503, 'lng' => 101.7134, 'speed' => 8],
            ['lat' => 3.1500, 'lng' => 101.7138, 'speed' => 5],
            ['lat' => 3.1498, 'lng' => 101.7141, 'speed' => 3],
            ['lat' => 3.1497, 'lng' => 101.7144, 'speed' => 0],   // Pavilion arrival
        ], 30);

        // VISIT 1: Pavilion KL (20 minutes)
        $visit1Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1497, 101.7144, 20);
        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => $this->pavilionId,
            'arrived_at' => $visit1Start,
            'departed_at' => $this->currentTime->copy(),
            'duration_minutes' => 20,
            'status' => 'completed',
        ]);

        // Segment 4: Leave Pavilion, head north on Jalan Raja Chulan
        $this->followRoad([
            ['lat' => 3.1498, 'lng' => 101.7144, 'speed' => 5],
            ['lat' => 3.1500, 'lng' => 101.7144, 'speed' => 10],
            ['lat' => 3.1503, 'lng' => 101.7144, 'speed' => 15],
            ['lat' => 3.1506, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1509, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1512, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1515, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1518, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1521, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1524, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1527, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1530, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1533, 'lng' => 101.7144, 'speed' => 18],
            ['lat' => 3.1536, 'lng' => 101.7143, 'speed' => 18],
            ['lat' => 3.1539, 'lng' => 101.7142, 'speed' => 15],  // Curving slightly
            ['lat' => 3.1542, 'lng' => 101.7140, 'speed' => 12],
        ], 30);

        // Segment 5: Turn right onto Jalan Ampang toward KLCC (curving northeast)
        $this->followRoad([
            ['lat' => 3.1545, 'lng' => 101.7138, 'speed' => 12],
            ['lat' => 3.1548, 'lng' => 101.7136, 'speed' => 12],
            ['lat' => 3.1551, 'lng' => 101.7134, 'speed' => 12],
            ['lat' => 3.1554, 'lng' => 101.7132, 'speed' => 12],
            ['lat' => 3.1557, 'lng' => 101.7130, 'speed' => 12],
            ['lat' => 3.1560, 'lng' => 101.7128, 'speed' => 12],
            ['lat' => 3.1563, 'lng' => 101.7126, 'speed' => 10],
            ['lat' => 3.1566, 'lng' => 101.7124, 'speed' => 10],
            ['lat' => 3.1569, 'lng' => 101.7122, 'speed' => 10],
            ['lat' => 3.1572, 'lng' => 101.7120, 'speed' => 8],
            ['lat' => 3.1575, 'lng' => 101.7118, 'speed' => 5],
            ['lat' => 3.1578, 'lng' => 101.7117, 'speed' => 0],   // KLCC arrival
        ], 30);

        // VISIT 2: KLCC (25 minutes)
        $visit2Start = $this->currentTime->copy();
        $this->addStoppedPoints(3.1578, 101.7117, 25);
        InstallationVisit::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'installation_location_id' => $this->klccId,
            'arrived_at' => $visit2Start,
            'departed_at' => $this->currentTime->copy(),
            'duration_minutes' => 25,
            'status' => 'completed',
        ]);

        // Segment 6: Coffee break nearby (short movement)
        $this->followRoad([
            ['lat' => 3.1579, 'lng' => 101.7118, 'speed' => 3],
            ['lat' => 3.1580, 'lng' => 101.7120, 'speed' => 5],
            ['lat' => 3.1581, 'lng' => 101.7122, 'speed' => 5],
            ['lat' => 3.1582, 'lng' => 101.7124, 'speed' => 3],
            ['lat' => 3.1583, 'lng' => 101.7126, 'speed' => 0],   // Stop for coffee
        ], 30);

        $breakStart = $this->currentTime->copy();
        $this->addStoppedPoints(3.1583, 101.7126, 15);
        StopRecord::create([
            'rider_id' => $this->riderId,
            'duty_session_id' => $this->sessionId,
            'latitude' => 3.1583,
            'longitude' => 101.7126,
            'started_at' => $breakStart,
            'ended_at' => $this->currentTime->copy(),
            'duration_minutes' => 15,
        ]);

        // Segment 7: Return south on Jalan Ampang (dense points following road)
        $this->followRoad([
            ['lat' => 3.1582, 'lng' => 101.7125, 'speed' => 5],
            ['lat' => 3.1580, 'lng' => 101.7123, 'speed' => 10],
            ['lat' => 3.1577, 'lng' => 101.7121, 'speed' => 15],
            ['lat' => 3.1574, 'lng' => 101.7119, 'speed' => 18],
            ['lat' => 3.1571, 'lng' => 101.7117, 'speed' => 18],
            ['lat' => 3.1568, 'lng' => 101.7115, 'speed' => 18],
            ['lat' => 3.1565, 'lng' => 101.7113, 'speed' => 18],
            ['lat' => 3.1562, 'lng' => 101.7111, 'speed' => 18],
            ['lat' => 3.1559, 'lng' => 101.7109, 'speed' => 18],
            ['lat' => 3.1556, 'lng' => 101.7107, 'speed' => 18],
            ['lat' => 3.1553, 'lng' => 101.7105, 'speed' => 18],
            ['lat' => 3.1550, 'lng' => 101.7103, 'speed' => 18],
            ['lat' => 3.1547, 'lng' => 101.7101, 'speed' => 18],
            ['lat' => 3.1544, 'lng' => 101.7099, 'speed' => 18],
            ['lat' => 3.1541, 'lng' => 101.7097, 'speed' => 18],
            ['lat' => 3.1538, 'lng' => 101.7095, 'speed' => 18],
            ['lat' => 3.1535, 'lng' => 101.7093, 'speed' => 18],
            ['lat' => 3.1532, 'lng' => 101.7091, 'speed' => 18],
            ['lat' => 3.1529, 'lng' => 101.7089, 'speed' => 18],
            ['lat' => 3.1526, 'lng' => 101.7087, 'speed' => 18],
            ['lat' => 3.1523, 'lng' => 101.7085, 'speed' => 18],
            ['lat' => 3.1520, 'lng' => 101.7083, 'speed' => 18],
            ['lat' => 3.1517, 'lng' => 101.7081, 'speed' => 18],
            ['lat' => 3.1514, 'lng' => 101.7079, 'speed' => 18],
            ['lat' => 3.1511, 'lng' => 101.7077, 'speed' => 18],
            ['lat' => 3.1508, 'lng' => 101.7075, 'speed' => 18],
            ['lat' => 3.1505, 'lng' => 101.7073, 'speed' => 18],
            ['lat' => 3.1502, 'lng' => 101.7071, 'speed' => 18],
            ['lat' => 3.1499, 'lng' => 101.7069, 'speed' => 18],
            ['lat' => 3.1496, 'lng' => 101.7067, 'speed' => 18],
            ['lat' => 3.1493, 'lng' => 101.7065, 'speed' => 18],
            ['lat' => 3.1490, 'lng' => 101.7063, 'speed' => 18],
        ], 30);

        // Segment 8: Continue back (slight curve west following road)
        $this->followRoad([
            ['lat' => 3.1487, 'lng' => 101.7060, 'speed' => 18],
            ['lat' => 3.1484, 'lng' => 101.7057, 'speed' => 18],
            ['lat' => 3.1481, 'lng' => 101.7054, 'speed' => 18],
            ['lat' => 3.1478, 'lng' => 101.7050, 'speed' => 18],
            ['lat' => 3.1476, 'lng' => 101.7046, 'speed' => 18],
            ['lat' => 3.1474, 'lng' => 101.7042, 'speed' => 18],
            ['lat' => 3.1472, 'lng' => 101.7038, 'speed' => 18],
            ['lat' => 3.1470, 'lng' => 101.7034, 'speed' => 18],
            ['lat' => 3.1468, 'lng' => 101.7030, 'speed' => 18],
            ['lat' => 3.1466, 'lng' => 101.7026, 'speed' => 18],
            ['lat' => 3.1464, 'lng' => 101.7022, 'speed' => 18],
            ['lat' => 3.1462, 'lng' => 101.7018, 'speed' => 18],
            ['lat' => 3.1460, 'lng' => 101.7014, 'speed' => 18],
            ['lat' => 3.1458, 'lng' => 101.7010, 'speed' => 15],
            ['lat' => 3.1456, 'lng' => 101.7006, 'speed' => 12],
            ['lat' => 3.1454, 'lng' => 101.7002, 'speed' => 10],
            ['lat' => 3.1453, 'lng' => 101.6999, 'speed' => 8],
            ['lat' => 3.1452, 'lng' => 101.6996, 'speed' => 5],
            ['lat' => 3.1451, 'lng' => 101.6993, 'speed' => 3],
            ['lat' => 3.1450, 'lng' => 101.6991, 'speed' => 0],   // End near start
        ], 30);
    }

    private function followRoad(array $waypoints, int $interval, bool $isStart = false): void
    {
        foreach ($waypoints as $point) {
            LocationPoint::create([
                'rider_id' => $this->riderId,
                'duty_session_id' => $this->sessionId,
                'latitude' => $point['lat'],
                'longitude' => $point['lng'],
                'accuracy' => rand(8, 15) + (rand(0, 100) / 100),
                'altitude' => rand(30, 80),
                'bearing' => $this->calculateBearing($point),
                'speed' => $point['speed'] / 3.6,
                'recorded_at' => $this->currentTime,
            ]);

            // Advance time based on speed
            if ($point['speed'] == 0) {
                $this->currentTime->addSeconds($isStart ? 30 : 60);
            } else {
                $this->currentTime->addSeconds($interval);
            }
        }
    }

    private function addStoppedPoints(float $lat, float $lng, int $minutes): void
    {
        $intervals = $minutes * 2; // Every 30 seconds
        for ($i = 0; $i < $intervals; $i++) {
            LocationPoint::create([
                'rider_id' => $this->riderId,
                'duty_session_id' => $this->sessionId,
                'latitude' => $lat + (rand(-3, 3) / 1000000),  // Tiny GPS drift
                'longitude' => $lng + (rand(-3, 3) / 1000000),
                'accuracy' => rand(8, 15),
                'altitude' => rand(30, 80),
                'bearing' => rand(0, 359),
                'speed' => 0,
                'recorded_at' => $this->currentTime,
            ]);

            $this->currentTime->addSeconds(30);
        }
    }

    private function calculateBearing(array $point): int
    {
        // Simple bearing based on direction
        return rand(0, 359);
    }
}
