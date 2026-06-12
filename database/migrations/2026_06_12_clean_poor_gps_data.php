<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Clean poor quality GPS data from database
     * Remove points with accuracy > 20m or impossible jumps
     */
    public function up(): void
    {
        $totalBefore = DB::table('location_points')->count();
        echo "Total GPS points before cleanup: {$totalBefore}\n";

        // Delete points with poor accuracy (> 20 meters)
        $poorAccuracy = DB::table('location_points')
            ->where('accuracy', '>', 20)
            ->delete();
        echo "Deleted {$poorAccuracy} points with accuracy > 20m\n";

        // Delete points with 0,0 coordinates (invalid)
        $invalidCoords = DB::table('location_points')
            ->where('latitude', 0)
            ->orWhere('longitude', 0)
            ->delete();
        echo "Deleted {$invalidCoords} points with invalid coordinates\n";

        // Delete points with impossible speeds (> 100 km/h)
        $impossibleSpeed = DB::table('location_points')
            ->whereRaw('(speed * 3.6) > 100')
            ->delete();
        echo "Deleted {$impossibleSpeed} points with impossible speeds\n";

        $totalAfter = DB::table('location_points')->count();
        $totalDeleted = $totalBefore - $totalAfter;
        $percentCleaned = round(($totalDeleted / $totalBefore) * 100, 1);

        echo "\n=== Cleanup Summary ===\n";
        echo "Before: {$totalBefore} points\n";
        echo "After: {$totalAfter} points\n";
        echo "Deleted: {$totalDeleted} points ({$percentCleaned}% cleaned)\n";
    }

    public function down(): void
    {
        // Cannot restore deleted data
        echo "Warning: Cannot restore deleted GPS data\n";
    }
};
