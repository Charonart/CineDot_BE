<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShowtimeSeatSeeder extends Seeder
{
    public function run(): void
    {
        // Populate showtime_seats for active showtimes from today onwards
        $showtimes = DB::table('showtimes')
            ->where('showtime_start', '>=', Carbon::today())
            ->take(120)
            ->get();

        if ($showtimes->isEmpty()) {
            $showtimes = DB::table('showtimes')->take(50)->get();
        }

        if ($showtimes->isEmpty()) {
            return;
        }

        $allSeats = [];

        foreach ($showtimes as $st) {
            $roomSeats = DB::table('seats')
                ->where('room_id', $st->room_id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->get();

            foreach ($roomSeats as $pSeat) {
                // Randomly book ~10% of seats for a live feeling
                $isBooked = (crc32($st->showtime_id . '_' . $pSeat->seat_id) % 10) === 0;

                $allSeats[] = [
                    'showtime_id' => $st->showtime_id,
                    'seat_id'     => $pSeat->seat_id,
                    'status'      => $isBooked ? 'booked' : 'available',
                ];
            }
        }

        foreach (array_chunk($allSeats, 250) as $chunk) {
            DB::table('showtime_seats')->insertOrIgnore($chunk);
        }
    }
}
