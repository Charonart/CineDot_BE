<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            ['province_id' => 1, 'province_name' => 'Hà Nội', 'province_code' => 'HN', 'created_at' => now()],
            ['province_id' => 2, 'province_name' => 'TP. Hồ Chí Minh', 'province_code' => 'HCM', 'created_at' => now()],
            ['province_id' => 3, 'province_name' => 'Đà Nẵng', 'province_code' => 'DN', 'created_at' => now()],
            ['province_id' => 4, 'province_name' => 'Hải Phòng', 'province_code' => 'HP', 'created_at' => now()],
            ['province_id' => 5, 'province_name' => 'Cần Thơ', 'province_code' => 'CT', 'created_at' => now()],
        ];

        DB::table('provinces')->insertOrIgnore($provinces);
    }
}
