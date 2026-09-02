<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class MovieRatingSeeder extends Seeder
{
    public function run(): void
    {
        // Chạy lệnh cào và đồng bộ dữ liệu IMDb chuẩn thực tế
        Artisan::call('movies:scrape-imdb');
    }
}
