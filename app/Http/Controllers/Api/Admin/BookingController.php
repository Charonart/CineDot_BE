<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\ShowtimeSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Danh sách tất cả đơn đặt vé (Admin) - Hỗ trợ Context Scoping
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Booking::with(['user', 'showtime.movie', 'showtime.room.cinema', 'bookingSeats.showtimeSeat', 'bookingCombos.combo']);

        // Data Scoping theo rạp/khu vực được phân quyền
        $user = $request->user();
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:booking', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $query->whereHas('showtime.room', function ($rq) use ($allowedCinemaIds) {
                    $rq->whereIn('cinema_id', $allowedCinemaIds);
                });
            }
        }

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
    public function show(string $id, Request $request)
    {
        $booking = Booking::with([
            'user',
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.showtimeSeat',
            'bookingCombos.combo',
            'voucher'
        ])->findOrFail($id);

        $user = $request->user();
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:booking', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $cinemaId = $booking->showtime?->room?->cinema_id;
                if (!in_array($cinemaId, $allowedCinemaIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền truy cập đơn đặt vé của rạp này.'
                    ], 403);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $booking
        ]);
    }

    /**
     * Xử lý hoàn tiền sự cố (Admin Refund)
     */
    public function refund(Request $request, string $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $booking = Booking::with('bookingSeats.showtimeSeat')->findOrFail($id);

        if (!in_array($booking->booking_status, ['paid', 'completed', 'cancelling'])) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể hoàn tiền cho đơn đã thanh toán hoặc đang yêu cầu hủy.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Dispatch refund job with reason
            \App\Jobs\ProcessRefundJob::dispatch($booking, $request->reason);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xử lý hoàn tiền thành công.',
                'data'    => $booking->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hoàn tiền: ' . $e->getMessage()
            ], 500);
        }
    }
}
