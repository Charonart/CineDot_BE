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
            [
                'campaign_id' => 2,
                'name' => 'Chào Mừng Thành Viên Mới',
                'start_date' => '2026-01-01 00:00:00',
                'end_date' => '2026-12-31 23:59:59',
                'budget' => 30000000.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 3,
                'name' => 'Tri Ân Khách Hàng VIP',
                'start_date' => '2026-01-01 00:00:00',
                'end_date' => '2026-12-31 23:59:59',
                'budget' => 40000000.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 4,
                'name' => 'CineNight - Suất Chiếu Đêm',
                'start_date' => '2026-05-01 00:00:00',
                'end_date' => '2026-11-30 23:59:59',
                'budget' => 20000000.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('campaigns')->insertOrIgnore($campaigns);
    }
}
