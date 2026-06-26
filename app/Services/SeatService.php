<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\ScheduleSeat;
use Illuminate\Support\Facades\Redis;

class SeatService
{
    public function getScheduleSeats(int $scheduleId)
    {
        Schedule::findOrFail($scheduleId);

        $seats = ScheduleSeat::with('seat')
            ->where('schedule_id', $scheduleId)
            ->get()
            ->sortBy(function ($ss) {
                return $ss->seat->seat_row . str_pad($ss->seat->seat_number, 3, '0', STR_PAD_LEFT);
            })
            ->values();

        // Lấy danh sách ghế đang giữ từ DB (bookings.booking_status = pending)
        $pendingSeatIds = \App\Models\BookingSeat::whereHas('booking', function ($query) use ($scheduleId) {
            $query->where('schedule_id', $scheduleId)
                  ->where('booking_status', 'pending');
        })->pluck('schedule_seat_id')->flip()->toArray();

        // Merge dữ liệu từ Redis và DB để xác định ghế nào đang bị HELD
        foreach ($seats as $ss) {
            if ($ss->status === 'available') {
                $redisKey = "hold:schedule:{$scheduleId}:seat:{$ss->schedule_seat_id}";
                if (Redis::exists($redisKey) || isset($pendingSeatIds[$ss->schedule_seat_id])) {
                    $ss->status = 'held';
                }
            }
        }

        return $seats;
    }
}
