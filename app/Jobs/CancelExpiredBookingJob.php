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
                $booking->update(['booking_status' => 'cancelled']);
                Log::info("CancelExpiredBookingJob: Booking {$this->bookingId} has been cancelled due to timeout.");
            }
        } catch (\Exception $e) {
            Log::error('CancelExpiredBookingJob Error: ' . $e->getMessage());
        }
    }
}
