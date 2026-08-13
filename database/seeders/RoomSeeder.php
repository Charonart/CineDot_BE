<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $generateMatrix = function ($rowsCount, $colsCount) {
            $matrix = [];
            $rowNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
            for ($r = 0; $r < min($rowsCount, count($rowNames)); $r++) {
                $rowName = $rowNames[$r];
                for ($c = 1; $c <= $colsCount; $c++) {
                    $seatId = $rowName . $c;
                    $type = ($r >= 3 && $r <= 6) ? 'VIP' : (($r >= 7) ? 'COUPLE' : 'STD');
                    $matrix[] = [
                        'id' => $seatId,
                        'type' => $type,
                        'cx' => $c * 35,
                        'cy' => ($r + 1) * 35,
                        'angle' => 0,
                    ];
                }
            }
            return $matrix;
        };

        $cinemas = DB::table('cinemas')->get();
        if ($cinemas->isEmpty()) {
            return;
        }

        $roomTemplates = [
            ['name' => 'Phòng 01 (IMAX Laser)', 'type' => 'IMAX Laser', 'rows' => 8, 'cols' => 12],
            ['name' => 'Phòng 02 (2D Dolby Atmos)', 'type' => '2D Dolby Atmos', 'rows' => 6, 'cols' => 10],
            ['name' => 'Phòng 03 (ScreenX 270°)', 'type' => 'ScreenX', 'rows' => 6, 'cols' => 10],
            ['name' => 'Phòng 04 (Gold Class VIP)', 'type' => 'Gold Class', 'rows' => 4, 'cols' => 8],
        ];

        $rooms = [];
        $roomId = 1;

        foreach ($cinemas as $cinema) {
            foreach ($roomTemplates as $tpl) {
                $matrix = $generateMatrix($tpl['rows'], $tpl['cols']);
                $totalSeats = count($matrix);

                $rooms[] = [
                    'room_id' => $roomId++,
                    'cinema_id' => $cinema->cinema_id,
                    'room_name' => $tpl['name'],
                    'room_type' => $tpl['type'],
                    'seat_matrix' => json_encode($matrix),
                    'total_seats' => $totalSeats,
                    'is_active' => true,
                    'created_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rooms, 50) as $chunk) {
            DB::table('rooms')->insertOrIgnore($chunk);
        }
    }
}
