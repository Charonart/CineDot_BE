<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingComboSeeder extends Seeder
{
    public function run(): void
    {
        $bookingCombos = [
            [
                'booking_combo_id' => 1,
                'booking_id' => 1,
                'combo_id' => 2,
                'quantity' => 1,
                'price_at_booking' => 119000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('booking_combos')->insertOrIgnore($bookingCombos);
    }
}
