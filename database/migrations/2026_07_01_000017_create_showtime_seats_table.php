<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showtime_seats', function (Blueprint $table) {
            $table->id('showtime_seat_id');
            $table->foreignId('showtime_id')->constrained('showtimes', 'showtime_id')->cascadeOnDelete();
            $table->foreignId('seat_id')->constrained('seats', 'seat_id')->cascadeOnDelete();
            $table->enum('status', ['available', 'selecting', 'holding', 'booked', 'blocked'])->default('available');

            // Performance Indexes
            $table->unique(['showtime_id', 'seat_id']);
            $table->index(['showtime_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtime_seats');
    }
};
