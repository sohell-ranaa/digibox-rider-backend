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
        Schema::create('location_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained()->onDelete('cascade');
            $table->foreignId('duty_session_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->decimal('speed', 5, 2)->nullable();
            $table->decimal('bearing', 5, 2)->nullable();
            $table->decimal('altitude', 10, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->boolean('is_synced')->default(false);
            $table->timestamps();

            $table->index(['rider_id', 'recorded_at']);
            $table->index(['duty_session_id', 'recorded_at']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_points');
    }
};
