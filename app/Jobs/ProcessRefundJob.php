<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\ScheduleSeat;
use App\Models\PointHistory;
use App\Models\UserVoucher;
use App\Models\BookingVoucher;
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
        // In a real application, we would call an external API.
        usleep(500000); // simulate 0.5s network lag

        // 2. Perform all database updates atomically inside a Transaction
        DB::transaction(function () {
            $booking = Booking::with(['user', 'payment'])->findOrFail($this->bookingId);

            if ($booking->booking_status === 'cancelled') {
                Log::info("ProcessRefundJob: Booking ID {$this->bookingId} is already cancelled.");
                return;
            }

            // A. Update Booking status
            $booking->update(['booking_status' => 'cancelled']);

            // B. Update Payment status and record refund metadata
            $payment = $booking->payment;
            if ($payment) {
                $refundAmount = (int) round($booking->total_amount * ($this->refundPercentage / 100));

                // Merge new refund data into existing json metadata
                $paymentData = $payment->payment_data ?? [];
                $paymentData['refund_metadata'] = [
                    'refund_amount' => $refundAmount,
                    'refund_percentage' => $this->refundPercentage,
                    'refund_processed_at' => now()->toIso8601String(),
                ];

                $payment->update([
                    'status' => 'refunded',
                    'payment_data' => $paymentData
                ]);
            }

            // C. Release seats
            $showtimeSeatIds = BookingSeat::where('booking_id', $booking->booking_id)
                ->pluck('showtime_seat_id');

            if ($showtimeSeatIds->isNotEmpty()) {
                \App\Models\ShowtimeSeat::whereIn('showtime_seat_id', $showtimeSeatIds)
                    ->update(['status' => 'available']);
            }


            // D. Revert earned loyalty points
            $user = $booking->user;
            $earnedPoints = (int) round($booking->total_amount / 10000);

            if ($user && $earnedPoints > 0) {
                // Lock the user row to prevent race conditions on points updates
                $user->fresh(); // Reload user
                $user->decrement('point', $earnedPoints);

                PointHistory::create([
                    'user_id' => $user->user_id,
                    'booking_id' => $booking->booking_id,
                    'amount' => -$earnedPoints,
                    'action' => 'deduct_refund',
                ]);
            }

            // E. Handle voucher recovery rules
            $appliedVoucherIds = BookingVoucher::where('booking_id', $booking->booking_id)
                ->pluck('voucher_id');

            if ($appliedVoucherIds->isNotEmpty()) {
                if ($this->returnVoucher) {
                    // Refund 100% path: return the voucher to active status
                    UserVoucher::where('user_id', $booking->user_id)
                        ->whereIn('voucher_id', $appliedVoucherIds)
                        ->update([
                            'is_used' => false,
                            'used_at' => null
                        ]);
                    Log::info("ProcessRefundJob: Vouchers for Booking {$this->bookingId} returned to customer.");
                } else {
                    // Refund 80% path: vouchers are kept as used (customer loses them)
                    Log::info("ProcessRefundJob: Vouchers for Booking {$this->bookingId} forfeited due to late cancellation penalty.");
                }
            }

            Log::info("ProcessRefundJob Completed: Booking ID {$this->bookingId} refunded successfully at {$this->refundPercentage}%.");
        });
    }
}
