<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cinema;
use App\Models\Province;
use Illuminate\Support\Str;

class CinemaSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = Province::pluck('province_id', 'province_name')->toArray();

        $cinemasData = [
            ['name' => 'CGV Vincom Center Bà Triệu', 'province' => 'Hà Nội',           'address' => '191 Bà Triệu, Hai Bà Trưng',         'phone' => '19006017'],
            ['name' => 'Lotte Cinema Hà Nội',        'province' => 'Hà Nội',           'address' => '54 Liễu Giai, Ba Đình',              'phone' => '0243333000'],
            ['name' => 'CGV Aeon Mall Bình Dương',   'province' => 'TP. Hồ Chí Minh',  'address' => 'Aeon Mall Bình Dương',                'phone' => '19006018'],
            ['name' => 'Galaxy Nguyễn Du',          'province' => 'TP. Hồ Chí Minh',  'address' => '116 Nguyễn Du, Quận 1',              'phone' => '028393506'],
            ['name' => 'BHD Star Phạm Hùng',        'province' => 'TP. Hồ Chí Minh',  'address' => 'SC VivoCity, 1058 Nguyễn Văn Linh',  'phone' => '19002099'],
            ['name' => 'CGV Vĩnh Trung Plaza',       'province' => 'Đà Nẵng',          'address' => '255-257 Hùng Vương, Thanh Khê',     'phone' => '19006019'],
            ['name' => 'Lotte Cinema Cần Thơ',       'province' => 'Cần Thơ',          'address' => 'Mậu Thân, Xuân Khánh, Ninh Kiều',    'phone' => '029237688'],
        ];

        foreach ($cinemasData as $cd) {
            $provinceId = $provinces[$cd['province']] ?? null;
            Cinema::create([
                'cinema_name'    => $cd['name'],
                'slug'           => Str::slug($cd['name']),
                'cinema_address' => $cd['address'],
                'province_id'    => $provinceId,
                'phone'          => $cd['phone'],
                'email'          => strtolower(Str::slug($cd['name'])) . '@cinedot.vn',
                'description'    => 'Rạp chiếu phim hiện đại tiêu chuẩn quốc tế tại ' . $cd['province'],
                'is_active'      => true,
            ]);
        }
    }
}
