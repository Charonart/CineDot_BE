<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\ShowtimeSeat;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CancelExpiredBookingsCommand extends Command
{
    protected $signature = 'booking:cancel-expired';
    protected $description = 'Tự động quét và nhả ghế cho tất cả các đơn đặt vé quá hạn 10 phút (Không tốn chi phí Worker Dyno)';

    public function handle(): int
    {
        $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);
        $threshold = now()->subSeconds($ttlSeconds);

        $expiredBookings = Booking::where('booking_status', 'pending')
            ->where('created_at', '<=', $threshold)
            ->get();

        $count = $expiredBookings->count();
        if ($count === 0) {
            $this->info("Không có đơn hàng nào hết hạn.");
            return Command::SUCCESS;
        }

        $this->info("Đang xử lý nhả ghế cho {$count} đơn hàng hết hạn...");

        foreach ($expiredBookings as $booking) {
            $booking->update(['booking_status' => 'cancelled']);

            $seatIds = \App\Models\BookingSeat::where('booking_id', $booking->booking_id)
                ->pluck('showtime_seat_id')
                ->toArray();

            if (!empty($seatIds)) {
                ShowtimeSeat::whereIn('showtime_seat_id', $seatIds)
                    ->where('status', 'holding')
                    ->update(['status' => 'available']);

                foreach ($seatIds as $sId) {
                    try {
                        Redis::del("hold:showtime:{$booking->showtime_id}:seat:{$sId}");
                    } catch (\Exception $e) {
                        // Redis fallback
                    }
                }
            }
        }

        Log::info("booking:cancel-expired completed. Released {$count} expired bookings.");
        $this->info("Đã nhả ghế thành công cho {$count} đơn hàng.");

        return Command::SUCCESS;
    }
}
