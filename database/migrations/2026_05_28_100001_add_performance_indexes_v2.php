<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index cho bảng schedules – truy vấn theo ngày, phim, phòng chiếu
        Schema::table('schedules', function (Blueprint $table) {
            $table->index('schedule_date',              'idx_schedules_date');
            $table->index('movie_id',                   'idx_schedules_movie_id');
            $table->index('room_id',                    'idx_schedules_room_id');
            // Composite index cho query phổ biến: schedule_date + movie_id
            $table->index(['schedule_date', 'movie_id'], 'idx_schedules_date_movie');
        });

        // Index cho bảng schedule_seats – withCount available_seats
        Schema::table('schedule_seats', function (Blueprint $table) {
            // Composite index: schedule_id + status (dùng cho WHERE status='available')
            $table->index(['schedule_id', 'status'], 'idx_schedule_seats_id_status');
        });

        // Index cho bảng credits – query theo movie_id + credit_type
        Schema::table('credits', function (Blueprint $table) {
            $table->index('movie_id',                      'idx_credits_movie_id');
            $table->index(['movie_id', 'credit_type'],     'idx_credits_movie_type');
        });

        // Index cho bảng cinemas – province_id
        Schema::table('cinemas', function (Blueprint $table) {
            $table->index('province_id', 'idx_cinemas_province_id');
        });

        // Index cho bảng rooms – cinema_id
        Schema::table('rooms', function (Blueprint $table) {
            $table->index('cinema_id', 'idx_rooms_cinema_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_date');
            $table->dropIndex('idx_schedules_movie_id');
            $table->dropIndex('idx_schedules_room_id');
            $table->dropIndex('idx_schedules_date_movie');
        });

        Schema::table('schedule_seats', function (Blueprint $table) {
            $table->dropIndex('idx_schedule_seats_id_status');
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->dropIndex('idx_credits_movie_id');
            $table->dropIndex('idx_credits_movie_type');
        });

        Schema::table('cinemas', function (Blueprint $table) {
            $table->dropIndex('idx_cinemas_province_id');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('idx_rooms_cinema_id');
        });
    }
};
