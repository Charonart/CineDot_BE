<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherSeeder extends Seeder
{
    public function run()
    {
        // 1. Expired voucher (cannot exchange or use)
        Voucher::create([
            'code' => 'EXPIRED10K',
            'discount_type' => 'fixed',
            'discount_value' => 10000,
            'min_order_value' => 50000,
            'max_discount_value' => 10000,
            'valid_from' => Carbon::now()->subDays(30),
            'valid_until' => Carbon::now()->subDays(5),
            'usage_limit' => 100,
            'is_active' => true,
            'points_cost' => null,
            'voucher_type' => 'ticket_discount',
            'combinable_rules' => null,
        ]);

        // 2. Active voucher (costs 500 points, cannot combine with other ticket discounts)
        Voucher::create([
            'code' => 'CINEDOT50',
            'discount_type' => 'percent',
            'discount_value' => 50,
            'min_order_value' => 100000,
            'max_discount_value' => 50000,
            'valid_from' => Carbon::now()->subDays(5),
            'valid_until' => Carbon::now()->addDays(30),
            'usage_limit' => 50,
            'is_active' => true,
            'points_cost' => 500,
            'voucher_type' => 'ticket_discount',
            'combinable_rules' => [
                'exclude_types' => ['ticket_discount']
            ],
        ]);

        // 3. Gift voucher (costs 300 points, cannot combine with CINEDOT50 specific code)
        Voucher::create([
            'code' => 'FREEPOPOCORN',
            'discount_type' => 'fixed',
            'discount_value' => 60000, // value of free popcorn
            'min_order_value' => 0,
            'max_discount_value' => 60000,
            'valid_from' => Carbon::now()->subDays(5),
            'valid_until' => Carbon::now()->addDays(30),
            'usage_limit' => 100,
            'is_active' => true,
            'points_cost' => 300,
            'voucher_type' => 'gift',
            'combinable_rules' => [
                'exclude_codes' => ['CINEDOT50']
            ],
        ]);

        // 4. Future voucher
        Voucher::create([
            'code' => 'FUTUREVOUCHER',
            'discount_type' => 'fixed',
            'discount_value' => 20000,
            'min_order_value' => 0,
            'max_discount_value' => 20000,
            'valid_from' => Carbon::now()->addDays(10),
            'valid_until' => Carbon::now()->addDays(30),
            'usage_limit' => 1000,
            'is_active' => true,
            'points_cost' => 200,
            'voucher_type' => 'ticket_discount',
            'combinable_rules' => null,
        ]);
    }
}
