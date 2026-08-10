<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(base_path('cinedot_data.json'));
        $data = json_decode($json, true);
        $genres = $data['genres'] ?? [];

        if (!empty($genres)) {
            DB::table('genres')->insert($genres);
        }
    }
}
