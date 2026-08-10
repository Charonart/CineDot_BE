<?php

namespace App\Services;

use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Illuminate\Support\Facades\Redis;

class SeatService
{
    public function getScheduleSeats(int $showtimeId)
    {
        Showtime::findOrFail($showtimeId);

        $seats = ShowtimeSeat::where('showtime_id', $showtimeId)
            ->get()
            ->sortBy(function ($ss) {
                return $ss->row_name . str_pad($ss->seat_number, 3, '0', STR_PAD_LEFT);
            })
            ->values();

        $pendingSeatIds = \App\Models\BookingSeat::whereHas('booking', function ($query) use ($showtimeId) {
            $query->where('showtime_id', $showtimeId)
                  ->where('booking_status', 'pending');
        })->pluck('showtime_seat_id')->flip()->toArray();

        foreach ($seats as $ss) {
            if ($ss->status === 'available') {
                $redisKey = "hold:showtime:{$showtimeId}:seat:{$ss->showtime_seat_id}";
                if (Redis::exists($redisKey) || isset($pendingSeatIds[$ss->showtime_seat_id])) {
                    $ss->status = 'holding';
                }
            }
        }

        return $seats;
    }
}
