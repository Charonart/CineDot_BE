<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'pricing_rule_id' => 1,
                'name' => 'Phụ thu cuối tuần',
                'rule_category' => 'weekend_surcharge',
                'conditions' => json_encode(['days' => ['Saturday', 'Sunday'], 'time_from' => '18:00', 'time_to' => '23:00']),
                'modifier_type' => 'fixed_amount',
                'modifier_value' => 10000.00,
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'pricing_rule_id' => 2,
                'name' => 'Giảm giá Suất chiếu sớm (Early Bird)',
                'rule_category' => 'early_bird_discount',
                'conditions' => json_encode(['days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'time_from' => '08:00', 'time_to' => '12:00']),
                'modifier_type' => 'percentage',
                'modifier_value' => -15.00,
                'priority' => 2,
                'is_active' => true,
            ],
            [
                'pricing_rule_id' => 3,
                'name' => 'Giảm giá Ngày Thứ 3 Vui Vẻ (Happy Tuesday)',
                'rule_category' => 'happy_tuesday',
                'conditions' => json_encode(['days' => ['Tuesday'], 'time_from' => '00:00', 'time_to' => '23:59']),
                'modifier_type' => 'fixed_amount',
                'modifier_value' => -20000.00,
                'priority' => 3,
                'is_active' => true,
            ],
            [
                'pricing_rule_id' => 4,
                'name' => 'Phụ thu Giờ Vàng (Prime Time)',
                'rule_category' => 'prime_time_surcharge',
                'conditions' => json_encode(['days' => ['Friday', 'Saturday', 'Sunday'], 'time_from' => '20:00', 'time_to' => '23:00']),
                'modifier_type' => 'fixed_amount',
                'modifier_value' => 15000.00,
                'priority' => 4,
                'is_active' => true,
            ],
        ];

        DB::table('pricing_rules')->insertOrIgnore($rules);
    }
}
