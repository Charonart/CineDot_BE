<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(database_path('data/cinedot_data.json'));
        $data = json_decode($json, true);
        $movies = $data['movies'] ?? [];

        if (!empty($movies)) {
            foreach (array_chunk($movies, 100) as $chunk) {
                DB::table('movies')->insertOrIgnore($chunk);
            }
        }
    }
}
