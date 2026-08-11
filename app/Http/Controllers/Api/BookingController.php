<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateSummaryRequest;
use App\Http\Requests\HoldSeatsRequest;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\PricingEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private PricingEngineService $pricingEngineService
    ) {
    }

    public function holdSeats(HoldSeatsRequest $request)
    {
        $showtimeId = $request->input('showtime_id', $request->input('schedule_id'));
        $showtimeSeatIds = $request->input('showtime_seat_ids', $request->input('schedule_seat_ids'));
        $combos = $request->input('combos', []);
        $voucherCode = $request->input('voucher_code');
        $pointsUsed = (int) $request->input('points_used', 0);

        $booking = $this->bookingService->holdSeats(
            $request->user()->user_id,
            $showtimeId,
            $showtimeSeatIds,
            $combos,
            $voucherCode,
            $pointsUsed
        );

        $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);
        $ttlMinutes = (int) ceil($ttlSeconds / 60);
        $expiresAt = \Carbon\Carbon::now()->addSeconds($ttlSeconds)->toIso8601String();

        return response()->json([
            'success' => true,
            'message' => "Đã giữ " . count($showtimeSeatIds) . " ghế thành công trong {$ttlMinutes} phút.",
            'data'    => [
                'booking_id'        => $booking->booking_id,
                'booking_code'      => $booking->booking_code,
                'showtime_id'       => (int) $showtimeId,
                'showtime_seat_ids' => array_map('intval', (array) $showtimeSeatIds),
                'expires_at'        => $expiresAt,
            ]
        ]);
    }

    public function releaseSeats(HoldSeatsRequest $request)
    {
        $showtimeId = $request->input('showtime_id', $request->input('schedule_id'));
        $showtimeSeatIds = $request->input('showtime_seat_ids', $request->input('schedule_seat_ids'));

        $this->bookingService->releaseSeats(
            $request->user()->user_id,
            $showtimeId,
            $showtimeSeatIds
        );

        return response()->json([
            'success' => true,
            'message' => 'Hủy giữ ghế thành công.'
        ]);
    }

    public function calculateSummary(CalculateSummaryRequest $request)
    {
        $showtimeId = $request->input('showtime_id', $request->input('schedule_id'));
        $showtimeSeatIds = $request->input('showtime_seat_ids', $request->input('schedule_seat_ids'));
        $combos = $request->input('combos', []);
        $voucherCode = $request->input('voucher_code');
        $pointsUsed = (int) $request->input('points_used', 0);

        $summary = $this->pricingEngineService->calculateSummary(
            $showtimeId,
            $showtimeSeatIds,
            $combos,
            $voucherCode,
            $pointsUsed,
            $request->user()
        );

        return response()->json($summary);
    }

    public function show($id, Request $request)
    {
        $booking = Booking::with([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.showtimeSeat.seatType',
            'bookingCombos.combo',
            'voucher'
        ])
        ->where('user_id', $request->user()->user_id)
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $booking
        ]);
    }

    public function myBookings(Request $request)
    {
        $bookings = Booking::with([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingCombos.combo',
            'voucher'
        ])
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
        return DB::transaction(function () use ($request, $id) {
            $booking = Booking::where('user_id', $request->user()->user_id)
                ->lockForUpdate()
                ->findOrFail($id);

            if (!in_array($booking->booking_status, ['completed', 'paid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể hủy đơn đặt vé ở trạng thái đã hoàn thành (completed).'
                ], 400);
            }

            $showtime = $booking->showtime;
            if (!$showtime) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin suất chiếu.'
                ], 400);
            }

            $showtimeStart = \Carbon\Carbon::parse($showtime->showtime_start);
            $now = \Carbon\Carbon::now();

            if ($now->greaterThanOrEqualTo($showtimeStart)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suất chiếu đã bắt đầu, không thể hủy vé.'
                ], 400);
            }

            $diffInHours = $now->diffInHours($showtimeStart, false);

            if ($diffInHours < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thời gian còn lại đến suất chiếu dưới 2 tiếng, không được phép hủy vé.'
                ], 400);
            }

            $user = $request->user();
            $earnedPoints = (int) round(($booking->final_amount ?? 0) / 10000);
            
            $user = \App\Models\User::where('user_id', $user->user_id)->lockForUpdate()->firstOrFail();
            if ($user->point < $earnedPoints) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể hủy vé vì bạn đã sử dụng số điểm thưởng tích lũy được từ giao dịch này.'
                ], 400);
            }

            $refundPercentage = 100;
            $returnVoucher = true;

            if ($diffInHours >= 2 && $diffInHours < 24) {
                $refundPercentage = 80;
                $returnVoucher = false;
            }

            $booking->update(['booking_status' => 'cancelling']);

            if (class_exists('\App\Jobs\ProcessRefundJob')) {
                \App\Jobs\ProcessRefundJob::dispatch($booking->booking_id, $refundPercentage, $returnVoucher);
            }

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
