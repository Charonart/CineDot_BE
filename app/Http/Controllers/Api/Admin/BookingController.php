<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\ShowtimeSeat;
use App\Models\PointHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Danh sách tất cả đơn đặt vé (Admin)
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Booking::with(['user', 'showtime.movie', 'showtime.room.cinema', 'bookingSeats.showtimeSeat', 'bookingCombos.combo']);

        if ($request->has('status')) {
            $query->where('booking_status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'ilike', '%' . $search . '%')
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('fullname', 'ilike', '%' . $search . '%')
                        ->orWhere('email', 'ilike', '%' . $search . '%')
                        ->orWhere('phone', 'ilike', '%' . $search . '%');
                  });
            });
        }

        $bookings = $query->orderBy('booking_id', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => $bookings
        ]);
    }

    /**
     * Chi tiết đơn đặt vé (Admin)
     */
    public function show(string $id)
    {
        $booking = Booking::with(['user', 'showtime.movie', 'showtime.room.cinema', 'bookingSeats.showtimeSeat', 'bookingCombos.combo', 'payments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $booking
        ]);
    }

    /**
     * Xử lý hoàn tiền / Hủy vé sự cố (POST /admin/bookings/{id}/refund)
     */
    public function refund(Request $request, string $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($id, $request) {
            $booking = Booking::lockForUpdate()->findOrFail($id);

            if ($booking->booking_status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn đặt vé này đã được hủy trước đó.'
                ], 400);
            }

            // 1. Cập nhật trạng thái Booking
            $booking->update([
                'booking_status' => 'cancelled',
                'cancel_reason'  => $request->reason ?? 'Admin hủy vé & hoàn tiền',
            ]);

            // 2. Cập nhật trạng thái các Payment liên quan
            $booking->payments()->where('status', 'success')->update(['status' => 'refunded']);

            // 3. Giải phóng ghế về trạng thái available
            $showtimeSeatIds = BookingSeat::where('booking_id', $booking->booking_id)->pluck('showtime_seat_id');
            if ($showtimeSeatIds->count() > 0) {
                ShowtimeSeat::whereIn('showtime_seat_id', $showtimeSeatIds)->update(['status' => 'available']);
            }

            // 4. Trừ lại điểm tích lũy nếu có
            $user = $booking->user;
            if ($user) {
                $earnedPoints = (int) round($booking->total_amount / 10000);
                if ($earnedPoints > 0) {
                    $user->decrement('point', $earnedPoints);

                    PointHistory::create([
                        'user_id'    => $user->user_id,
                        'booking_id' => $booking->booking_id,
                        'amount'     => -$earnedPoints,
                        'action'     => 'deduct_refund',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã khởi chạy Job hoàn tiền cho đơn đặt vé.'
            ]);
        });
    }
}
