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
        Schema::create('system_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained()->onDelete('cascade');
            $table->foreignId('duty_session_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('event_type', ['location_on', 'location_off', 'internet_on', 'internet_off']);
            $table->timestamp('occurred_at');
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->index(['rider_id', 'occurred_at']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_events');
    }
};
