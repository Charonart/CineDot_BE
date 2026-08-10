<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeatSeeder extends Seeder
{
    public function run(): void
    {
        $bookingSeats = [
            [
                'booking_seat_id' => 1,
                'booking_id' => 1,
                'showtime_seat_id' => 25,
                'ticket_type' => 'adult',
                'price' => 90000.00,
                'created_at' => now(),
            ],
            [
                'booking_seat_id' => 2,
                'booking_id' => 1,
                'showtime_seat_id' => 26,
                'ticket_type' => 'adult',
                'price' => 90000.00,
                'created_at' => now(),
            ],
        ];

        DB::table('booking_seats')->insert($bookingSeats);
    }
}
