<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = [
            [
                'booking_id' => 1,
                'user_id' => 3,
                'showtime_id' => 1,
                'voucher_id' => 1,
                'price_breakdown' => json_encode(['tickets' => 180000, 'combos' => 119000, 'discount' => 50000]),
                'final_amount' => 249000.00,
                'discount_amount' => 50000.00,
                'booking_status' => 'confirmed',
                'booking_code' => 'CND2026072101',
                'notes' => 'Thanh toán MoMo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('bookings')->insert($bookings);
    }
}
