<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'room_id' => 1,
                'cinema_id' => 1,
                'room_name' => 'Phòng 01 (IMAX)',
                'room_type' => 'IMAX',
                'seat_matrix' => json_encode(['rows' => 10, 'cols' => 12]),
                'total_seats' => 120,
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'room_id' => 2,
                'cinema_id' => 1,
                'room_name' => 'Phòng 02 (2D Standard)',
                'room_type' => '2D',
                'seat_matrix' => json_encode(['rows' => 8, 'cols' => 10]),
                'total_seats' => 80,
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'room_id' => 3,
                'cinema_id' => 2,
                'room_name' => 'Phòng Premium 01',
                'room_type' => '3D',
                'seat_matrix' => json_encode(['rows' => 10, 'cols' => 10]),
                'total_seats' => 100,
                'is_active' => true,
                'created_at' => now(),
            ],
        ];

        DB::table('rooms')->insert($rooms);
    }
}
