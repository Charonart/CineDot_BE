<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ReleaseSeatHoldInRedis
{
    public function handle(BookingCancelled $event): void
    {
        $booking = $event->booking;

        try {
            // Load bookingSeats if not already loaded
            $booking->loadMissing('bookingSeats');

            foreach ($booking->bookingSeats as $bookingSeat) {
                $redisKey = "hold:schedule:{$booking->schedule_id}:seat:{$bookingSeat->schedule_seat_id}";
                Redis::del($redisKey);
            }

            Log::info("ReleaseSeatHoldInRedis: Seats released in Redis for cancelled booking ID: {$booking->booking_id}");
        } catch (\Exception $e) {
            Log::error("ReleaseSeatHoldInRedis Error for booking ID {$booking->booking_id}: " . $e->getMessage());
        }
    }
}
