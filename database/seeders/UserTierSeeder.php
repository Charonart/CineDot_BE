<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['user_tier_id' => 1, 'tier' => 'Bronze', 'discount_percent' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['user_tier_id' => 2, 'tier' => 'Silver', 'discount_percent' => 5.00, 'created_at' => now(), 'updated_at' => now()],
            ['user_tier_id' => 3, 'tier' => 'Gold', 'discount_percent' => 10.00, 'created_at' => now(), 'updated_at' => now()],
            ['user_tier_id' => 4, 'tier' => 'Platinum', 'discount_percent' => 15.00, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('user_tiers')->insertOrIgnore($tiers);
    }
}
