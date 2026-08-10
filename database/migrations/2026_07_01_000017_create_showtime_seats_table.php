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
            $table->string('seat_type');
            $table->foreign('seat_type')->references('seat_type')->on('seat_types')->onDelete('cascade');
            $table->string('row_name', 10);
            $table->string('seat_number', 10);
            $table->enum('status', ['available', 'holding', 'booked', 'blocked'])->default('available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtime_seats');
    }
};
