<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Retrieve all movies available for scheduling
        $nowShowingMovies = DB::table('movies')
            ->where('status', 'now_showing')
            ->get()
            ->toArray();

        if (empty($nowShowingMovies)) {
            $nowShowingMovies = DB::table('movies')->take(30)->get()->toArray();
        }

        // Also get some upcoming movies for occasional early sneak previews
        $upcomingMovies = DB::table('movies')
            ->where('status', 'upcoming')
            ->get()
            ->toArray();

        $allSchedulableMovies = array_merge($nowShowingMovies, array_slice($upcomingMovies, 0, 5));

        $rooms = DB::table('rooms')->get();
        if ($rooms->isEmpty() || empty($allSchedulableMovies)) {
            return;
        }

        $showtimes = [];
        $showtimeId = 1;
        $today = Carbon::today();

        // 2. Generate showtimes from 3 days ago (-3) to 7 days in the future (+7)
        for ($dayOffset = -3; $dayOffset <= 7; $dayOffset++) {
            $date = $today->copy()->addDays($dayOffset);

            foreach ($rooms as $room) {
                // Determine a realistic morning opening time for this room (between 08:30 and 09:30)
                $morningMinutes = [0, 15, 30, 45][($room->room_id + abs($dayOffset)) % 4];
                $currentPointer = $date->copy()->setTime(8, 30, 0)->addMinutes($morningMinutes);

                // End of scheduling day limit (until 23:30)
                $closingLimit = $date->copy()->setTime(23, 30, 0);

                while ($currentPointer->lt($closingLimit)) {
                    // Pick a random movie for this room slot
                    $movie = $allSchedulableMovies[array_rand($allSchedulableMovies)];
                    $duration = max(75, min(190, (int) ($movie->duration ?? 110)));

                    $startDateTime = $currentPointer->copy();
                    $endDateTime = $startDateTime->copy()->addMinutes($duration);

                    // Base price calculation depending on room format and peak time
                    $basePrice = 85000;
                    $roomTypeLower = strtolower($room->room_type);

                    if (str_contains($roomTypeLower, 'imax')) {
                        $basePrice += 45000;
                    } elseif (str_contains($roomTypeLower, 'screenx') || str_contains($roomTypeLower, '4d')) {
                        $basePrice += 35000;
                    } elseif (str_contains($roomTypeLower, 'gold') || str_contains($roomTypeLower, 'vip')) {
                        $basePrice += 65000;
                    }

                    // Peak evening hour surcharge (18:00 - 21:30)
                    if ($startDateTime->hour >= 18 && $startDateTime->hour <= 21) {
                        $basePrice += 15000;
                    }

                    $showtimes[] = [
                        'showtime_id' => $showtimeId++,
                        'room_id' => $room->room_id,
                        'movie_id' => $movie->movie_id,
                        'showtime_start' => $startDateTime->format('Y-m-d H:i:s'),
                        'showtime_end' => $endDateTime->format('Y-m-d H:i:s'),
                        'base_price' => $basePrice,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // 3. Exactly 30 minutes buffer between showtimes for cleaning & commercials
                    $currentPointer = $endDateTime->copy()->addMinutes(30);
                }
            }
        }

        // Insert in bulk chunks
        foreach (array_chunk($showtimes, 200) as $chunk) {
            DB::table('showtimes')->insertOrIgnore($chunk);
        }
    }
}
