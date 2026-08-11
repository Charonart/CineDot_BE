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
        ];

        DB::table('pricing_rules')->insertOrIgnore($rules);
    }
}
