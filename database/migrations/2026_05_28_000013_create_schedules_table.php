<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->unsignedBigInteger('movie_id');
            $table->unsignedBigInteger('room_id');
            $table->date('schedule_date');
            $table->time('schedule_start');
            $table->time('schedule_end');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('base_price')->default(0);

            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
            $table->foreign('room_id')->references('room_id')->on('rooms')->onDelete('cascade');

            $table->index('schedule_date', 'idx_schedules_date');
            $table->index('movie_id', 'idx_schedules_movie_id');
            $table->index('room_id', 'idx_schedules_room_id');
            $table->index(['schedule_date', 'movie_id'], 'idx_schedules_date_movie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
