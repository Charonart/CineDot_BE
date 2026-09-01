<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SeatType;
use App\Services\RoomFormatCatalog;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $seatSize = 35;
        $rowNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];

        // 1. Layout Generator for IMAX Laser (Curved aisles & wing angles)
        $generateImaxLayout = function () use ($seatSize, $rowNames) {
            $matrix = [];
            $rows = 8;
            $cols = 14;
            for ($r = 0; $r < $rows; $r++) {
                $rowName = $rowNames[$r];
                $actualCy = ($r + 1) * $seatSize + 20;
                $seatNumber = 1;
                for ($c = 1; $c <= $cols; $c++) {
                    $actualCx = $c * $seatSize;
                    if ($c > 3) $actualCx += 15;
                    if ($c > 11) $actualCx += 15;

                    $angle = 0;
                    if ($c <= 3) $angle = 12 - ($c * 3);
                    elseif ($c >= 12) $angle = - (12 - ((15 - $c) * 3));

                    $type = ($r == $rows - 1) ? 'sweetbox' : (($r >= 2 && $r <= 6) ? 'vip' : 'standard');

                    $matrix[] = [
                        'row_name'    => $rowName,
                        'seat_number' => (string) $seatNumber++,
                        'seat_type'   => $type,
                        'coord_x'     => (int) $actualCx,
                        'coord_y'     => (int) $actualCy,
                        'angle'       => (int) $angle,
                        'is_active'   => true,
                    ];
                }
            }
            return $matrix;
        };

        // 2. Layout Generator for Dolby Cinema (Dual symmetrical aisles, VIP acoustic sweet spot)
        $generateDolbyLayout = function () use ($seatSize, $rowNames) {
            $matrix = [];
            $rows = 8;
            $cols = 12;
            for ($r = 0; $r < $rows; $r++) {
                $rowName = $rowNames[$r];
                $actualCy = ($r + 1) * $seatSize + 15;
                $seatNumber = 1;
                for ($c = 1; $c <= $cols; $c++) {
                    $actualCx = $c * $seatSize;
                    if ($c > 3) $actualCx += 20; // Left aisle
                    if ($c > 9) $actualCx += 20; // Right aisle

                    $type = ($r == $rows - 1) ? 'couple' : (($r >= 3 && $r <= 6) ? 'vip' : 'standard');

                    $matrix[] = [
                        'row_name'    => $rowName,
                        'seat_number' => (string) $seatNumber++,
                        'seat_type'   => $type,
                        'coord_x'     => (int) $actualCx,
                        'coord_y'     => (int) $actualCy,
                        'angle'       => 0,
                        'is_active'   => true,
                    ];
                }
            }
            return $matrix;
        };

        // 3. Layout Generator for ScreenX (3 Blocks separated by wide aisles for 270 degree wall viewing)
        $generateScreenXLayout = function () use ($seatSize, $rowNames) {
            $matrix = [];
            $rows = 7;
            $cols = 12;
            for ($r = 0; $r < $rows; $r++) {
                $rowName = $rowNames[$r];
                $actualCy = ($r + 1) * $seatSize + 25;
                $seatNumber = 1;
                for ($c = 1; $c <= $cols; $c++) {
                    $actualCx = $c * $seatSize;
                    if ($c > 4) $actualCx += 25;
                    if ($c > 8) $actualCx += 25;

                    $type = ($r == $rows - 1) ? 'couple' : (($r >= 2 && $r <= 5) ? 'vip' : 'standard');

                    $matrix[] = [
                        'row_name'    => $rowName,
                        'seat_number' => (string) $seatNumber++,
                        'seat_type'   => $type,
                        'coord_x'     => (int) $actualCx,
                        'coord_y'     => (int) $actualCy,
                        'angle'       => 0,
                        'is_active'   => true,
                    ];
                }
            }
            return $matrix;
        };

        // 4. Layout Generator for Samsung Onyx Cinema LED (Straight matrix, high-contrast zone)
        $generateOnyxLedLayout = function () use ($seatSize, $rowNames) {
            $matrix = [];
            $rows = 7;
            $cols = 12;
            for ($r = 0; $r < $rows; $r++) {
                $rowName = $rowNames[$r];
                $actualCy = ($r + 1) * $seatSize + 15;
                $seatNumber = 1;
                for ($c = 1; $c <= $cols; $c++) {
                    $actualCx = $c * $seatSize;
                    if ($c > 6) $actualCx += 20; // Center aisle

                    $type = ($r == $rows - 1) ? 'couple' : (($r >= 3) ? 'vip' : 'standard');

                    $matrix[] = [
                        'row_name'    => $rowName,
                        'seat_number' => (string) $seatNumber++,
                        'seat_type'   => $type,
                        'coord_x'     => (int) $actualCx,
                        'coord_y'     => (int) $actualCy,
                        'angle'       => 0,
                        'is_active'   => true,
                    ];
                }
            }
            return $matrix;
        };

        // 5. Layout Generator for Gold Class VIP (Spacious, couple recliners & bed seats)
        $generateGoldClassLayout = function () use ($rowNames) {
            $matrix = [];
            $rows = 4;
            $cols = 8;
            $wideSeatW = 55;
            $wideSeatH = 50;
            for ($r = 0; $r < $rows; $r++) {
                $rowName = $rowNames[$r];
                $actualCy = ($r + 1) * $wideSeatH + 20;
                $seatNumber = 1;
                for ($c = 1; $c <= $cols; $c++) {
                    $actualCx = $c * $wideSeatW;
                    // Mini table gaps between pairs
                    if ($c % 2 == 1) $actualCx += 10;
                    if ($c == 5) $actualCx += 30; // Center walk aisle

                    $type = ($r == $rows - 1) ? 'bed' : 'deluxe';

                    $matrix[] = [
                        'row_name'    => $rowName,
                        'seat_number' => (string) $seatNumber++,
                        'seat_type'   => $type,
                        'coord_x'     => (int) $actualCx,
                        'coord_y'     => (int) $actualCy,
                        'angle'       => 0,
                        'is_active'   => true,
                    ];
                }
            }
            return $matrix;
        };

        // 6. Layout Generator for Digital 3D Atmos
        $generateDigital3DLayout = function () use ($seatSize, $rowNames) {
            $matrix = [];
            $rows = 8;
            $cols = 12;
            for ($r = 0; $r < $rows; $r++) {
                $rowName = $rowNames[$r];
                $actualCy = ($r + 1) * $seatSize + 15;
                $seatNumber = 1;
                for ($c = 1; $c <= $cols; $c++) {
                    $actualCx = $c * $seatSize;
                    if ($c == 4 || $c == 9) $actualCx += 15;

                    $type = ($r == $rows - 1) ? 'couple' : (($r >= 3 && $r <= 6) ? 'vip' : 'standard');

                    $matrix[] = [
                        'row_name'    => $rowName,
                        'seat_number' => (string) $seatNumber++,
                        'seat_type'   => $type,
                        'coord_x'     => (int) $actualCx,
                        'coord_y'     => (int) $actualCy,
                        'angle'       => 0,
                        'is_active'   => true,
                    ];
                }
            }
            return $matrix;
        };

        // 7. Layout Generator for Digital 2D Standard
        $generateStandard2DLayout = function () use ($seatSize, $rowNames) {
            $matrix = [];
            $rows = 8;
            $cols = 12;
            for ($r = 0; $r < $rows; $r++) {
                $rowName = $rowNames[$r];
                $actualCy = ($r + 1) * $seatSize + 15;
                $seatNumber = 1;
                for ($c = 1; $c <= $cols; $c++) {
                    $actualCx = $c * $seatSize;
                    if ($c > 6) $actualCx += 20; // Center aisle

                    $type = ($r == $rows - 1) ? 'couple' : (($r >= 3 && $r <= 6) ? 'vip' : 'standard');

                    $matrix[] = [
                        'row_name'    => $rowName,
                        'seat_number' => (string) $seatNumber++,
                        'seat_type'   => $type,
                        'coord_x'     => (int) $actualCx,
                        'coord_y'     => (int) $actualCy,
                        'angle'       => 0,
                        'is_active'   => true,
                    ];
                }
            }
            return $matrix;
        };

        $cinemas = DB::table('cinemas')->get();
        if ($cinemas->isEmpty()) {
            return;
        }

        $templates = [
            [
                'name'             => 'Phòng 01 (IMAX Laser 3D)',
                'room_type'        => 'IMAX Laser 3D',
                'screen_type'      => 'imax_laser',
                'sound_technology' => 'imax_sound',
                'screen_config'    => RoomFormatCatalog::getDefaultScreenConfig('imax_laser'),
                'features'         => ['laser_projection', 'curved_screen', '12ch_imax_sound', 'sweetbox_seats'],
                'generator'        => $generateImaxLayout,
            ],
            [
                'name'             => 'Phòng 02 (Dolby Cinema Atmos)',
                'room_type'        => 'Dolby Cinema',
                'screen_type'      => 'dolby_cinema',
                'sound_technology' => 'dolby_atmos',
                'screen_config'    => RoomFormatCatalog::getDefaultScreenConfig('dolby_cinema'),
                'features'         => ['dolby_vision_hdr', 'dolby_atmos', 'curved_screen', 'acoustic_walls'],
                'generator'        => $generateDolbyLayout,
            ],
            [
                'name'             => 'Phòng 03 (ScreenX 270° Atmos)',
                'room_type'        => 'ScreenX 270°',
                'screen_type'      => 'screenx',
                'sound_technology' => 'dolby_atmos',
                'screen_config'    => RoomFormatCatalog::getDefaultScreenConfig('screenx'),
                'features'         => ['three_wall_screen', '270_degree_view', 'dolby_atmos'],
                'generator'        => $generateScreenXLayout,
            ],
            [
                'name'             => 'Phòng 04 (Samsung Onyx Cinema LED 4K)',
                'room_type'        => 'Samsung Onyx Cinema LED',
                'screen_type'      => 'onyx_led',
                'sound_technology' => 'dolby_atmos',
                'screen_config'    => RoomFormatCatalog::getDefaultScreenConfig('onyx_led'),
                'features'         => ['samsung_onyx_led', '4k_dci', 'hfr_120fps', 'jbl_audio'],
                'generator'        => $generateOnyxLedLayout,
            ],
            [
                'name'             => 'Phòng 05 (Gold Class VIP Recliner)',
                'room_type'        => 'Gold Class VIP',
                'screen_type'      => 'standard_2d',
                'sound_technology' => 'dolby_atmos',
                'screen_config'    => RoomFormatCatalog::getDefaultScreenConfig('standard_2d'),
                'features'         => ['recliner_leather_seats', 'in_seat_service', 'mini_table', 'dolby_atmos'],
                'generator'        => $generateGoldClassLayout,
            ],
            [
                'name'             => 'Phòng 06 (Digital 3D Dolby Atmos)',
                'room_type'        => 'Digital 3D Atmos',
                'screen_type'      => 'standard_3d',
                'sound_technology' => 'dolby_atmos',
                'screen_config'    => RoomFormatCatalog::getDefaultScreenConfig('standard_3d'),
                'features'         => ['polarized_3d', 'dolby_atmos', 'sweetbox_seats'],
                'generator'        => $generateDigital3DLayout,
            ],
            [
                'name'             => 'Phòng 07 (Digital 2D Standard)',
                'room_type'        => 'Digital 2D Standard',
                'screen_type'      => 'standard_2d',
                'sound_technology' => 'surround_71',
                'screen_config'    => RoomFormatCatalog::getDefaultScreenConfig('standard_2d'),
                'features'         => ['2k_laser', '71_surround'],
                'generator'        => $generateStandard2DLayout,
            ],
        ];

        $rooms = [];
        $seats = [];
        $roomId = 1;

        foreach ($cinemas as $cinema) {
            foreach ($templates as $tpl) {
                $matrix = $tpl['generator']();
                $totalSeats = count($matrix);

                $currentRoomId = $roomId++;
                $rooms[] = [
                    'room_id'          => $currentRoomId,
                    'cinema_id'        => $cinema->cinema_id,
                    'room_name'        => $tpl['name'],
                    'room_type'        => $tpl['room_type'],
                    'screen_type'      => $tpl['screen_type'],
                    'sound_technology' => $tpl['sound_technology'],
                    'screen_config'    => json_encode($tpl['screen_config']),
                    'features'         => json_encode($tpl['features']),
                    'total_seats'      => $totalSeats,
                    'is_active'        => true,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];

                foreach ($matrix as $s) {
                    $seats[] = [
                        'room_id'     => $currentRoomId,
                        'seat_type'   => SeatType::resolveTypeKey($s['seat_type']),
                        'row_name'    => $s['row_name'],
                        'seat_number' => $s['seat_number'],
                        'coord_x'     => $s['coord_x'],
                        'coord_y'     => $s['coord_y'],
                        'angle'       => $s['angle'],
                        'is_active'   => $s['is_active'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($rooms, 50) as $chunk) {
            DB::table('rooms')->insertOrIgnore($chunk);
        }

        foreach (array_chunk($seats, 500) as $chunk) {
            DB::table('seats')->insertOrIgnore($chunk);
        }
    }
}
