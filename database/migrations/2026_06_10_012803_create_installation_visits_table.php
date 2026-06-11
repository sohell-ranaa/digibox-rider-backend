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
        Schema::create('installation_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained()->onDelete('cascade');
            $table->foreignId('duty_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('installation_location_id')->constrained()->onDelete('cascade');
            $table->timestamp('arrived_at');
            $table->timestamp('departed_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->enum('status', ['ongoing', 'completed'])->default('ongoing');
            $table->timestamps();

            $table->index(['rider_id', 'arrived_at']);
            $table->index(['installation_location_id', 'arrived_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_visits');
    }
};
