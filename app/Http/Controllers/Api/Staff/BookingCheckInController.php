<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingCombo;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class BookingCheckInController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    /**
     * Staff scans QR code to check in a booking via POST /staff/check-in.
     */
    public function checkInByQr(Request $request)
    {
        $code = $request->input('qr_code', $request->input('booking_code', $request->input('code')));
        if (empty($code)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng cung cấp mã QR hoặc mã vé.'
            ], 422);
        }

        return $this->checkIn($request, $code);
    }

    /**
     * Staff confirms F&B combo claim for customer via POST /staff/fnb/claim.
     */
    public function claimFnb(Request $request)
    {
        $detailId = $request->input('booking_detail_id', $request->input('booking_combo_id'));
        if (empty($detailId)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã booking_detail_id là bắt buộc.'
            ], 422);
        }

        $bookingCombo = BookingCombo::find($detailId);
        if (!$bookingCombo) {
            return response()->json([
                'success' => false,
                'message' => 'Combo bắp nước không tồn tại.'
            ], 404);
        }

        if ($bookingCombo->is_claimed) {
            return response()->json([
                'success' => false,
                'message' => 'Combo bắp nước này đã được nhận trước đó.'
            ], 422);
        }

        $bookingCombo->update(['is_claimed' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Xác nhận trả Combo Bắp Nước thành công (is_claimed = true).'
        ]);
    }

    /**
     * Direct POS counter sale via POST /staff/pos/create-order.
     */
    public function createPosOrder(Request $request)
    {
        $showtimeId = $request->input('showtime_id');
        $showtimeSeatIds = $request->input('showtime_seat_ids', []);
        $combos = $request->input('combos', []);

        $booking = $this->bookingService->holdSeats(
            $request->user()->user_id,
            $showtimeId,
            $showtimeSeatIds,
            $combos
        );

        $confirmed = $this->bookingService->confirmBooking($booking->booking_id);

        return response()->json([
            'success' => true,
            'message' => 'Bán vé & bắp nước tại quầy POS thành công!',
            'data'    => $confirmed
        ], 201);
    }

    /**
     * Staff scans QR code to check in a booking.
     */
    public function checkIn(Request $request, string $code)
    {
        $lockKey = "lock:checkin:{$code}";
        
        $acquired = Redis::set($lockKey, true, 'NX', 'EX', 5);
        if (!$acquired) {
            return response()->json([
                'success' => false,
                'message' => 'Yêu cầu soát vé đang được xử lý, vui lòng thử lại.'
            ], 429);
        }

        try {
            return DB::transaction(function () use ($request, $code) {
                $booking = Booking::with(['showtime.movie', 'showtime.room.cinema'])
                    ->where('booking_code', $code)
                    ->lockForUpdate()
                    ->first();

                if (!$booking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin vé với mã soát vé này.'
                    ], 404);
                }

                if (!in_array($booking->booking_status, ['completed', 'paid'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chỉ có thể soát vé cho đơn hàng đã hoàn thành thanh toán (completed).'
                    ], 400);
                }

                if ($booking->checked_in_at !== null) {
                    $formattedTime = Carbon::parse($booking->checked_in_at)->format('H:i d/m/Y');
                    return response()->json([
                        'success' => false,
                        'message' => "Vé này đã được soát trước đó vào lúc {$formattedTime}."
                    ], 400);
                }

                $staff = $request->user();
                $booking->update([
                    'checked_in_at' => now(),
                    'checked_in_by' => $staff->user_id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Soát vé thành công.',
                    'data' => [
                        'bookingId' => $booking->booking_id,
                        'bookingCode' => $booking->booking_code,
                        'checkedInAt' => now()->toDateTimeString(),
                        'checkedInBy' => $staff->fullname ?? $staff->username,
                        'movieTitle' => $booking->showtime->movie->title ?? '',
                        'roomName' => $booking->showtime->room->room_name ?? '',
                        'cinemaName' => $booking->showtime->room->cinema->cinema_name ?? '',
                    ]
                ]);
            });
        } finally {
            Redis::del($lockKey);
        }
    }
}
