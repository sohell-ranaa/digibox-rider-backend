<?php

namespace App\Console\Commands;

use App\Models\DutySession;
use App\Models\LocationPoint;
use App\Models\InstallationVisit;
use App\Models\StopRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDutySessionSummaries extends Command
{
    protected $signature = 'session:summarize {--days=90 : Days old to summarize}';
    protected $description = 'Create summaries for old duty sessions and archive detailed data';

    public function handle()
    {
        $daysOld = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($daysOld);

        $this->info("Creating summaries for duty sessions older than {$daysOld} days");

        // Get completed sessions older than cutoff
        $sessions = DutySession::where('status', 'completed')
            ->where('started_at', '<', $cutoffDate)
            ->whereDoesntHave('summary')
            ->with(['locationPoints', 'installationVisits', 'stopRecords'])
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('No sessions to summarize');
            return 0;
        }

        $this->info("Found {$sessions->count()} sessions to summarize");

        $bar = $this->output->createProgressBar($sessions->count());
        $bar->start();

        $summarized = 0;
        $pointsRemoved = 0;

        foreach ($sessions as $session) {
            DB::beginTransaction();
            try {
                // Get all location points for this session
                $points = $session->locationPoints()
                    ->orderBy('recorded_at', 'asc')
                    ->get();

                // Create simplified polyline (keep every 10th point + start/end)
                $simplifiedRoute = [];
                foreach ($points as $index => $point) {
                    if ($index === 0 || $index === $points->count() - 1 || $index % 10 === 0) {
                        $simplifiedRoute[] = [
                            'lat' => (float) $point->latitude,
                            'lng' => (float) $point->longitude,
                            'time' => $point->recorded_at->format('H:i'),
                        ];
                    }
                }

                // Get key locations (visits and stops)
                $keyLocations = [];

                foreach ($session->installationVisits as $visit) {
                    $keyLocations[] = [
                        'type' => 'visit',
                        'name' => $visit->installationLocation->name ?? 'Unknown',
                        'lat' => (float) $visit->installationLocation->latitude,
                        'lng' => (float) $visit->installationLocation->longitude,
                        'arrived_at' => $visit->arrived_at->format('Y-m-d H:i:s'),
                        'duration' => $visit->duration_minutes,
                    ];
                }

                foreach ($session->stopRecords as $stop) {
                    $keyLocations[] = [
                        'type' => 'stop',
                        'lat' => (float) $stop->latitude,
                        'lng' => (float) $stop->longitude,
                        'started_at' => $stop->started_at->format('Y-m-d H:i:s'),
                        'duration' => $stop->duration_minutes,
                    ];
                }

                // Create summary
                DB::table('duty_session_summaries')->insert([
                    'duty_session_id' => $session->id,
                    'rider_id' => $session->rider_id,
                    'started_at' => $session->started_at,
                    'ended_at' => $session->ended_at,
                    'duration_minutes' => $session->total_duration_minutes,
                    'total_distance_km' => $session->total_distance_km,
                    'location_points_count' => $points->count(),
                    'installation_visits_count' => $session->installationVisits->count(),
                    'stops_count' => $session->stopRecords->count(),
                    'route_polyline' => json_encode($simplifiedRoute),
                    'key_locations' => json_encode($keyLocations),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Delete detailed location points
                $deleted = LocationPoint::where('duty_session_id', $session->id)->delete();
                $pointsRemoved += $deleted;

                $summarized++;
                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("\nError summarizing session {$session->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("✓ Summarized {$summarized} duty sessions");
        $this->info("✓ Removed {$pointsRemoved} detailed location points");
        $this->info("Data reduction: ~" . round(($pointsRemoved / ($pointsRemoved + $summarized * 20)) * 100, 1) . "%");

        return 0;
    }
}
