<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $vouchers = [
            [
                'voucher_id' => 1,
                'campaign_id' => 1,
                'code' => 'CINEDOT20',
                'voucher_type' => 'order',
                'discount_type' => 'percentage',
                'min_order_value' => 100000.00,
                'max_discount_value' => 50000.00,
                'valid_from' => '2026-06-01 00:00:00',
                'valid_until' => '2026-08-31 23:59:59',
                'system_limit' => 1000,
                'limit_per_user' => 1,
                'used_count' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('vouchers')->insertOrIgnore($vouchers);
    }
}
