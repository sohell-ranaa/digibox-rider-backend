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

class GenerateRealisticWorkDay extends Command
{
    protected $signature = 'work:generate
                            {rider_id=1 : Rider ID}
                            {--date=today : Date (today, yesterday, or Y-m-d)}
                            {--clear : Clear existing data first}';

    protected $description = 'Generate realistic work day with installations visits and breaks';

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

        $this->info("🚀 Generating realistic work day for Rider {$riderId} on {$date->format('Y-m-d')}");

        // Check if rider exists
        $rider = Rider::find($riderId);
        if (!$rider) {
            $this->error("Rider {$riderId} not found!");
            return 1;
        }

        // Clear existing data if requested
        if ($this->option('clear')) {
            $this->info('🗑️  Clearing existing data...');
            LocationPoint::where('rider_id', $riderId)->whereDate('recorded_at', $date)->delete();
            $sessions = DutySession::where('rider_id', $riderId)->whereDate('started_at', $date)->get();
            foreach ($sessions as $session) {
                InstallationVisit::where('duty_session_id', $session->id)->delete();
                StopRecord::where('duty_session_id', $session->id)->delete();
            }
            DutySession::where('rider_id', $riderId)->whereDate('started_at', $date)->delete();
            $this->info('✓ Cleared');
        }

        DB::beginTransaction();
        try {
            // Create duty session (8:10 AM - 2:00 PM = 5 hours 50 minutes)
            $startTime = $date->copy()->setTime(8, 10);
            $endTime = $date->copy()->setTime(14, 0);
            $totalMinutes = $startTime->diffInMinutes($endTime);

            $session = DutySession::create([
                'rider_id' => $riderId,
                'started_at' => $startTime,
                'ended_at' => $endTime,
                'status' => 'completed',
                'total_duration_minutes' => $totalMinutes,
            ]);

            $this->info("✓ Created duty session #{$session->id} ({$totalMinutes} minutes)");

            // Get installations
            $installations = [
                'tesco' => InstallationLocation::where('name', 'LIKE', '%Tesco%')->first(),
                'sunway' => InstallationLocation::where('name', 'LIKE', '%Sunway%')->first(),
                'kfc' => InstallationLocation::where('name', 'LIKE', '%KFC%')->first(),
                'mines' => InstallationLocation::where('name', 'LIKE', '%MINES%')->first(),
                'southcity' => InstallationLocation::where('name', 'LIKE', '%South City%')->first(),
            ];

            // Story timeline
            $story = $this->buildStory($date, $session, $installations);

            $this->newLine();
            $this->info('📖 Work Day Story:');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $totalDistance = 0;
            $pointsCount = 0;

            foreach ($story as $event) {
                $this->line("⏰ {$event['time']->format('h:i A')} - {$event['description']}");

                // Generate location points for this segment
                $points = $this->generateLocationPoints(
                    $riderId,
                    $session->id,
                    $event['from_lat'],
                    $event['from_lng'],
                    $event['to_lat'],
                    $event['to_lng'],
                    $event['time'],
                    $event['duration'],
                    $event['type']
                );

                $pointsCount += count($points);

                // Calculate distance
                if ($event['type'] === 'travel') {
                    $distance = $this->calculateDistance(
                        $event['from_lat'],
                        $event['from_lng'],
                        $event['to_lat'],
                        $event['to_lng']
                    );
                    $totalDistance += $distance;
                }

                // Create stop record
                if ($event['type'] === 'break') {
                    StopRecord::create([
                        'rider_id' => $riderId,
                        'duty_session_id' => $session->id,
                        'latitude' => $event['to_lat'],
                        'longitude' => $event['to_lng'],
                        'started_at' => $event['time'],
                        'ended_at' => $event['time']->copy()->addMinutes($event['duration']),
                        'duration_minutes' => $event['duration'],
                    ]);
                }

                // Create installation visit
                if ($event['type'] === 'visit' && $event['installation']) {
                    InstallationVisit::create([
                        'rider_id' => $riderId,
                        'duty_session_id' => $session->id,
                        'installation_location_id' => $event['installation']->id,
                        'arrived_at' => $event['time'],
                        'departed_at' => $event['time']->copy()->addMinutes($event['duration']),
                        'duration_minutes' => $event['duration'],
                        'status' => 'completed',
                    ]);
                }
            }

            // Update session totals
            $session->update([
                'total_distance_km' => round($totalDistance, 2),
            ]);

            DB::commit();

            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
            $this->info('✅ Work day generated successfully!');
            $this->info("   📍 Location points: {$pointsCount}");
            $this->info("   🏢 Installation visits: " . InstallationVisit::where('duty_session_id', $session->id)->count());
            $this->info("   ☕ Breaks/Stops: " . StopRecord::where('duty_session_id', $session->id)->count());
            $this->info("   📏 Total distance: " . round($totalDistance, 2) . " km");
            $this->info("   ⏱️  Total time: {$totalMinutes} minutes (5h 50m)");
            $this->newLine();
            $this->info('🌐 View in browser: http://172.16.0.89:7999/tracking');
            $this->info("   1. Select 'Test Rider'");
            $this->info("   2. Set date to: {$date->format('Y-m-d')}");
            $this->info("   3. Click 'Load'");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function buildStory($date, $session, $installations)
    {
        $story = [];

        // Base location: Menara Simfoni Tower
        $baseLatitude = 3.0339;
        $baseLongitude = 101.7598;

        $currentTime = $date->copy()->setTime(8, 10);
        $currentLat = $baseLatitude;
        $currentLng = $baseLongitude;

        // 1. Start duty
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🟢 Started Duty at Menara Simfoni Tower',
            'type' => 'start',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $currentLat,
            'to_lng' => $currentLng,
            'duration' => 0,
            'installation' => null,
        ];

        // 2. Travel to Tesco
        $currentTime->addMinutes(5);
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🚗 Traveling to Tesco Extra Cheras Selatan',
            'type' => 'travel',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $installations['tesco']->latitude,
            'to_lng' => $installations['tesco']->longitude,
            'duration' => 20,
            'installation' => null,
        ];
        $currentLat = $installations['tesco']->latitude;
        $currentLng = $installations['tesco']->longitude;
        $currentTime->addMinutes(20);

