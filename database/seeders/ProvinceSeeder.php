<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Province;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinceData = [
            ['name' => 'Hà Nội', 'code' => 'HN'],
            ['name' => 'TP. Hồ Chí Minh', 'code' => 'HCM'],
            ['name' => 'Đà Nẵng', 'code' => 'DN'],
            ['name' => 'Cần Thơ', 'code' => 'CT'],
            ['name' => 'Hải Phòng', 'code' => 'HP'],
            ['name' => 'Huế', 'code' => 'HUE'],
            ['name' => 'Biên Hoà', 'code' => 'BH'],
            ['name' => 'Vũng Tàu', 'code' => 'VT'],
            ['name' => 'Nha Trang', 'code' => 'NT'],
        ];
        
        foreach ($provinceData as $p) {
            Province::create([
                'province_name' => $p['name'],
                'province_code' => $p['code'],
            ]);
        }
    }
}
