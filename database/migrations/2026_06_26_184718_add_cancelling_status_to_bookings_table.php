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
        // Drop the old check constraint on PostgreSQL and add the new one including 'cancelling'
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_booking_status_check");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_booking_status_check CHECK (booking_status::text IN ('pending', 'confirmed', 'cancelled', 'completed', 'cancelling'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_booking_status_check");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_booking_status_check CHECK (booking_status::text IN ('pending', 'confirmed', 'cancelled', 'completed'))");
    }
};
