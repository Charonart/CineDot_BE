<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovieGenreSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(base_path('cinedot_data.json'));
        $data = json_decode($json, true);
        $movieGenres = $data['movie_genres'] ?? [];

        if (!empty($movieGenres)) {
            foreach (array_chunk($movieGenres, 200) as $chunk) {
                DB::table('movie_genres')->insert($chunk);
            }
        }
    }
}
