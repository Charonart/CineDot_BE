<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComboSeeder extends Seeder
{
    public function run(): void
    {
        $combos = [
            [
                'combo_id' => 1,
                'name' => 'Combo Solo',
                'description' => '1 Bắp ngọt vừa + 1 Nước ngọt có gas',
                'price' => 79000.00,
                'image_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'combo_id' => 2,
                'name' => 'Combo Đôi (Couple)',
                'description' => '1 Bắp ngọt lớn + 2 Nước ngọt có gas',
                'price' => 119000.00,
                'image_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('combos')->insertOrIgnore($combos);
    }
}
