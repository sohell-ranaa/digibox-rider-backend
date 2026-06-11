<?php

namespace App\Console\Commands;

use App\Models\Rider;
use App\Models\DutySession;
use App\Models\LocationPoint;
use App\Models\InstallationLocation;
use App\Models\InstallationVisit;
use App\Models\StopRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateSampleTravelData extends Command
{
    protected $signature = 'travel:generate
                            {rider_id=1 : Rider ID to generate data for}
                            {--date=today : Date to generate (today, yesterday, or Y-m-d)}
                            {--clear : Clear existing data first}';

    protected $description = 'Generate realistic sample travel data around Menara Simfoni Tower, Balakong';

    // Menara Simfoni Tower coordinates (Balakong, Selangor, Malaysia)
    private $baseLatitude = 3.0339;
    private $baseLongitude = 101.7598;

    public function handle()
    {
        $riderId = $this->argument('rider_id');
        $dateOption = $this->option('date');

        // Parse date
        if ($dateOption === 'today') {
            $date = Carbon::today();
        } elseif ($dateOption === 'yesterday') {
            $date = Carbon::yesterday();
        } else {
            $date = Carbon::parse($dateOption);
        }

        $this->info("Generating sample travel data for Rider {$riderId} on {$date->format('Y-m-d')}");
        $this->info("Base location: Menara Simfoni Tower, Balakong (3.0339°N, 101.7598°E)");

        // Check if rider exists
        $rider = Rider::find($riderId);
        if (!$rider) {
            $this->error("Rider {$riderId} not found!");
            return 1;
        }

        // Clear existing data if requested
        if ($this->option('clear')) {
            $this->info('Clearing existing travel data...');
            LocationPoint::whereDate('recorded_at', $date)->delete();
            DutySession::whereDate('started_at', $date)->delete();
            InstallationVisit::whereDate('arrived_at', $date)->delete();
            StopRecord::whereDate('started_at', $date)->delete();
            $this->info('✓ Cleared');
        }

        DB::beginTransaction();
        try {
            // Create duty session (8 AM - 5 PM)
            $startTime = $date->copy()->setTime(8, 0);
            $endTime = $date->copy()->setTime(17, 0);

            $session = DutySession::create([
                'rider_id' => $riderId,
                'started_at' => $startTime,
                'ended_at' => $endTime,
                'status' => 'completed',
                'total_duration_minutes' => 540, // 9 hours
            ]);

            $this->info("✓ Created duty session #{$session->id}");

            // Generate realistic route with multiple points of interest
            $route = $this->generateRealisticRoute();

            $this->info("Generating {$route['points_count']} location points along route...");
            $bar = $this->output->createProgressBar($route['points_count']);
            $bar->start();

            $currentTime = $startTime->copy();
            $totalDistance = 0;
            $previousLat = null;
            $previousLng = null;

            foreach ($route['waypoints'] as $waypoint) {
                $pointsInSegment = $waypoint['points'];

                for ($i = 0; $i < $pointsInSegment; $i++) {
                    // Calculate position along segment
                    $progress = $pointsInSegment > 1 ? $i / ($pointsInSegment - 1) : 0;

                    $lat = $waypoint['from_lat'] + ($waypoint['to_lat'] - $waypoint['from_lat']) * $progress;
                    $lng = $waypoint['from_lng'] + ($waypoint['to_lng'] - $waypoint['from_lng']) * $progress;

                    // Add some randomness for realistic GPS jitter
                    $lat += (rand(-10, 10) / 100000);
                    $lng += (rand(-10, 10) / 100000);

                    // Calculate speed and distance
                    $speed = $waypoint['speed'] + rand(-10, 10) / 10; // m/s with variation
                    $accuracy = rand(5, 25); // GPS accuracy in meters

                    if ($previousLat && $previousLng) {
                        $distance = $this->calculateDistance($previousLat, $previousLng, $lat, $lng);
                        $totalDistance += $distance;
                    }

                    LocationPoint::create([
                        'rider_id' => $riderId,
                        'duty_session_id' => $session->id,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'accuracy' => $accuracy,
                        'speed' => $speed,
                        'recorded_at' => $currentTime,
                    ]);

                    $previousLat = $lat;
                    $previousLng = $lng;
                    $currentTime->addMinutes(2); // 2-minute intervals
                    $bar->advance();
                }

                // Create stop/visit if this is a POI
                if (isset($waypoint['stop_duration']) && $waypoint['stop_duration'] > 0) {
                    if (isset($waypoint['installation_id'])) {
                        // Installation visit
                        InstallationVisit::create([
                            'rider_id' => $riderId,
                            'duty_session_id' => $session->id,
                            'installation_location_id' => $waypoint['installation_id'],
                            'arrived_at' => $currentTime->copy(),
                            'departed_at' => $currentTime->copy()->addMinutes($waypoint['stop_duration']),
                            'duration_minutes' => $waypoint['stop_duration'],
                            'status' => 'completed',
                        ]);
                    } else {
                        // Regular stop
                        StopRecord::create([
                            'rider_id' => $riderId,
                            'duty_session_id' => $session->id,
                            'latitude' => $waypoint['to_lat'],
                            'longitude' => $waypoint['to_lng'],
                            'started_at' => $currentTime->copy(),
                            'ended_at' => $currentTime->copy()->addMinutes($waypoint['stop_duration']),
                            'duration_minutes' => $waypoint['stop_duration'],
                        ]);
                    }
                    $currentTime->addMinutes($waypoint['stop_duration']);
                }
            }

            $bar->finish();
            $this->newLine();

            // Update session totals
            $session->update([
                'total_distance_km' => round($totalDistance, 2),
            ]);

            DB::commit();

            $this->newLine();
            $this->info('✓ Sample travel data generated successfully!');
            $this->info("   • Location points: {$route['points_count']}");
            $this->info("   • Installation visits: {$route['visits_count']}");
            $this->info("   • Stops: {$route['stops_count']}");
            $this->info("   • Total distance: " . round($totalDistance, 2) . " km");
            $this->info("   • Duration: 9 hours");
            $this->newLine();
            $this->info('View in browser: http://172.16.0.89:7999/tracking');
            $this->info("  1. Click 'History' tab");
            $this->info("  2. Select 'Test Rider'");
            $this->info("  3. Set date to: {$date->format('Y-m-d')}");
            $this->info("  4. Click 'Apply'");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error generating data: ' . $e->getMessage());
            return 1;
        }
    }

    private function generateRealisticRoute(): array
    {
        // Create a realistic natural movement route around Balakong/Cheras area
        // This is NATURAL rider movement - NOT forced to any installations
        // Installations are visited ONLY if they happen to be along the natural route

        $waypoints = [];
        $pointsCount = 0;
        $visitsCount = 0;
        $stopsCount = 0;

        // Morning: Start from Menara Simfoni, head north towards Cheras
        $waypoints[] = [
            'from_lat' => $this->baseLatitude,
            'from_lng' => $this->baseLongitude,
            'to_lat' => 3.0420,
            'to_lng' => 101.7620,
            'points' => 12,
            'speed' => 8.3, // ~30 km/h
            'description' => 'Morning departure - heading north on Jalan Balakong',
        ];
        $pointsCount += 12;

        // Continue along main road towards Taman Connaught
        $waypoints[] = [
            'from_lat' => 3.0420,
            'from_lng' => 101.7620,
            'to_lat' => 3.0580,
            'to_lng' => 101.7680,
            'points' => 18,
            'speed' => 9.2, // ~33 km/h
            'description' => 'Moving through Taman Connaught area',
        ];
        $pointsCount += 18;

        // Turn east towards Taman Midah
        $waypoints[] = [
            'from_lat' => 3.0580,
            'from_lng' => 101.7680,
            'to_lat' => 3.0650,
            'to_lng' => 101.7820,
            'points' => 20,
            'speed' => 7.8, // ~28 km/h (traffic)
            'description' => 'Heading towards Taman Midah',
        ];
        $pointsCount += 20;

        // Quick stop at mamak for breakfast/tea break
        $waypoints[] = [
            'from_lat' => 3.0650,
            'from_lng' => 101.7820,
            'to_lat' => 3.0665,
            'to_lng' => 101.7835,
            'points' => 3,
            'speed' => 3.5, // ~12 km/h (slow, parking)
            'stop_duration' => 15, // Quick tea break
            'description' => 'Morning tea stop',
        ];
        $pointsCount += 3;
        $stopsCount++;

        // Head south towards Taman Segar
        $waypoints[] = [
            'from_lat' => 3.0665,
            'from_lng' => 101.7835,
            'to_lat' => 3.0480,
            'to_lng' => 101.7900,
            'points' => 22,
            'speed' => 8.9, // ~32 km/h
            'description' => 'Moving south to Taman Segar',
        ];
        $pointsCount += 22;

        // Continue to Sungai Long area
        $waypoints[] = [
            'from_lat' => 3.0480,
            'from_lng' => 101.7900,
            'to_lat' => 3.0280,
            'to_lng' => 101.7950,
            'points' => 25,
            'speed' => 10.5, // ~38 km/h (faster road)
            'description' => 'Approaching Sungai Long',
        ];
        $pointsCount += 25;

        // Navigate through Sungai Long township
        $waypoints[] = [
            'from_lat' => 3.0280,
            'from_lng' => 101.7950,
            'to_lat' => 3.0150,
            'to_lng' => 101.8020,
            'points' => 18,
            'speed' => 6.5, // ~23 km/h (residential area)
            'description' => 'Through Sungai Long residential',
        ];
        $pointsCount += 18;

        // Lunch break (longer stop)
        $waypoints[] = [
            'from_lat' => 3.0150,
            'from_lng' => 101.8020,
            'to_lat' => 3.0145,
            'to_lng' => 101.8035,
            'points' => 4,
            'speed' => 2.8, // ~10 km/h (very slow)
            'stop_duration' => 45, // Lunch
            'description' => 'Lunch break',
        ];
        $pointsCount += 4;
        $stopsCount++;

        // Afternoon: Head west towards Kajang
        $waypoints[] = [
            'from_lat' => 3.0145,
            'from_lng' => 101.8035,
            'to_lat' => 3.0050,
            'to_lng' => 101.7850,
            'points' => 20,
            'speed' => 9.8, // ~35 km/h
            'description' => 'Moving towards Kajang',
        ];
        $pointsCount += 20;

        // Navigate through Kajang town
        $waypoints[] = [
            'from_lat' => 3.0050,
            'from_lng' => 101.7850,
            'to_lat' => 2.9920,
            'to_lng' => 101.7780,
            'points' => 16,
            'speed' => 5.2, // ~19 km/h (town traffic)
            'description' => 'Through Kajang town center',
        ];
        $pointsCount += 16;

        // Head back north towards Semenyih Road
        $waypoints[] = [
            'from_lat' => 2.9920,
            'from_lng' => 101.7780,
            'to_lat' => 3.0180,
            'to_lng' => 101.7650,
            'points' => 28,
            'speed' => 11.2, // ~40 km/h (highway)
            'description' => 'North on Semenyih Road',
        ];
        $pointsCount += 28;

        // Final leg: Return to base (Menara Simfoni)
        $waypoints[] = [
            'from_lat' => 3.0180,
            'from_lng' => 101.7650,
            'to_lat' => $this->baseLatitude,
            'to_lng' => $this->baseLongitude,
            'points' => 20,
            'speed' => 8.5, // ~31 km/h
            'description' => 'Returning to base - Menara Simfoni',
        ];
        $pointsCount += 20;

        // NOW check if rider passed near any installations
        // This is realistic - visits happen ONLY if installation is near the natural route
        $installations = InstallationLocation::where('is_active', true)->get();

        foreach ($waypoints as &$waypoint) {
            foreach ($installations as $installation) {
                // Check if this waypoint passes near an installation (within 200m)
                $waypointLat = ($waypoint['from_lat'] + $waypoint['to_lat']) / 2;
                $waypointLng = ($waypoint['from_lng'] + $waypoint['to_lng']) / 2;

                $distance = $this->calculateDistance(
                    $waypointLat,
                    $waypointLng,
                    $installation->latitude,
                    $installation->longitude
                ) * 1000; // Convert to meters

                // If rider passed within 200m of an installation, mark it as a visit
                if ($distance < 200 && !isset($waypoint['installation_id'])) {
                    $waypoint['installation_id'] = $installation->id;
                    $waypoint['stop_duration'] = rand(15, 30); // Visit duration
                    $visitsCount++;
                    break; // Only one installation per waypoint
                }
            }
        }

        return [
            'waypoints' => $waypoints,
            'points_count' => $pointsCount,
            'visits_count' => $visitsCount,
            'stops_count' => $stopsCount,
        ];
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}
