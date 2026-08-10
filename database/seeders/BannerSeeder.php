<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'banner_id' => 1,
                'campaign_id' => 1,
                'title' => 'Khuyến Mãi Vé Hè 2026',
                'image_url' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba',
                'link_url' => '/promotions/summer-2026',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('banners')->insert($banners);
    }
}