        // 3. Work at Tesco
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🛠️  Working at Tesco - POS System Upgrade',
            'type' => 'visit',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $currentLat,
            'to_lng' => $currentLng,
            'duration' => 35,
            'installation' => $installations['tesco'],
            'work_done' => 'Upgraded POS terminal software, tested payment gateway, trained staff on new features',
        ];
        $currentTime->addMinutes(35);

        // 4. Travel to Sunway College
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🚗 Traveling to Sunway College',
            'type' => 'travel',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $installations['sunway']->latitude,
            'to_lng' => $installations['sunway']->longitude,
            'duration' => 25,
            'installation' => null,
        ];
        $currentLat = $installations['sunway']->latitude;
        $currentLng = $installations['sunway']->longitude;
        $currentTime->addMinutes(25);

        // 5. Work at Sunway
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🛠️  Working at Sunway College - Network Maintenance',
            'type' => 'visit',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $currentLat,
            'to_lng' => $currentLng,
            'duration' => 25,
            'installation' => $installations['sunway'],
            'work_done' => 'Performed network switch maintenance, updated firmware, checked WiFi coverage in classrooms',
        ];
        $currentTime->addMinutes(25);

        // 6. Coffee break
        $breakLat = 3.0650;
        $breakLng = 101.7500;
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '☕ Coffee Break',
            'type' => 'break',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $breakLat,
            'to_lng' => $breakLng,
            'duration' => 10,
            'installation' => null,
        ];
        $currentLat = $breakLat;
        $currentLng = $breakLng;
        $currentTime->addMinutes(10);

        // 7. Travel to KFC
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🚗 Traveling to KFC Balakong',
            'type' => 'travel',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $installations['kfc']->latitude,
            'to_lng' => $installations['kfc']->longitude,
            'duration' => 15,
            'installation' => null,
        ];
        $currentLat = $installations['kfc']->latitude;
        $currentLng = $installations['kfc']->longitude;
        $currentTime->addMinutes(15);

        // 8. Work at KFC
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🛠️  Working at KFC - POS System Troubleshooting',
            'type' => 'visit',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $currentLat,
            'to_lng' => $currentLng,
            'duration' => 20,
            'installation' => $installations['kfc'],
            'work_done' => 'Fixed POS terminal connection issue, replaced faulty ethernet cable, verified order system',
        ];
        $currentTime->addMinutes(20);

        // 9. Travel to MINES
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🚗 Traveling to MINES Shopping Mall',
            'type' => 'travel',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $installations['mines']->latitude,
            'to_lng' => $installations['mines']->longitude,
            'duration' => 30,
            'installation' => null,
        ];
        $currentLat = $installations['mines']->latitude;
        $currentLng = $installations['mines']->longitude;
        $currentTime->addMinutes(30);

        // 10. Work at MINES
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🛠️  Working at MINES - Digital Signage Installation',
            'type' => 'visit',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $currentLat,
            'to_lng' => $currentLng,
            'duration' => 45,
            'installation' => $installations['mines'],
            'work_done' => 'Installed new digital signage displays, configured content management system, tested remote updates',
        ];
        $currentTime->addMinutes(45);

        // 11. Lunch break
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🍽️  Lunch Break',
            'type' => 'break',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $currentLat,
            'to_lng' => $currentLng,
            'duration' => 45,
            'installation' => null,
        ];
        $currentTime->addMinutes(45);

        // 12. Travel to South City Plaza
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🚗 Traveling to South City Plaza',
            'type' => 'travel',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $installations['southcity']->latitude,
            'to_lng' => $installations['southcity']->longitude,
            'duration' => 15,
            'installation' => null,
        ];
        $currentLat = $installations['southcity']->latitude;
        $currentLng = $installations['southcity']->longitude;
        $currentTime->addMinutes(15);

        // 13. Work at South City Plaza
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🛠️  Working at South City Plaza - Network Infrastructure Check',
            'type' => 'visit',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $currentLat,
            'to_lng' => $currentLng,
            'duration' => 30,
            'installation' => $installations['southcity'],
            'work_done' => 'Performed network infrastructure health check, verified backup systems, documented equipment status',
        ];
        $currentTime->addMinutes(30);

        // 14. Travel back to base
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🚗 Returning to Menara Simfoni Tower',
            'type' => 'travel',
            'from_lat' => $currentLat,
            'from_lng' => $currentLng,
            'to_lat' => $baseLatitude,
            'to_lng' => $baseLongitude,
            'duration' => 25,
            'installation' => null,
        ];
        $currentTime->addMinutes(25);

        // 15. End duty
        $story[] = [
            'time' => $currentTime->copy(),
            'description' => '🔴 Ended Duty at Menara Simfoni Tower',
            'type' => 'end',
            'from_lat' => $baseLatitude,
            'from_lng' => $baseLongitude,
            'to_lat' => $baseLatitude,
            'to_lng' => $baseLongitude,
            'duration' => 0,
            'installation' => null,
        ];

        return $story;
    }

    private function generateLocationPoints($riderId, $sessionId, $fromLat, $fromLng, $toLat, $toLng, $startTime, $duration, $type)
    {
        $points = [];

        if ($type === 'start' || $type === 'end') {
            // Single point
            LocationPoint::create([
                'rider_id' => $riderId,
                'duty_session_id' => $sessionId,
                'latitude' => $toLat,
                'longitude' => $toLng,
                'accuracy' => rand(5, 15),
                'speed' => 0,
                'recorded_at' => $startTime,
            ]);
            return [1];
        }

        if ($type === 'visit' || $type === 'break') {
            // Points every 5 minutes during visit/break (stationary)
            $numPoints = max(1, floor($duration / 5));
            for ($i = 0; $i < $numPoints; $i++) {
                LocationPoint::create([
                    'rider_id' => $riderId,
                    'duty_session_id' => $sessionId,
                    'latitude' => $toLat + (rand(-5, 5) / 100000), // Minor GPS jitter
                    'longitude' => $toLng + (rand(-5, 5) / 100000),
                    'accuracy' => rand(5, 20),
                    'speed' => rand(0, 2) / 10,
                    'recorded_at' => $startTime->copy()->addMinutes($i * 5),
                ]);
                $points[] = 1;
            }
            return $points;
        }

        if ($type === 'travel') {
            // Points every 2 minutes during travel (GPS collection interval)
            $numPoints = max(1, floor($duration / 2));
            for ($i = 0; $i <= $numPoints; $i++) {
                $progress = $numPoints > 0 ? $i / $numPoints : 0;

                // Simple linear interpolation (AI will enhance this on frontend)
                $lat = $fromLat + ($toLat - $fromLat) * $progress;
                $lng = $fromLng + ($toLng - $fromLng) * $progress;

                // Add realistic GPS jitter (±10-20 meters)
                $lat += (rand(-15, 15) / 100000);
                $lng += (rand(-15, 15) / 100000);

                LocationPoint::create([
                    'rider_id' => $riderId,
                    'duty_session_id' => $sessionId,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'accuracy' => rand(8, 25),
                    'speed' => rand(5, 15), // 5-15 m/s (18-54 km/h)
                    'recorded_at' => $startTime->copy()->addMinutes($i * 2),
                ]);
                $points[] = 1;
            }
            return $points;
        }

        return $points;
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
