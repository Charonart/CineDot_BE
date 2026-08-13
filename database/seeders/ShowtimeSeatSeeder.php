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
        $seatId = 1;

        foreach ($showtimes as $st) {
            $matrix = json_decode($st->layout_snaps, true);
            if (empty($matrix) || !is_array($matrix)) {
                // Fallback standard layout
                $matrix = [];
                foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $rIdx => $row) {
                    for ($c = 1; $c <= 10; $c++) {
                        $type = ($rIdx >= 2 && $rIdx <= 4) ? 'VIP' : (($rIdx >= 5) ? 'COUPLE' : 'STD');
                        $matrix[] = ['id' => $row . $c, 'type' => $type];
                    }
                }
            }

            foreach ($matrix as $seatItem) {
                $code = $seatItem['id'] ?? 'A1';
                preg_match('/^([A-Za-z]+)(\d+)$/', $code, $m);
                $rowName = $m[1] ?? 'A';
                $seatNumber = $m[2] ?? '1';

                $rawType = strtoupper($seatItem['type'] ?? 'STD');
                $seatType = match ($rawType) {
                    'VIP' => 'vip',
                    'COUPLE' => 'couple',
                    default => 'standard',
                };

                // Randomly book ~10% of seats for a live feeling
                $isBooked = (crc32($st->showtime_id . $code) % 10) === 0;

                $allSeats[] = [
                    'showtime_seat_id' => $seatId++,
                    'showtime_id' => $st->showtime_id,
                    'seat_type' => $seatType,
                    'row_name' => $rowName,
                    'seat_number' => $seatNumber,
                    'status' => $isBooked ? 'booked' : 'available',
                ];
            }
        }

        foreach (array_chunk($allSeats, 250) as $chunk) {
            DB::table('showtime_seats')->insertOrIgnore($chunk);
        }
    }
}
