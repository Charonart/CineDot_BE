<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CancelExpiredBookings extends Command
{
    protected $signature = 'bookings:cancel-expired';
    protected $description = 'Hủy các đơn hàng Booking ở trạng thái pending đã quá 10 phút';

    public function handle()
    {
        $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);
        $threshold = now()->subSeconds($ttlSeconds);

        $expiredBookings = Booking::where('booking_status', 'pending')
            ->where('created_at', '<=', $threshold)
            ->get();

        $count = $expiredBookings->count();
        if ($count === 0) {
            $this->info("Không có đơn hàng nào hết hạn.");
            return 0;
        }

        $this->info("Đang xử lý nhả ghế cho {$count} đơn hàng hết hạn...");

        foreach ($expiredBookings as $booking) {
            $booking->update(['booking_status' => 'cancelled']);

            $seatIds = \App\Models\BookingSeat::where('booking_id', $booking->booking_id)
                ->pluck('showtime_seat_id')
                ->toArray();

            if (!empty($seatIds)) {
                \App\Models\ShowtimeSeat::whereIn('showtime_seat_id', $seatIds)
                    ->where('status', 'holding')
                    ->update(['status' => 'available']);

                try {
                    \Illuminate\Support\Facades\Redis::pipeline(function ($pipe) use ($booking, $seatIds) {
                        foreach ($seatIds as $sId) {
                            $pipe->del("hold:showtime:{$booking->showtime_id}:seat:{$sId}");
                        }
                    });
                } catch (\Throwable $e) {
                    // Redis fallback
                }

                try {
                    event(new \App\Events\SeatStatusUpdated($booking->showtime_id, $seatIds, 'available'));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to broadcast SeatStatusUpdated in CancelExpiredBookings: " . $e->getMessage());
                }
            }
        }

        \Illuminate\Support\Facades\Log::info("bookings:cancel-expired completed. Released {$count} expired bookings.");
        $this->info("Đã nhả ghế thành công cho {$count} đơn hàng.");
        return 0;
    }
}
