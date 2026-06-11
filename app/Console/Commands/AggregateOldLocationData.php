<?php

namespace App\Console\Commands;

use App\Models\LocationPoint;
use App\Models\AggregatedLocationPoint;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateOldLocationData extends Command
{
    protected $signature = 'location:aggregate {--days=7 : Days old to aggregate} {--interval=10 : Aggregation interval in minutes}';
    protected $description = 'Aggregate old location points to reduce database size';

    public function handle()
    {
        $daysOld = $this->option('days');
        $interval = $this->option('interval');
        $cutoffDate = Carbon::now()->subDays($daysOld);

        $this->info("Aggregating location points older than {$daysOld} days (before {$cutoffDate->format('Y-m-d H:i:s')})");
        $this->info("Aggregation interval: {$interval} minutes");

        // Get count before
        $beforeCount = LocationPoint::where('recorded_at', '<', $cutoffDate)->count();
        $this->info("Found {$beforeCount} points to aggregate");

        if ($beforeCount === 0) {
            $this->info('No data to aggregate');
            return 0;
        }

        DB::beginTransaction();
        try {
            // Aggregate points by time buckets
            $aggregated = DB::table('location_points')
                ->select(
                    'rider_id',
                    'duty_session_id',
                    DB::raw("AVG(latitude) as latitude"),
                    DB::raw("AVG(longitude) as longitude"),
                    DB::raw("DATE_FORMAT(
                        FROM_UNIXTIME(
                            FLOOR(UNIX_TIMESTAMP(recorded_at) / ({$interval} * 60)) * ({$interval} * 60)
                        ),
                        '%Y-%m-%d %H:%i:00'
                    ) as time_bucket"),
                    DB::raw("COUNT(*) as point_count"),
                    DB::raw("AVG(speed) as avg_speed"),
                    DB::raw("AVG(accuracy) as avg_accuracy")
                )
                ->where('recorded_at', '<', $cutoffDate)
                ->groupBy('rider_id', 'duty_session_id', 'time_bucket')
                ->get();

            $this->info("Created {$aggregated->count()} aggregated buckets");

            // Insert aggregated data
            $bar = $this->output->createProgressBar($aggregated->count());
            $bar->start();

            foreach ($aggregated as $agg) {
                DB::table('aggregated_location_points')->insert([
                    'rider_id' => $agg->rider_id,
                    'duty_session_id' => $agg->duty_session_id,
                    'latitude' => $agg->latitude,
                    'longitude' => $agg->longitude,
                    'time_bucket' => $agg->time_bucket,
                    'point_count' => $agg->point_count,
                    'avg_speed' => $agg->avg_speed,
                    'avg_accuracy' => $agg->avg_accuracy,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Delete original points after aggregation
            if ($this->confirm('Delete original aggregated points?', true)) {
                $deleted = LocationPoint::where('recorded_at', '<', $cutoffDate)->delete();
                $this->info("Deleted {$deleted} original location points");
            }

            DB::commit();

            $this->info('✓ Aggregation completed successfully');
            $this->info("Data reduction: {$beforeCount} → {$aggregated->count()} (" .
                round((($beforeCount - $aggregated->count()) / $beforeCount) * 100, 1) . "% reduction)");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error aggregating data: ' . $e->getMessage());
            return 1;
        }
    }
}
