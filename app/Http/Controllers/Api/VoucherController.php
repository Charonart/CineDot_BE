<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VoucherController extends Controller
{
    public function apply(Request $request, $bookingId)
    {
        $request->validate([
            'voucher_code' => 'required|string'
        ]);

        return DB::transaction(function () use ($request, $bookingId) {
            $booking = Booking::where('user_id', $request->user()->user_id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->findOrFail($bookingId);

            $voucher = Voucher::where('code', $request->voucher_code)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$voucher) {
                return response()->json(['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn.'], 400);
            }

            $now = Carbon::now();
            if ($now->lt($voucher->valid_from) || $now->gt($voucher->valid_until)) {
                return response()->json(['success' => false, 'message' => 'Mã giảm giá không trong thời gian sử dụng.'], 400);
            }

            if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
                return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.'], 400);
            }

            // Restore previous discount if there was a voucher applied before
            $originalTotal = $booking->total_amount + $booking->discount_amount;

            if ($originalTotal < $voucher->min_order_value) {
                return response()->json(['success' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã này.'], 400);
            }

            $discount = 0;
            if ($voucher->discount_type === 'fixed') {
                $discount = $voucher->discount_value;
            } else {
                $discount = intval($originalTotal * ($voucher->discount_value / 100));
                if ($voucher->max_discount_value && $discount > $voucher->max_discount_value) {
                    $discount = $voucher->max_discount_value;
                }
            }

            // Ensure discount doesn't exceed total amount
            if ($discount > $originalTotal) {
                $discount = $originalTotal;
            }

            $booking->update([
                'voucher_id' => $voucher->voucher_id,
                'discount_amount' => $discount,
                'total_amount' => $originalTotal - $discount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Áp dụng mã giảm giá thành công!',
                'data' => $booking->load('voucher')
            ]);
        });
    }

    public function remove(Request $request, $bookingId)
    {
        return DB::transaction(function () use ($request, $bookingId) {
            $booking = Booking::where('user_id', $request->user()->user_id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->findOrFail($bookingId);

            if (!$booking->voucher_id) {
                return response()->json(['success' => false, 'message' => 'Đơn hàng chưa áp dụng mã giảm giá nào.'], 400);
            }

            $originalTotal = $booking->total_amount + $booking->discount_amount;

            $booking->update([
                'voucher_id' => null,
                'discount_amount' => 0,
                'total_amount' => $originalTotal
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã gỡ mã giảm giá.',
                'data' => $booking
            ]);
        });
    }
}
