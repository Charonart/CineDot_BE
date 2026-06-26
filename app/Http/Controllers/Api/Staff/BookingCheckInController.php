<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class BookingCheckInController extends Controller
{
    /**
     * Staff scans QR code to check in a booking.
     */
    public function checkIn(Request $request, string $code)
    {
        $lockKey = "lock:checkin:{$code}";
        
        // 1. Redis Atomic Lock to prevent race condition
        $acquired = Redis::set($lockKey, true, 'NX', 'EX', 5);
        if (!$acquired) {
            return response()->json([
                'success' => false,
                'message' => 'Yêu cầu soát vé đang được xử lý, vui lòng thử lại.'
            ], 429);
        }

        try {
            return DB::transaction(function () use ($request, $code) {
                // 2. Fetch booking with locking
                $booking = Booking::with(['schedule.movie', 'schedule.room.cinema', 'checkedInBy'])
                    ->where('booking_code', $code)
                    ->lockForUpdate()
                    ->first();

                if (!$booking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin vé với mã soát vé này.'
                    ], 404);
                }

                // 3. Validate booking payment status
                if ($booking->booking_status !== 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chỉ có thể soát vé cho đơn hàng đã hoàn thành thanh toán (completed).'
                    ], 400);
                }

                // 4. Validate double check-in
                if ($booking->checked_in_at !== null) {
                    $staffName = $booking->checkedInBy ? $booking->checkedInBy->fullname : 'Nhân viên';
                    $formattedTime = Carbon::parse($booking->checked_in_at)->format('H:i d/m/Y');
                    return response()->json([
                        'success' => false,
                        'message' => "Vé này đã được soát trước đó vào lúc {$formattedTime} bởi {$staffName}."
                    ], 400);
                }

                // 5. Time Window Validation (from 45 mins before to 30 mins after show start time)
                $schedule = $booking->schedule;
                $scheduleDateStr = Carbon::parse($schedule->schedule_date)->format('Y-m-d');
                $showtime = Carbon::parse($scheduleDateStr . ' ' . $schedule->schedule_start);
                $now = Carbon::now();

                $windowStart = $showtime->copy()->subMinutes(45);
                $windowEnd = $showtime->copy()->addMinutes(30);

                if (!$now->between($windowStart, $windowEnd)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Thời gian soát vé không hợp lệ. Chỉ cho phép soát vé từ 45 phút trước giờ chiếu đến 30 phút sau khi phim bắt đầu.'
                    ], 403);
                }

                // 6. Location Validation
                $staff = $request->user();
                if ($staff->role === 'staff') {
                    $bookingCinemaId = $schedule->room->cinema_id;
                    if (!$staff->cinema_id || $staff->cinema_id !== $bookingCinemaId) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Nhân viên không được phép soát vé của cụm rạp khác.'
                        ], 403);
                    }
                }

                // 7. Update check-in record
                $booking->update([
                    'checked_in_at' => $now,
                    'checked_in_by' => $staff->user_id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Soát vé thành công.',
                    'data' => [
                        'bookingId' => $booking->booking_id,
                        'bookingCode' => $booking->booking_code,
                        'checkedInAt' => $booking->checked_in_at->toDateTimeString(),
                        'checkedInBy' => $staff->fullname,
                        'movieTitle' => $booking->schedule->movie->title,
                        'roomName' => $booking->schedule->room->room_name,
                        'cinemaName' => $booking->schedule->room->cinema->cinema_name,
                        'showtime' => $showtime->toDateTimeString(),
                    ]
                ]);
            });
        } finally {
            // Always release Redis atomic lock
            Redis::del($lockKey);
        }
    }
}
