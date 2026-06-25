<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherSeeder extends Seeder
{
    public function run()
    {
        Voucher::insert([
            [
                'code' => 'EXPIRED10K',
                'discount_type' => 'fixed',
                'discount_value' => 10000,
                'min_order_value' => 50000,
                'max_discount_value' => 10000,
                'valid_from' => Carbon::now()->subDays(30),
                'valid_until' => Carbon::now()->subDays(5), // Đã hết hạn
                'usage_limit' => 100,
                'is_active' => true,
                'created_at' => Carbon::now()->subDays(30),
                'updated_at' => Carbon::now()->subDays(30),
            ],
            [
                'code' => 'CINEDOT50',
                'discount_type' => 'percent',
                'discount_value' => 50,
                'min_order_value' => 100000,
                'max_discount_value' => 50000,
                'valid_from' => Carbon::now()->subDays(5),
                'valid_until' => Carbon::now()->addDays(30), // Đang active
                'usage_limit' => 50,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'FUTUREVOUCHER',
                'discount_type' => 'fixed',
                'discount_value' => 20000,
                'min_order_value' => 0,
                'max_discount_value' => 20000,
                'valid_from' => Carbon::now()->addDays(10), // Chưa tới ngày
                'valid_until' => Carbon::now()->addDays(30),
                'usage_limit' => 1000,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
