<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(base_path('cinedot_data.json'));
        $data = json_decode($json, true);
        $persons = $data['persons'] ?? [];

        if (!empty($persons)) {
            foreach (array_chunk($persons, 100) as $chunk) {
                DB::table('persons')->insertOrIgnore($chunk);
            }
        }
    }
}
