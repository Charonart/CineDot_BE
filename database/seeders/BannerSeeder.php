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
                'title' => 'Đại Tiệc Bom Tấn Hè 2026 - Giảm 20% Toàn Hệ Thống',
                'image_url' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=1600&auto=format&fit=crop',
                'link_url' => '/booking/seats?showtime_id=1',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'banner_id' => 2,
                'campaign_id' => 2,
                'title' => 'Chào Đón Tân Hội Viên - Tặng Ngay Voucher 50K',
                'image_url' => 'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?q=80&w=1600&auto=format&fit=crop',
                'link_url' => '/auth/register',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'banner_id' => 3,
                'campaign_id' => 3,
                'title' => 'Đặc Quyền Hội Viên VIP CineDot - Trải Nghiệm IMAX Laser',
                'image_url' => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?q=80&w=1600&auto=format&fit=crop',
                'link_url' => '/movies',
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'banner_id' => 4,
                'campaign_id' => 4,
                'title' => 'CineNight - Suất Chiếu Nửa Đêm Cùng Combo Bắp Nước Ưu Đãi',
                'image_url' => 'https://images.unsplash.com/photo-1574267432553-4b4628081c31?q=80&w=1600&auto=format&fit=crop',
                'link_url' => '/movies',
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($banners as $b) {
            DB::table('banners')->updateOrInsert(
                ['banner_id' => $b['banner_id']],
                $b
            );
        }
    }
}
