<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aggregated location points for historical data
        Schema::create('aggregated_location_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained()->onDelete('cascade');
            $table->foreignId('duty_session_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('time_bucket'); // Aggregated time slot (e.g., every 10 min)
            $table->integer('point_count')->default(1); // Number of original points aggregated
            $table->float('avg_speed')->nullable();
            $table->float('avg_accuracy')->nullable();
            $table->float('distance_km')->nullable(); // Distance traveled in this bucket
            $table->timestamps();

            // Indexes for fast queries
            $table->index(['rider_id', 'time_bucket']);
            $table->index('duty_session_id');
            $table->index('time_bucket');
        });

        // Duty session summaries for long-term storage
        Schema::create('duty_session_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duty_session_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('rider_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->float('total_distance_km')->nullable();
            $table->integer('location_points_count')->default(0);
            $table->integer('installation_visits_count')->default(0);
            $table->integer('stops_count')->default(0);
            $table->json('route_polyline')->nullable(); // Simplified route coordinates
            $table->json('key_locations')->nullable(); // Important points (starts, ends, visits)
            $table->timestamps();

            $table->index(['rider_id', 'started_at']);
        });

        // Add indexes to existing tables for optimization
        Schema::table('location_points', function (Blueprint $table) {
            $table->index(['rider_id', 'recorded_at'], 'idx_rider_recorded');
            $table->index('recorded_at', 'idx_recorded_cleanup');
        });

        Schema::table('duty_sessions', function (Blueprint $table) {
            $table->index(['rider_id', 'started_at'], 'idx_rider_started');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::table('duty_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_rider_started');
            $table->dropIndex('idx_status');
        });

        Schema::table('location_points', function (Blueprint $table) {
            $table->dropIndex('idx_rider_recorded');
            $table->dropIndex('idx_recorded_cleanup');
        });

        Schema::dropIfExists('duty_session_summaries');
        Schema::dropIfExists('aggregated_location_points');
    }
};
