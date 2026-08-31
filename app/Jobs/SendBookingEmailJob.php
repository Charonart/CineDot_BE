<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmedMail;
use Illuminate\Support\Facades\Log;

class SendBookingEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $bookingId;

    /**
     * Queue configuration & retry policies
     */
    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 30;

    public function __construct(int $bookingId)
    {
        $this->bookingId = $bookingId;
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $booking = Booking::with('user')->find($this->bookingId);
        if (!$booking) {
            Log::warning("SendBookingEmailJob: Booking #{$this->bookingId} not found. Skipping.");
            return;
        }

        $user = $booking->user;
        if ($user && !empty($user->email)) {
            Mail::to($user->email)->send(new BookingConfirmedMail($booking));
            Log::info("SendBookingEmailJob: Confirmation email sent for booking #{$this->bookingId} to {$user->email}");
        } else {
            Log::warning("SendBookingEmailJob: Booking #{$this->bookingId} does not have a valid user email.");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendBookingEmailJob Failed for booking #{$this->bookingId}: " . $exception->getMessage());
    }
}
