<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HoldSeatsRequest;
use App\Services\BookingService;
use Illuminate\Http\Request;
use App\Models\Booking;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    public function holdSeats(HoldSeatsRequest $request)
    {
        $combos = $request->input('combos', []);
        
        $booking = $this->bookingService->holdSeats(
            $request->user()->user_id,
            $request->schedule_id,
            $request->schedule_seat_ids,
            $combos
        );

        return response()->json([
            'success' => true,
            'message' => 'Giữ ghế thành công, vui lòng thanh toán trong 10 phút.',
            'data'    => $booking
        ]);
    }

    public function show($id, Request $request)
    {
        $booking = Booking::with(['schedule.movie', 'bookingSeats.scheduleSeat.seat', 'schedule.room.cinema', 'bookingCombos.combo', 'voucher'])
            ->where('user_id', $request->user()->user_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $booking
        ]);
    }

    public function myBookings(Request $request)
    {
        $bookings = Booking::with(['schedule.movie', 'schedule.room.cinema', 'bookingCombos.combo', 'voucher'])
            ->where('user_id', $request->user()->user_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $bookings
        ]);
    }

    /**
     * Cancel booking and request refund.
     */
    public function cancel(Request $request, $id)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id) {
            // 1. Fetch booking with locking
            $booking = Booking::where('user_id', $request->user()->user_id)
                ->lockForUpdate()
                ->findOrFail($id);

            // 2. Validate booking status (must be completed)
            if ($booking->booking_status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể hủy đơn đặt vé ở trạng thái đã hoàn thành (completed).'
                ], 400);
            }

            // 3. Verify showtime limits
            $schedule = $booking->schedule;
            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin suất chiếu.'
                ], 400);
            }

            $scheduleDateStr = \Carbon\Carbon::parse($schedule->schedule_date)->format('Y-m-d');
            $showtime = \Carbon\Carbon::parse($scheduleDateStr . ' ' . $schedule->schedule_start);
            $now = \Carbon\Carbon::now();

            if ($now->greaterThanOrEqualTo($showtime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suất chiếu đã bắt đầu, không thể hủy vé.'
                ], 400);
            }

            $diffInHours = $now->diffInHours($showtime, false);

            if ($diffInHours < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thời gian còn lại đến suất chiếu dưới 2 tiếng, không được phép hủy vé.'
                ], 400);
            }

            // 4. Check negative point dilemma (earn points calculated from total_amount)
            $user = $request->user();
            $earnedPoints = (int) round($booking->total_amount / 10000);
            
            // Reload user fresh with locking to get current points
            $user = \App\Models\User::where('user_id', $user->user_id)->lockForUpdate()->firstOrFail();
            if ($user->point < $earnedPoints) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể hủy vé vì bạn đã sử dụng số điểm thưởng tích lũy được từ giao dịch này.'
                ], 400);
            }

            // 5. Tiered refund calculation
            $refundPercentage = 100;
            $returnVoucher = true;

            if ($diffInHours >= 2 && $diffInHours < 24) {
                // Tier 2: 2h - 24h before showtime -> 80% refund, forfeit voucher
                $refundPercentage = 80;
                $returnVoucher = false;
            }

            // 6. Update booking status to 'cancelling'
            $booking->update(['booking_status' => 'cancelling']);

            // 7. Dispatch background refund job
            \App\Jobs\ProcessRefundJob::dispatch($booking->booking_id, $refundPercentage, $returnVoucher);

            return response()->json([
                'success' => true,
                'message' => 'Yêu cầu hủy vé đang được xử lý hoàn tiền.',
                'data' => [
                    'bookingId' => $booking->booking_id,
                    'bookingStatus' => 'cancelling',
                    'refundPercentage' => $refundPercentage,
                    'returnVoucher' => $returnVoucher
                ]
            ]);
        });
    }
}
