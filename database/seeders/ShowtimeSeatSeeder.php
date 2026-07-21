<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShowtimeSeatSeeder extends Seeder
{
    public function run(): void
    {
        $seats = [];
        $seatTypes = ['standard', 'vip', 'couple'];
        $id = 1;

        // Generate sample seats for showtime 1 and 2
        foreach ([1, 2] as $showtimeId) {
            foreach (['A', 'B', 'C', 'D', 'E'] as $rowIndex => $row) {
                for ($num = 1; $num <= 8; $num++) {
                    $seatType = ($rowIndex >= 3) ? 'vip' : 'standard';
                    if ($row === 'E' && $num > 6) {
                        $seatType = 'couple';
                    }
                    $seats[] = [
                        'showtime_seat_id' => $id++,
                        'showtime_id' => $showtimeId,
                        'seat_type' => $seatType,
                        'row_name' => $row,
                        'seat_number' => (string) $num,
                        'status' => 'available',
                    ];
                }
            }
        }

        foreach (array_chunk($seats, 100) as $chunk) {
            DB::table('showtime_seats')->insert($chunk);
        }
    }
}
