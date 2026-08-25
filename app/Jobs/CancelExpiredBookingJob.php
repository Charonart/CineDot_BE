<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class CancelExpiredBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bookingId;

    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
    }

    public function handle(): void
    {
        try {
            $booking = Booking::find($this->bookingId);

            if ($booking && $booking->booking_status === 'pending') {
                $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);
                $createdAt = \Carbon\Carbon::parse($booking->created_at);
                $expiresAt = (clone $createdAt)->addSeconds($ttlSeconds);

                // Safety guard: If job runs before expiration (e.g., sync queue driver or early worker execution), skip cancellation
                if (now()->lt($expiresAt->subSeconds(5))) {
                    Log::info("CancelExpiredBookingJob: Booking {$this->bookingId} is not expired yet (expires at {$expiresAt->toDateTimeString()}). Skipping.");
                    return;
                }

                $booking->update(['booking_status' => 'cancelled']);

                $seatIds = \App\Models\BookingSeat::where('booking_id', $booking->booking_id)->pluck('showtime_seat_id')->toArray();
                if (!empty($seatIds)) {
                    \App\Models\ShowtimeSeat::whereIn('showtime_seat_id', $seatIds)
                        ->where('status', 'holding')
                        ->update(['status' => 'available']);

                    foreach ($seatIds as $sId) {
                        try {
                            \Illuminate\Support\Facades\Redis::del("hold:showtime:{$booking->showtime_id}:seat:{$sId}");
                        } catch (\Exception $e) {
                            // Redis fallback
                        }
                    }

                    try {
                        event(new \App\Events\SeatStatusUpdated($booking->showtime_id, $seatIds, 'available'));
                    } catch (\Exception $e) {
                        Log::warning("Failed to broadcast SeatStatusUpdated in CancelExpiredBookingJob: " . $e->getMessage());
                    }
                }

                Log::info("CancelExpiredBookingJob: Booking {$this->bookingId} and " . count($seatIds) . " seats released due to timeout.");
            }
        } catch (\Exception $e) {
            Log::error('CancelExpiredBookingJob Error: ' . $e->getMessage());
        }
    }
}
