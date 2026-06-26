<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banner;

class BannerSeeder extends Seeder
{
    public function run()
    {
        Banner::create([
            'title'     => 'Xem phim cực đã - Nhận quà thả ga',
            'image_url' => 'https://example.com/banners/banner1.jpg',
            'link_url'  => 'https://example.com/promo/p1',
            'order'     => 1,
            'is_active' => true,
        ]);

        Banner::create([
            'title'     => 'Combo bắp nước siêu hời',
            'image_url' => 'https://example.com/banners/banner2.jpg',
            'link_url'  => 'https://example.com/promo/p2',
            'order'     => 2,
            'is_active' => true,
        ]);

        Banner::create([
            'title'     => 'Cuối tuần rộn ràng - Nhận ngàn voucher',
            'image_url' => 'https://example.com/banners/banner3.jpg',
            'link_url'  => 'https://example.com/promo/p3',
            'order'     => 3,
            'is_active' => true,
        ]);

        Banner::create([
            'title'     => 'Banner ẩn (Không active)',
            'image_url' => 'https://example.com/banners/banner4.jpg',
            'link_url'  => 'https://example.com/promo/p4',
            'order'     => 4,
            'is_active' => false,
        ]);
    }
}
