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

            $showtimeId = $booking->showtime_id ?? $booking->schedule_id;
            $releasedSeatIds = [];

            foreach ($booking->bookingSeats as $bookingSeat) {
                $seatId = $bookingSeat->showtime_seat_id ?? $bookingSeat->schedule_seat_id;
                if ($seatId) {
                    $releasedSeatIds[] = (int) $seatId;
                    Redis::del("hold:showtime:{$showtimeId}:seat:{$seatId}");
                    Redis::del("hold:schedule:{$showtimeId}:seat:{$seatId}");
                }
            }

            if (!empty($releasedSeatIds) && $showtimeId) {
                \App\Models\ShowtimeSeat::whereIn('showtime_seat_id', $releasedSeatIds)
                    ->where('status', 'holding')
                    ->update(['status' => 'available']);

                event(new \App\Events\SeatStatusUpdated($showtimeId, $releasedSeatIds, 'available', $booking->user_id));
            }

            Log::info("ReleaseSeatHoldInRedis: Seats released in Redis and broadcasted for cancelled booking ID: {$booking->booking_id}");
        } catch (\Exception $e) {
            Log::error("ReleaseSeatHoldInRedis Error for booking ID {$booking->booking_id}: " . $e->getMessage());
        }
    }
}
