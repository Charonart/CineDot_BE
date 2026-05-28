<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id('booking_seat_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('schedule_seat_id');
            $table->timestamp('created_at')->nullable();

            $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
            $table->foreign('schedule_seat_id')->references('schedule_seat_id')->on('schedule_seats')->onDelete('cascade');

            $table->unique(['booking_id', 'schedule_seat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_seats');
    }
};
