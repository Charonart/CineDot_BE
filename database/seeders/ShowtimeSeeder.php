<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        $movieIds = DB::table('movies')->pluck('movie_id')->toArray();
        if (empty($movieIds)) {
            return;
        }

        $room1Matrix = DB::table('rooms')->where('room_id', 1)->value('seat_matrix');
        $room2Matrix = DB::table('rooms')->where('room_id', 2)->value('seat_matrix');

        $movie1 = $movieIds[0];
        $movie2 = isset($movieIds[1]) ? $movieIds[1] : $movie1;

        $showtimes = [
            [
                'showtime_id' => 1,
                'room_id' => 1,
                'movie_id' => $movie1,
                'showtime_start' => '2026-07-25 18:00:00',
                'showtime_end' => '2026-07-25 20:10:00',
                'layout_snaps' => $room1Matrix,
                'base_price' => 90000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'showtime_id' => 2,
                'room_id' => 2,
                'movie_id' => $movie2,
                'showtime_start' => '2026-07-25 19:30:00',
                'showtime_end' => '2026-07-25 21:15:00',
                'layout_snaps' => $room2Matrix,
                'base_price' => 80000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('showtimes')->insert($showtimes);
    }
}
