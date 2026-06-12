<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained()->onDelete('cascade');
            $table->foreignId('duty_session_id')->constrained()->onDelete('cascade');
            $table->timestamp('batch_start_time');
            $table->timestamp('batch_end_time');
            $table->integer('point_count')->unsigned();
            $table->decimal('total_distance_meters', 10, 2)->default(0);
            $table->decimal('avg_speed', 5, 2)->default(0);
            $table->decimal('avg_accuracy', 10, 2)->default(0);

            // JSON array of compressed GPS points
            // Format: [{"lat":3.025621,"lng":101.753138,"acc":10.5,"spd":25.2,"ts":"10:00:01"},...]
            $table->json('points');

            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['rider_id', 'batch_start_time']);
            $table->index(['duty_session_id']);
            $table->index(['batch_start_time', 'batch_end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_batches');
    }
};
