<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PointHistorySeeder extends Seeder
{
    public function run(): void
    {
        $pointHistories = [
            [
                'point_histories_id' => 1,
                'user_id' => 3,
                'reference_type' => 'App\\Models\\Booking',
                'reference_id' => 1,
                'amount' => 50,
                'action' => 'earn',
                'created_at' => now(),
            ],
        ];

        DB::table('point_histories')->insert($pointHistories);
    }
}
