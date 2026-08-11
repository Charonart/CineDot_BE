<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $campaigns = [
            [
                'campaign_id' => 1,
                'name' => 'Chiến dịch Hè Rực Rỡ 2026',
                'start_date' => '2026-06-01 00:00:00',
                'end_date' => '2026-08-31 23:59:59',
                'budget' => 50000000.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('campaigns')->insertOrIgnore($campaigns);
    }
}
