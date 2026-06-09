<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\ScheduleSeat;

class SeatService
{
    public function getScheduleSeats(int $scheduleId)
    {
        Schedule::findOrFail($scheduleId);

        return ScheduleSeat::with('seat')
            ->where('schedule_id', $scheduleId)
            ->get()
            ->sortBy(function ($ss) {
                return $ss->seat->seat_row . str_pad($ss->seat->seat_number, 3, '0', STR_PAD_LEFT);
            })
            ->values();
    }
}
