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

    public $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function handle(): void
    {
        try {
            // Đảm bảo user đã load
            $user = $this->booking->user;

            if ($user && $user->email) {
                Mail::to($user->email)->send(new BookingConfirmedMail($this->booking));
            } else {
                Log::warning('SendBookingEmailJob: Booking ' . $this->booking->booking_id . ' does not have a valid user email.');
            }
        } catch (\Exception $e) {
            Log::error('SendBookingEmailJob Error: ' . $e->getMessage());
            // Có thể throw lại để retry nếu cần thiết
            throw $e;
        }
    }
}
