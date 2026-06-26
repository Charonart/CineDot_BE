<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\BookingSeat;
use App\Models\ScheduleSeat;
use App\Models\PointHistory;
use App\Http\Resources\AdminPaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Payment::with(['booking.user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('method')) {
            $query->where('method', $request->method);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'ilike', '%' . $search . '%')
                    ->orWhereHas('booking', function ($bq) use ($search) {
                        $bq->where('booking_code', 'ilike', '%' . $search . '%')
                            ->orWhereHas('user', function ($uq) use ($search) {
                                $uq->where('fullname', 'ilike', '%' . $search . '%')
                                    ->orWhere('email', 'ilike', '%' . $search . '%');
                            });
                    });
            });
        }

        $payments = $query->orderBy('payment_id', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'page' => $payments->currentPage(),
                'results' => AdminPaymentResource::collection($payments->items()),
                'totalPages' => $payments->lastPage(),
                'totalResults' => $payments->total(),
            ]
        ]);
    }

    /**
     * Perform refund for a payment.
     */
    public function refund(string $id)
    {
        return DB::transaction(function () use ($id) {
            $payment = Payment::lockForUpdate()->findOrFail($id);

            if ($payment->status !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể hoàn tiền cho giao dịch thanh toán thành công.'
                ], 400);
            }

            $booking = $payment->booking;
            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng tương ứng với giao dịch này.'
                ], 400);
            }

            // 1. Cập nhật trạng thái Payment
            $payment->update(['status' => 'refunded']);

            // 2. Cập nhật trạng thái Booking
            $booking->update(['booking_status' => 'cancelled']);

            // 3. Giải phóng ghế
            $scheduleSeatIds = BookingSeat::where('booking_id', $booking->booking_id)->pluck('schedule_seat_id');
            if ($scheduleSeatIds->count() > 0) {
                ScheduleSeat::whereIn('schedule_seat_id', $scheduleSeatIds)->update(['status' => 'available']);
            }

            // 4. Thu hồi điểm thưởng của user
            $user = $booking->user;
            if ($user) {
                $earnedPoints = (int) round($booking->total_amount / 10000);
                if ($earnedPoints > 0) {
                    $user->decrement('point', $earnedPoints);

                    PointHistory::create([
                        'user_id' => $user->user_id,
                        'booking_id' => $booking->booking_id,
                        'amount' => -$earnedPoints,
                        'action' => 'deduct_refund',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Hoàn tiền và hủy đơn hàng thành công.',
                'data' => new AdminPaymentResource($payment->load('booking.user'))
            ]);
        });
    }
}
