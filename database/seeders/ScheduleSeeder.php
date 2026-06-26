<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Seat;
use App\Models\Movie;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $movies = Movie::pluck('id', 'title')->toArray();
        $rooms = Room::all();
        if ($rooms->count() === 0 || empty($movies)) return;

        $baseScheduleTemplates = [
            ['Se7en', 0, '09:00', '11:15', 75000],
            ['Se7en', 0, '14:30', '16:45', 75000],
            ['Se7en', 1, '20:00', '22:15', 95000],
            ['Parasite', 2, '10:00', '12:15', 75000],
            ['Parasite', 2, '19:30', '21:45', 75000],
            ['The Lord of the Rings: The Fellowship of the Ring', 0, '12:00', '15:00', 85000],
            ['Finding Nemo', 2, '08:30', '10:10', 70000],
            ['CineDot: The Beginning', 3, '09:00', '11:00', 70000],
            ['Tanstack & Beyond', 3, '15:00', '16:35', 75000],
        ];

        // Random từ quá khứ 3 ngày đến tương lai 7 ngày
        $scheduleTemplates = [];
        for ($i = -3; $i <= 7; $i++) {
            $date = Carbon::now()->addDays($i)->toDateString();
            foreach ($baseScheduleTemplates as $st) {
                // Ensure room exists
                if (isset($rooms[$st[1]])) {
                    $scheduleTemplates[] = [$st[0], $st[1], $date, $st[2], $st[3], $st[4]];
                }
            }
        }

        foreach ($scheduleTemplates as $st) {
            [$title, $roomIdx, $date, $start, $end, $price] = $st;
            
            if (!isset($movies[$title])) {
                continue;
            }

            $movieId = $movies[$title];
            $room  = $rooms[$roomIdx];

            $schedule = Schedule::create([
                'movie_id'       => $movieId,
                'room_id'        => $room->room_id,
                'schedule_date'  => $date,
                'schedule_start' => $start . ':00',
                'schedule_end'   => $end . ':00',
                'base_price'     => $price,
            ]);

            // Query seats thuộc room của schedule
            $seats = Seat::where('room_id', $room->room_id)->get();
            $scheduleSeatsInsert = [];
            foreach ($seats as $seat) {
                $seatPrice = ($seat->seat_type === 'vip') ? $price + 20000 : $price;
                $scheduleSeatsInsert[] = [
                    'schedule_id' => $schedule->schedule_id,
                    'seat_id'     => $seat->seat_id,
                    'status'      => 'available',
                    'price'       => $seatPrice,
                ];
            }
            DB::table('schedule_seats')->insert($scheduleSeatsInsert);
        }
    }
}
