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
        // Lấy thời điểm cách đây 10 phút
        $expiredTime = Carbon::now()->subMinutes(10);

        $expiredBookings = Booking::where('booking_status', 'pending')
            ->where('created_at', '<', $expiredTime)
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('No expired bookings found.');
            return 0;
        }

        $count = 0;
        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking, &$count) {
                $booking->update(['booking_status' => 'cancelled']);
                $count++;
            });
            $this->info("Cancelled booking ID: {$booking->booking_id}");
        }

        $this->info("Successfully cancelled {$count} expired bookings.");
        return 0;
    }
}
