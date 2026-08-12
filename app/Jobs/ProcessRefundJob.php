<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\ShowtimeSeat;
use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bookingId;
    protected $refundPercentage;
    protected $returnVoucher;

    /**
     * Create a new job instance.
     */
    public function __construct(int $bookingId, int $refundPercentage, bool $returnVoucher)
    {
        $this->bookingId = $bookingId;
        $this->refundPercentage = $refundPercentage;
        $this->returnVoucher = $returnVoucher;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("ProcessRefundJob Started: Processing refund for Booking ID {$this->bookingId}");

        // 1. Simulate communicating with the payment gateway (VNPay/Momo)
        usleep(500000); // simulate 0.5s network lag

        // 2. Perform all database updates atomically inside a Transaction
        DB::transaction(function () {
            $booking = Booking::with(['user'])->findOrFail($this->bookingId);

            if ($booking->booking_status === 'cancelled') {
                Log::info("ProcessRefundJob: Booking ID {$this->bookingId} is already cancelled.");
                return;
            }

            // A. Update Booking status
            $booking->update([
                'booking_status' => 'cancelled',
                'notes'          => "Khách yêu cầu hủy vé (Hoàn {$this->refundPercentage}%)"
            ]);

            // B. Release seats
            $showtimeSeatIds = BookingSeat::where('booking_id', $booking->booking_id)
                ->pluck('showtime_seat_id');

            if ($showtimeSeatIds->isNotEmpty()) {
                ShowtimeSeat::whereIn('showtime_seat_id', $showtimeSeatIds)
                    ->update(['status' => 'available']);
            }

            // C. Revert earned loyalty points
            $user = $booking->user;
            $earnedPoints = (int) round(($booking->final_amount ?? 0) / 10000);

            if ($user && $earnedPoints > 0) {
                $user = \App\Models\User::where('user_id', $user->user_id)->lockForUpdate()->first();
                if ($user) {
                    $user->decrement('total_points', $earnedPoints);
                }
            }

            // D. Handle voucher recovery rules (if 100% refund, restore used_count for voucher)
            if ($this->returnVoucher && $booking->voucher_id) {
                Voucher::where('voucher_id', $booking->voucher_id)->where('used_count', '>', 0)->decrement('used_count');
                Log::info("ProcessRefundJob: Voucher #{$booking->voucher_id} used_count decremented.");
            }

            Log::info("ProcessRefundJob Completed: Booking ID {$this->bookingId} refunded successfully at {$this->refundPercentage}%.");
        });
    }
}
