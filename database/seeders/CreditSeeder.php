<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreditSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(base_path('cinedot_data.json'));
        $data = json_decode($json, true);
        $credits = $data['credits'] ?? [];

        if (!empty($credits)) {
            foreach (array_chunk($credits, 100) as $chunk) {
                DB::table('credits')->insertOrIgnore($chunk);
            }
        }
    }
}
