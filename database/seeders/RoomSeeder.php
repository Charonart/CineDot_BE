<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $generateLayout = function ($layoutType, $rowsCount, $colsCount) {
            $matrix = [];
            $rowNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
            $seatSize = 35;

            if ($layoutType === 'layout_1') {
                // Layout 1: Central aisle, Couple at the back, some broken seats
                for ($r = 0; $r < min($rowsCount, count($rowNames)); $r++) {
                    $rowName = $rowNames[$r];
                    $seatNumber = 1;
                    $actualCx = 0;
                    for ($c = 1; $c <= $colsCount; $c++) {
                        $actualCx += $seatSize;
                        
                        // Central aisle
                        if ($c == floor($colsCount / 2) + 1) {
                            $actualCx += $seatSize; // Gap
                        }

                        $type = ($r == $rowsCount - 1) ? 'COUPLE' : 'STD';
                        if ($r >= 3 && $r < $rowsCount - 1) {
                            $type = 'VIP';
                        }
                        
                        $status = 'available';
                        // Add some broken seats
                        if ($r == 2 && $c == 3) $status = 'blocked';
                        if ($r == 4 && $c == 5) $status = 'blocked';

                        $matrix[] = [
                            'id' => $rowName . $seatNumber,
                            'type' => $type,
                            'cx' => $actualCx,
                            'cy' => ($r + 1) * $seatSize,
                            'angle' => 0,
                            'status' => $status
                        ];
                        $seatNumber++;
                    }
                }
            } elseif ($layoutType === 'layout_2') {
                // Layout 2: Two aisles, empty row in the middle, couple seats back 2 rows
                $actualCy = 0;
                for ($r = 0; $r < min($rowsCount, count($rowNames)); $r++) {
                    $rowName = $rowNames[$r];
                    $actualCy += $seatSize;
                    
                    // Empty row
                    if ($r == 3) {
                        $actualCy += $seatSize;
                    }
                    
                    $seatNumber = 1;
                    $actualCx = 0;
                    for ($c = 1; $c <= $colsCount; $c++) {
                        $actualCx += $seatSize;
                        
                        // Two aisles
                        if ($c == 4 || $c == $colsCount - 2) {
                            $actualCx += $seatSize;
                        }

                        $type = ($r >= $rowsCount - 2) ? 'COUPLE' : 'STD';
                        
                        $status = 'available';
                        // Broken seats
                        if ($r == 1 && $c == 2) $status = 'blocked';

                        $matrix[] = [
                            'id' => $rowName . $seatNumber,
                            'type' => $type,
                            'cx' => $actualCx,
                            'cy' => $actualCy,
                            'angle' => 0,
                            'status' => $status
                        ];
                        $seatNumber++;
                    }
                }
            } elseif ($layoutType === 'layout_3') {
                // Layout 3: Gold Class / VIP only. Wide spacing. Curving (angle).
                for ($r = 0; $r < min($rowsCount, count($rowNames)); $r++) {
                    $rowName = $rowNames[$r];
                    $seatNumber = 1;
                    $actualCx = 0;
                    for ($c = 1; $c <= $colsCount; $c++) {
                        $actualCx += $seatSize * 1.5;
                        
                        // Omit corners to make it look curved
                        if (($r == 0 && ($c == 1 || $c == $colsCount)) || 
                            ($r == 1 && ($c == 1 || $c == $colsCount))) {
                            continue; // Omit seat
                        }

                        $type = 'VIP';
                        $status = 'available';
                        if ($r == 3 && $c == 4) $status = 'blocked';

                        $matrix[] = [
                            'id' => $rowName . $seatNumber,
                            'type' => $type,
                            'cx' => (int) $actualCx,
                            'cy' => (int) (($r + 1) * $seatSize * 1.5),
                            'angle' => 0,
                            'status' => $status
                        ];
                        $seatNumber++;
                    }
                }
            } elseif ($layoutType === 'layout_4') {
                // Layout 4: Sweetbox layout (Couples at the wings/sides), standard in the middle
                for ($r = 0; $r < min($rowsCount, count($rowNames)); $r++) {
                    $rowName = $rowNames[$r];
                    $seatNumber = 1;
                    $actualCx = 0;
                    for ($c = 1; $c <= $colsCount; $c++) {
                        $actualCx += $seatSize;

                        $type = 'STD';
                        if ($r > 2 && $r < $rowsCount - 1) {
                            $type = 'VIP';
                        }
                        
                        // Couples on the edges
                        if ($c <= 2 || $c >= $colsCount - 1) {
                            $type = 'COUPLE';
                        }

                        $status = 'available';
                        if ($r == 2 && $c == 8) $status = 'blocked';
                        
                        $matrix[] = [
                            'id' => $rowName . $seatNumber,
                            'type' => $type,
                            'cx' => $actualCx,
                            'cy' => ($r + 1) * $seatSize,
                            'angle' => 0,
                            'status' => $status
                        ];
                        $seatNumber++;
                    }
                }
            } else {
                // Layout 5: Block layout. 3 blocks separated by 2 aisles. Center block is VIP. Back row Couple.
                for ($r = 0; $r < min($rowsCount, count($rowNames)); $r++) {
                    $rowName = $rowNames[$r];
                    $seatNumber = 1;
                    $actualCx = 0;
                    for ($c = 1; $c <= $colsCount; $c++) {
                        $actualCx += $seatSize;
                        
                        // 2 aisles
                        if ($c == 4 || $c == $colsCount - 2) {
                            $actualCx += $seatSize;
                        }

                        $type = 'STD';
                        if ($c > 3 && $c < $colsCount - 2) {
                            $type = 'VIP';
                        }
                        if ($r == $rowsCount - 1) {
                            $type = 'COUPLE';
                        }
                        
                        $status = 'available';
                        // Broken seats
                        if ($r == 0 && $c == 5) $status = 'blocked';
                        if ($r == 5 && $c == 2) $status = 'blocked';

                        $matrix[] = [
                            'id' => $rowName . $seatNumber,
                            'type' => $type,
                            'cx' => $actualCx,
                            'cy' => ($r + 1) * $seatSize,
                            'angle' => 0,
                            'status' => $status
                        ];
                        $seatNumber++;
                    }
                }
            }

            return $matrix;
        };

        $cinemas = DB::table('cinemas')->get();
        if ($cinemas->isEmpty()) {
            return;
        }

        $roomTemplates = [
            ['name' => 'Phòng 01 (IMAX Laser)', 'type' => 'IMAX Laser', 'rows' => 8, 'cols' => 12, 'layout' => 'layout_1'],
            ['name' => 'Phòng 02 (2D Dolby Atmos)', 'type' => '2D Dolby Atmos', 'rows' => 7, 'cols' => 10, 'layout' => 'layout_2'],
            ['name' => 'Phòng 03 (ScreenX 270°)', 'type' => 'ScreenX', 'rows' => 6, 'cols' => 12, 'layout' => 'layout_5'],
            ['name' => 'Phòng 04 (Gold Class VIP)', 'type' => 'Gold Class', 'rows' => 5, 'cols' => 8, 'layout' => 'layout_3'],
            ['name' => 'Phòng 05 (Sweetbox Couple)', 'type' => 'Standard', 'rows' => 6, 'cols' => 10, 'layout' => 'layout_4'],
        ];

        $rooms = [];
        $roomId = 1;

        foreach ($cinemas as $cinema) {
            foreach ($roomTemplates as $tpl) {
                $matrix = $generateLayout($tpl['layout'], $tpl['rows'], $tpl['cols']);
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
