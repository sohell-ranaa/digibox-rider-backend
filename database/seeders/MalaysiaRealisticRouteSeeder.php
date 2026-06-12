<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rider;
use App\Models\DutySession;
use App\Models\LocationPoint;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MalaysiaRealisticRouteSeeder extends Seeder
{
    private $riderId = 1;
    private $sessionId;
    private $currentTime;
    private $pointCounter = 0;

    /**
     * Realistic route through Kuala Lumpur with curves, turns, and varied patterns
     * Starting from Simfoni Tower
     */
    public function run(): void
    {
        echo "🧹 Cleaning existing data...\n";
        $this->cleanAllData();

        echo "📍 Creating duty session for rider01...\n";
        $this->createDutySession();

        echo "🗺️ Generating realistic route through KL...\n";

        // Morning route with curves and turns
        $this->morningCommuteWithTurns();
        $this->firstDeliveryLoop();
        $this->trafficJamArea();
        $this->multipleDeliveriesWithCurves();
        $this->lunchBreak();
        $this->highwaySegment();

        // Afternoon route
        $this->afternoonDeliveryLoop();
        $this->fastRoadSegment();
        $this->eveningRushHour();
        $this->returnJourney();

        $total = LocationPoint::where('duty_session_id', $this->sessionId)->count();
        echo "✅ Generated {$total} GPS points with realistic curved route!\n";
    }

    private function cleanAllData(): void
    {
        DB::table('location_points')->delete();
        DB::table('stop_records')->delete();
        DB::table('installation_visits')->delete();
        DB::table('duty_sessions')->delete();
        echo "✅ All data cleaned\n";
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
        echo "✅ Session created: ID {$this->sessionId}\n";
    }

    // Morning commute with realistic turns
    private function morningCommuteWithTurns(): void
    {
        $route = [
            // Start at Simfoni Tower
            ['lat' => 3.1480, 'lng' => 101.6990, 'speed' => 0],

            // Head north on Jalan Ampang with traffic lights
            ['lat' => 3.1490, 'lng' => 101.6995, 'speed' => 5],
            ['lat' => 3.1505, 'lng' => 101.7000, 'speed' => 15],
            ['lat' => 3.1520, 'lng' => 101.7005, 'speed' => 20],
            ['lat' => 3.1530, 'lng' => 101.7008, 'speed' => 0], // Traffic light

            // Turn left (west) onto smaller street
            ['lat' => 3.1535, 'lng' => 101.6995, 'speed' => 12],
            ['lat' => 3.1540, 'lng' => 101.6980, 'speed' => 18],

            // Curve right going northwest
            ['lat' => 3.1555, 'lng' => 101.6975, 'speed' => 22],
            ['lat' => 3.1570, 'lng' => 101.6978, 'speed' => 25],

            // Turn right heading east toward KLCC
            ['lat' => 3.1575, 'lng' => 101.6995, 'speed' => 15],
            ['lat' => 3.1575, 'lng' => 101.7010, 'speed' => 20],
            ['lat' => 3.1575, 'lng' => 101.7025, 'speed' => 18],
        ];

        $this->insertRoute($route, 3); // 3 seconds interval
    }

    // First delivery with parking and loop
    private function firstDeliveryLoop(): void
    {
        $route = [
            // Arrive at delivery area
            ['lat' => 3.1575, 'lng' => 101.7040, 'speed' => 10],
            ['lat' => 3.1573, 'lng' => 101.7045, 'speed' => 5],
            ['lat' => 3.1572, 'lng' => 101.7048, 'speed' => 0], // Stop

            // Stopped for 15 minutes (delivery)
            ['lat' => 3.1572, 'lng' => 101.7048, 'speed' => 0],
            ['lat' => 3.1572, 'lng' => 101.7048, 'speed' => 0],
            ['lat' => 3.1572, 'lng' => 101.7048, 'speed' => 0],
            ['lat' => 3.1572, 'lng' => 101.7048, 'speed' => 0],
            ['lat' => 3.1572, 'lng' => 101.7048, 'speed' => 0],

            // Leave and make U-turn
            ['lat' => 3.1570, 'lng' => 101.7045, 'speed' => 8],
            ['lat' => 3.1568, 'lng' => 101.7040, 'speed' => 12],
        ];

        $this->insertRoute($route, 30); // 30 sec for stopped, 3 sec for moving
    }

    // Traffic jam with stop-and-go
    private function trafficJamArea(): void
    {
        $route = [
            ['lat' => 3.1565, 'lng' => 101.7035, 'speed' => 3],
            ['lat' => 3.1562, 'lng' => 101.7032, 'speed' => 0], // Stopped
            ['lat' => 3.1562, 'lng' => 101.7032, 'speed' => 0],
            ['lat' => 3.1560, 'lng' => 101.7030, 'speed' => 5],
            ['lat' => 3.1558, 'lng' => 101.7028, 'speed' => 2],
            ['lat' => 3.1558, 'lng' => 101.7028, 'speed' => 0],
            ['lat' => 3.1556, 'lng' => 101.7026, 'speed' => 7],
            ['lat' => 3.1554, 'lng' => 101.7024, 'speed' => 4],
        ];

        $this->insertRoute($route, 10);
    }

    // Multiple deliveries with curved route
    private function multipleDeliveriesWithCurves(): void
    {
        $route = [
            // Delivery 2 - Southwest loop
            ['lat' => 3.1550, 'lng' => 101.7020, 'speed' => 18],
            ['lat' => 3.1540, 'lng' => 101.7010, 'speed' => 22],
            ['lat' => 3.1530, 'lng' => 101.7005, 'speed' => 15],
            ['lat' => 3.1525, 'lng' => 101.7003, 'speed' => 0], // Delivery stop
            ['lat' => 3.1525, 'lng' => 101.7003, 'speed' => 0],
            ['lat' => 3.1525, 'lng' => 101.7003, 'speed' => 0],

            // Head east with curves
            ['lat' => 3.1528, 'lng' => 101.7015, 'speed' => 20],
            ['lat' => 3.1532, 'lng' => 101.7030, 'speed' => 25],
            ['lat' => 3.1538, 'lng' => 101.7042, 'speed' => 22],

            // Delivery 3 - Turn north
            ['lat' => 3.1550, 'lng' => 101.7045, 'speed' => 15],
            ['lat' => 3.1565, 'lng' => 101.7048, 'speed' => 18],
            ['lat' => 3.1575, 'lng' => 101.7050, 'speed' => 10],
            ['lat' => 3.1578, 'lng' => 101.7051, 'speed' => 0], // Stop
            ['lat' => 3.1578, 'lng' => 101.7051, 'speed' => 0],
            ['lat' => 3.1578, 'lng' => 101.7051, 'speed' => 0],

            // Curved path northwest
            ['lat' => 3.1585, 'lng' => 101.7045, 'speed' => 20],
            ['lat' => 3.1595, 'lng' => 101.7035, 'speed' => 24],
            ['lat' => 3.1605, 'lng' => 101.7028, 'speed' => 22],

            // Delivery 4
            ['lat' => 3.1610, 'lng' => 101.7025, 'speed' => 8],
            ['lat' => 3.1612, 'lng' => 101.7024, 'speed' => 0], // Stop
            ['lat' => 3.1612, 'lng' => 101.7024, 'speed' => 0],
            ['lat' => 3.1612, 'lng' => 101.7024, 'speed' => 0],
        ];

        $this->insertRoute($route, 3);
    }

    // Lunch break - stationary
    private function lunchBreak(): void
    {
        $route = [
            ['lat' => 3.1615, 'lng' => 101.7022, 'speed' => 10],
            ['lat' => 3.1618, 'lng' => 101.7020, 'speed' => 5],
            ['lat' => 3.1620, 'lng' => 101.7020, 'speed' => 0], // Lunch spot
            // Stay for 30 minutes
            ['lat' => 3.1620, 'lng' => 101.7020, 'speed' => 0],
            ['lat' => 3.1620, 'lng' => 101.7020, 'speed' => 0],
            ['lat' => 3.1620, 'lng' => 101.7020, 'speed' => 0],
            ['lat' => 3.1620, 'lng' => 101.7020, 'speed' => 0],
            ['lat' => 3.1620, 'lng' => 101.7020, 'speed' => 0],
            ['lat' => 3.1620, 'lng' => 101.7020, 'speed' => 0],
        ];

        $this->insertRoute($route, 30);
    }

    // Highway segment - straight but fast
    private function highwaySegment(): void
    {
        $route = [
            // Get on highway entrance
            ['lat' => 3.1625, 'lng' => 101.7025, 'speed' => 20],
            ['lat' => 3.1635, 'lng' => 101.7035, 'speed' => 35],

            // Highway - going northeast fast
            ['lat' => 3.1650, 'lng' => 101.7055, 'speed' => 55],
            ['lat' => 3.1680, 'lng' => 101.7090, 'speed' => 68],
            ['lat' => 3.1710, 'lng' => 101.7125, 'speed' => 72], // Max speed
            ['lat' => 3.1740, 'lng' => 101.7160, 'speed' => 65],
            ['lat' => 3.1765, 'lng' => 101.7190, 'speed' => 58],

            // Exit highway
            ['lat' => 3.1780, 'lng' => 101.7210, 'speed' => 40],
            ['lat' => 3.1790, 'lng' => 101.7220, 'speed' => 25],
        ];

        $this->insertRoute($route, 1); // 1 sec interval for highway
    }

    // Afternoon deliveries - circular pattern
    private function afternoonDeliveryLoop(): void
    {
        $route = [
            // Head west in a curve
            ['lat' => 3.1795, 'lng' => 101.7210, 'speed' => 20],
            ['lat' => 3.1800, 'lng' => 101.7195, 'speed' => 22],
            ['lat' => 3.1805, 'lng' => 101.7180, 'speed' => 18],
            ['lat' => 3.1808, 'lng' => 101.7178, 'speed' => 0], // Delivery 5
            ['lat' => 3.1808, 'lng' => 101.7178, 'speed' => 0],
            ['lat' => 3.1808, 'lng' => 101.7178, 'speed' => 0],

            // South curve
            ['lat' => 3.1800, 'lng' => 101.7175, 'speed' => 20],
            ['lat' => 3.1785, 'lng' => 101.7172, 'speed' => 24],
            ['lat' => 3.1770, 'lng' => 101.7170, 'speed' => 22],
            ['lat' => 3.1768, 'lng' => 101.7169, 'speed' => 0], // Delivery 6
            ['lat' => 3.1768, 'lng' => 101.7169, 'speed' => 0],
            ['lat' => 3.1768, 'lng' => 101.7169, 'speed' => 0],

            // East curve
            ['lat' => 3.1770, 'lng' => 101.7185, 'speed' => 20],
            ['lat' => 3.1772, 'lng' => 101.7200, 'speed' => 18],
            ['lat' => 3.1775, 'lng' => 101.7215, 'speed' => 22],
        ];

        $this->insertRoute($route, 3);
    }

    // Fast road segment
    private function fastRoadSegment(): void
    {
        $route = [
            ['lat' => 3.1780, 'lng' => 101.7230, 'speed' => 30],
            ['lat' => 3.1790, 'lng' => 101.7250, 'speed' => 45],
            ['lat' => 3.1805, 'lng' => 101.7275, 'speed' => 52],
            ['lat' => 3.1820, 'lng' => 101.7300, 'speed' => 55],
            ['lat' => 3.1830, 'lng' => 101.7320, 'speed' => 48],
            ['lat' => 3.1838, 'lng' => 101.7335, 'speed' => 38],
        ];

        $this->insertRoute($route, 1);
    }

    // Evening rush - slow with stops
    private function eveningRushHour(): void
    {
        $route = [
            ['lat' => 3.1840, 'lng' => 101.7330, 'speed' => 8],
            ['lat' => 3.1842, 'lng' => 101.7325, 'speed' => 3],
            ['lat' => 3.1842, 'lng' => 101.7325, 'speed' => 0], // Traffic
            ['lat' => 3.1842, 'lng' => 101.7325, 'speed' => 0],
            ['lat' => 3.1844, 'lng' => 101.7320, 'speed' => 5],
            ['lat' => 3.1846, 'lng' => 101.7315, 'speed' => 2],
            ['lat' => 3.1846, 'lng' => 101.7315, 'speed' => 0],
            ['lat' => 3.1848, 'lng' => 101.7310, 'speed' => 7],
            ['lat' => 3.1850, 'lng' => 101.7305, 'speed' => 10],
        ];

        $this->insertRoute($route, 10);
    }

    // Return journey - curved path back
    private function returnJourney(): void
    {
        $route = [
            // Southwest curve back
            ['lat' => 3.1845, 'lng' => 101.7295, 'speed' => 20],
            ['lat' => 3.1835, 'lng' => 101.7280, 'speed' => 22],
            ['lat' => 3.1820, 'lng' => 101.7260, 'speed' => 24],
            ['lat' => 3.1800, 'lng' => 101.7240, 'speed' => 20],

            // South toward start
            ['lat' => 3.1780, 'lng' => 101.7230, 'speed' => 22],
            ['lat' => 3.1750, 'lng' => 101.7210, 'speed' => 20],
            ['lat' => 3.1720, 'lng' => 101.7185, 'speed' => 18],
            ['lat' => 3.1690, 'lng' => 101.7160, 'speed' => 20],

            // Final curve west back to start area
            ['lat' => 3.1660, 'lng' => 101.7135, 'speed' => 18],
            ['lat' => 3.1630, 'lng' => 101.7105, 'speed' => 20],
            ['lat' => 3.1600, 'lng' => 101.7075, 'speed' => 22],
            ['lat' => 3.1570, 'lng' => 101.7045, 'speed' => 18],
            ['lat' => 3.1540, 'lng' => 101.7020, 'speed' => 15],
            ['lat' => 3.1510, 'lng' => 101.7000, 'speed' => 12],

            // Arrive back near Simfoni Tower
            ['lat' => 3.1490, 'lng' => 101.6995, 'speed' => 8],
            ['lat' => 3.1485, 'lng' => 101.6992, 'speed' => 5],
            ['lat' => 3.1482, 'lng' => 101.6991, 'speed' => 0], // Final stop
        ];

        $this->insertRoute($route, 3);
    }

    private function insertRoute(array $waypoints, int $baseInterval): void
    {
        foreach ($waypoints as $point) {
            $accuracy = rand(8, 15) + (rand(0, 100) / 100);

            LocationPoint::create([
                'rider_id' => $this->riderId,
                'duty_session_id' => $this->sessionId,
                'latitude' => $point['lat'],
                'longitude' => $point['lng'],
                'accuracy' => $accuracy,
                'altitude' => rand(30, 80),
                'bearing' => rand(0, 359),
                'speed' => $point['speed'] / 3.6, // Convert km/h to m/s
                'recorded_at' => $this->currentTime,
            ]);

            $this->pointCounter++;

            // Adjust interval based on speed
            if ($point['speed'] == 0) {
                $interval = 30; // 30 sec when stopped
            } elseif ($point['speed'] > 50) {
                $interval = 1; // 1 sec when fast
            } else {
                $interval = $baseInterval;
            }

            $this->currentTime->addSeconds($interval);
        }
    }
}
