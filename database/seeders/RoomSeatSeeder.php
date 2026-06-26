<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cinema;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class RoomSeatSeeder extends Seeder
{
    public function run(): void
    {
        $cinemas = Cinema::all();
        $roomTypes = ['2D', '3D', 'IMAX', '4DX'];
        $roomNameSuffixes = ['Phòng 1', 'Phòng 2'];

        foreach ($cinemas as $cinema) {
            foreach ($roomNameSuffixes as $index => $suffix) {
                $type = $roomTypes[($cinema->cinema_id + $index) % 4];
                $room = Room::create([
                    'cinema_id'   => $cinema->cinema_id,
                    'room_name'   => $suffix . ' (' . $type . ')',
                    'room_type'   => $type,
                    'total_seats' => 48, // Grid 6x8
                    'is_active'   => true,
                ]);

                // Tạo seats cho phòng này (bulk insert để tăng tốc độ)
                $seatsInsert = [];
                $rows = ['A', 'B', 'C', 'D', 'E', 'F'];
                foreach ($rows as $rIndex => $row) {
                    for ($num = 1; $num <= 8; $num++) {
                        $seatType = ($row === 'E' || $row === 'F') ? 'vip' : 'standard';
                        $seatsInsert[] = [
                            'room_id'     => $room->room_id,
                            'seat_row'    => $row,
                            'seat_number' => $num,
                            'seat_type'   => $seatType,
                            'position_x'  => $num * 40,
                            'position_y'  => ($rIndex + 1) * 50,
                            'is_active'   => true,
                            'created_at'  => now(),
                        ];
                    }
                }
                DB::table('seats')->insert($seatsInsert);
            }
        }
    }
}
