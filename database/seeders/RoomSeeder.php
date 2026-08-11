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
            $rowNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
            for ($r = 0; $r < min($rowsCount, count($rowNames)); $r++) {
                $rowName = $rowNames[$r];
                for ($c = 1; $c <= $colsCount; $c++) {
                    $seatId = $rowName . $c;
                    $type = ($r >= 2 && $r <= 4) ? 'VIP' : (($r >= 5) ? 'COUPLE' : 'STD');
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

        $rooms = [
            [
                'room_id' => 1,
                'cinema_id' => 1,
                'room_name' => 'Phòng 01 (IMAX)',
                'room_type' => 'IMAX',
                'seat_matrix' => json_encode($generateMatrix(6, 10)),
                'total_seats' => 60,
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'room_id' => 2,
                'cinema_id' => 1,
                'room_name' => 'Phòng 02 (2D Standard)',
                'room_type' => '2D',
                'seat_matrix' => json_encode($generateMatrix(5, 8)),
                'total_seats' => 40,
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'room_id' => 3,
                'cinema_id' => 2,
                'room_name' => 'Phòng Premium 01',
                'room_type' => '3D',
                'seat_matrix' => json_encode($generateMatrix(6, 10)),
                'total_seats' => 60,
                'is_active' => true,
                'created_at' => now(),
            ],
        ];

        DB::table('rooms')->insertOrIgnore($rooms);
    }
}
