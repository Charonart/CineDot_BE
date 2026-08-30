<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->index('status');
            $table->index('popularity');
        });

        Schema::table('showtimes', function (Blueprint $table) {
            $table->index('showtime_start');
            $table->index('movie_id');
            $table->index('room_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('created_at');
            $table->index('booking_status');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['popularity']);
        });

        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropIndex(['showtime_start']);
            $table->dropIndex(['movie_id']);
            $table->dropIndex(['room_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['booking_status']);
        });
    }
};
