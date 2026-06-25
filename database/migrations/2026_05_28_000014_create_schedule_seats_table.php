<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_seats', function (Blueprint $table) {
            $table->id('schedule_seat_id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('seat_id');
            $table->enum('status', ['available', 'booked', 'held', 'blocked'])->default('available');
            $table->integer('price')->default(0);

            $table->foreign('schedule_id')->references('schedule_id')->on('schedules')->onDelete('cascade');
            $table->foreign('seat_id')->references('seat_id')->on('seats')->onDelete('cascade');

            $table->unique(['schedule_id', 'seat_id']);

            $table->index(['schedule_id', 'status'], 'idx_schedule_seats_id_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_seats');
    }
};
