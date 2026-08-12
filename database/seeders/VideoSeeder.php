<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VideoSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(database_path('data/cinedot_data.json'));
        $data = json_decode($json, true);
        $videos = $data['videos'] ?? [];

        if (!empty($videos)) {
            foreach (array_chunk($videos, 200) as $chunk) {
                DB::table('videos')->insertOrIgnore($chunk);
            }
        }
    }
}
