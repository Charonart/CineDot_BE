<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CinemaSeeder extends Seeder
{
    public function run(): void
    {
        $cinemas = [
            [
                'cinema_id' => 1,
                'province_id' => 1,
                'cinema_name' => 'CineDot Vincom Bà Triệu',
                'slug' => 'cinedot-vincom-ba-trieu',
                'cinema_address' => 'Tầng 6 Vincom Center, 191 Bà Triệu, Hai Bà Trưng, Hà Nội',
                'phone' => '02439748888',
                'email' => 'batrieu@cinedot.com',
                'description' => 'Rạp phim hiện đại tại trung tâm Hà Nội với dàn âm thanh Dolby Atmos.',
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'cinema_id' => 2,
                'province_id' => 2,
                'cinema_name' => 'CineDot Landmark 81',
                'slug' => 'cinedot-landmark-81',
                'cinema_address' => 'Tầng B1 Landmark 81, 720A Điện Biên Phủ, Bình Thạnh, TP.HCM',
                'phone' => '02839158888',
                'email' => 'landmark81@cinedot.com',
                'description' => 'Cụm rạp cao cấp tích hợp màn hình IMAX lớn nhất TP.HCM.',
                'is_active' => true,
                'created_at' => now(),
            ],
        ];

        DB::table('cinemas')->insert($cinemas);
    }
}
