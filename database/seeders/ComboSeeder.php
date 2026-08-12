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
                'description' => '1 Bắp ngọt vừa + 1 Nước ngọt có gas mát lạnh',
                'price' => 79000.00,
                'image_url' => 'https://images.unsplash.com/photo-1578849278619-e73505e9610f?w=600&auto=format&fit=crop&q=80',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'combo_id' => 2,
                'name' => 'Combo Đôi (Couple)',
                'description' => '1 Bắp ngọt lớn + 2 Nước ngọt có gas mát lạnh',
                'price' => 119000.00,
                'image_url' => 'https://images.unsplash.com/photo-1585647347483-22b66260dfff?w=600&auto=format&fit=crop&q=80',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'combo_id' => 3,
                'name' => 'Combo Gia Đình (Family Party)',
                'description' => '2 Bắp ngọt lớn + 4 Nước ngọt + 1 Gói Snack khoai tây',
                'price' => 199000.00,
                'image_url' => 'https://images.unsplash.com/photo-1512149177596-f817c7ef5d4c?w=600&auto=format&fit=crop&q=80',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'combo_id' => 4,
                'name' => 'Combo Caramel Special',
                'description' => '1 Bắp Phô Mai / Caramel thơm lừng + 2 Nước trái cây mát lạnh',
                'price' => 139000.00,
                'image_url' => 'https://images.unsplash.com/photo-1578849278619-e73505e9610f?w=600&auto=format&fit=crop&q=80',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'combo_id' => 5,
                'name' => 'Combo Snack & Coke',
                'description' => '1 Đĩa Xúc Xích / Khoai Tây Chiên nóng hổi + 1 Nước ngọt có gas',
                'price' => 69000.00,
                'image_url' => 'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?w=600&auto=format&fit=crop&q=80',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'combo_id' => 6,
                'name' => 'Combo Phô Mai Béo Ngậy',
                'description' => '1 Bắp Lắc Phô Mai đậm vị + 1 Trà Đào Hạt Chia mát rượi',
                'price' => 89000.00,
                'image_url' => 'https://images.unsplash.com/photo-1585647347483-22b66260dfff?w=600&auto=format&fit=crop&q=80',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('combos')->insertOrIgnore($combos);
    }
}
