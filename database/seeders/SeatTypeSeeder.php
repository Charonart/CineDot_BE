<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatTypeSeeder extends Seeder
{
    public function run(): void
    {
        $seatTypes = [
            ['seat_type' => 'standard', 'surcharge_amount' => 0.00],
            ['seat_type' => 'vip', 'surcharge_amount' => 20000.00],
            ['seat_type' => 'couple', 'surcharge_amount' => 40000.00],
        ];

        DB::table('seat_types')->insert($seatTypes);
    }
}
