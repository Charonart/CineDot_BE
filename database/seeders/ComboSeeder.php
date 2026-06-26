<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Combo;

class ComboSeeder extends Seeder
{
    public function run(): void
    {
        $combosData = [
            ['name' => 'Combo Single', 'description' => '1 Bắp + 1 Nước', 'price' => 65000, 'image_url' => 'https://via.placeholder.com/150', 'is_active' => true],
            ['name' => 'Combo Couple', 'description' => '1 Bắp lớn + 2 Nước', 'price' => 95000, 'image_url' => 'https://via.placeholder.com/150', 'is_active' => true],
            ['name' => 'Combo Family', 'description' => '2 Bắp lớn + 4 Nước', 'price' => 175000, 'image_url' => 'https://via.placeholder.com/150', 'is_active' => true],
            ['name' => 'Combo Extra', 'description' => '1 Bắp + 1 Nước + 1 Snack', 'price' => 85000, 'image_url' => 'https://via.placeholder.com/150', 'is_active' => true],
        ];
        
        foreach ($combosData as $cd) {
            Combo::create($cd);
        }
    }
}
