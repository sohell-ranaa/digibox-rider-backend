<?php

namespace App\Console\Commands;

use App\Models\DutySession;
use App\Models\InstallationVisit;
use App\Models\StopRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOldData extends Command
{
    protected $signature = 'tracking:cleanup
                            {--months=3 : Number of months to keep}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete tracking data older than specified months (keeps last 3 calendar months by default)';

    public function handle()
    {
        $monthsToKeep = $this->option('months');
        $dryRun = $this->option('dry-run');

        // Calculate cutoff date (3 months ago from start of current month)
        $cutoffDate = Carbon::now()->startOfMonth()->subMonths($monthsToKeep - 1);

        $this->info("🗑️  Cleanup Old Tracking Data");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Keeping data from: {$cutoffDate->format('Y-m-01')} onwards");
        $this->info("Deleting data older than: {$cutoffDate->format('Y-m-d')}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be deleted');
            $this->newLine();
        }

        // Count records to be deleted
        $locationBatchesCount = DB::table('location_batches')->where('batch_start_time', '<', $cutoffDate)->count();
        $stopRecordsCount = StopRecord::where('started_at', '<', $cutoffDate)->count();
        $visitsCount = InstallationVisit::where('arrived_at', '<', $cutoffDate)->count();
        $sessionsCount = DutySession::where('started_at', '<', $cutoffDate)->count();

        $this->table(
            ['Table', 'Records to Delete', 'Status'],
            [
                ['location_batches', number_format($locationBatchesCount), $locationBatchesCount > 0 ? '⚠️' : '✅'],
                ['stop_records', number_format($stopRecordsCount), $stopRecordsCount > 0 ? '⚠️' : '✅'],
                ['installation_visits', number_format($visitsCount), $visitsCount > 0 ? '⚠️' : '✅'],
                ['duty_sessions', number_format($sessionsCount), $sessionsCount > 0 ? '⚠️' : '✅'],
            ]
        );

        $totalRecords = $locationBatchesCount + $stopRecordsCount + $visitsCount + $sessionsCount;

        if ($totalRecords === 0) {
            $this->info('✅ No old data to clean up. Database is already optimized!');
            return 0;
        }

        $this->newLine();
        $this->warn("Total records to delete: " . number_format($totalRecords));

        if ($dryRun) {
            $this->info('✓ Dry run complete. Run without --dry-run to actually delete data.');
            return 0;
        }

        // Ask for confirmation
        if (!$this->confirm('Do you want to proceed with deletion?', false)) {
            $this->info('Cancelled.');
            return 0;
        }

        $this->newLine();
        $this->info('🗑️  Starting cleanup...');

        DB::beginTransaction();
        try {
            $bar = $this->output->createProgressBar(4);
            $bar->start();

            // Delete location batches
            DB::table('location_batches')->where('batch_start_time', '<', $cutoffDate)->delete();
            $bar->advance();

            // Delete stop records
            StopRecord::where('started_at', '<', $cutoffDate)->delete();
            $bar->advance();

            // Delete installation visits
            InstallationVisit::where('arrived_at', '<', $cutoffDate)->delete();
            $bar->advance();

            // Delete duty sessions
            DutySession::where('started_at', '<', $cutoffDate)->delete();
            $bar->advance();

            $bar->finish();
            $this->newLine(2);

            DB::commit();

            $this->info('✅ Cleanup completed successfully!');
            $this->newLine();
            $this->info("Deleted:");
            $this->line("  • Location batches: " . number_format($locationBatchesCount));
            $this->line("  • Stop records: " . number_format($stopRecordsCount));
            $this->line("  • Installation visits: " . number_format($visitsCount));
            $this->line("  • Duty sessions: " . number_format($sessionsCount));
            $this->newLine();

            // Optimize tables
            $this->info('🔧 Optimizing database tables...');
            DB::statement('OPTIMIZE TABLE location_batches');
            DB::statement('OPTIMIZE TABLE stop_records');
            DB::statement('OPTIMIZE TABLE installation_visits');
            DB::statement('OPTIMIZE TABLE duty_sessions');
            $this->info('✅ Database optimized!');

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during cleanup: ' . $e->getMessage());
            return 1;
        }
    }
}
