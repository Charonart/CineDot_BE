<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Voucher;
use App\Models\UserVoucher;
use App\Models\BookingVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VoucherController extends Controller
{
    /**
     * Apply a voucher to a booking.
     */
    public function apply(Request $request, $bookingId)
    {
        $request->validate([
            'voucher_code' => 'required|string'
        ]);

        return DB::transaction(function () use ($request, $bookingId) {
            $booking = Booking::where('user_id', $request->user()->user_id)
                ->where('booking_status', 'pending')
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

            // 1. Check if the voucher has already been applied to this booking
            $alreadyApplied = BookingVoucher::where('booking_id', $booking->booking_id)
                ->where('voucher_id', $voucher->voucher_id)
                ->exists();

            if ($alreadyApplied) {
                return response()->json(['success' => false, 'message' => 'Mã giảm giá này đã được áp dụng cho đơn hàng.'], 400);
            }

            // 2. Check general voucher usage limit
            if ($voucher->usage_limit !== null) {
                // Count bookings using this voucher that are confirmed/completed
                $usedCount = BookingVoucher::where('voucher_id', $voucher->voucher_id)
                    ->whereHas('booking', function ($q) {
                        $q->where('booking_status', '!=', 'cancelled');
                    })
                    ->count();

                if ($usedCount >= $voucher->usage_limit) {
                    return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.'], 400);
                }
            }

            // 3. Stacking Matrix (Rule Matrix check)
            $appliedVouchers = BookingVoucher::with('voucher')
                ->where('booking_id', $booking->booking_id)
                ->get();

            foreach ($appliedVouchers as $av) {
                $appliedV = $av->voucher;

                // Check if already applied voucher excludes the new one
                if ($appliedV->combinable_rules) {
                    $excludeTypes = $appliedV->combinable_rules['exclude_types'] ?? [];
                    $excludeCodes = $appliedV->combinable_rules['exclude_codes'] ?? [];
                    if (in_array($voucher->voucher_type, $excludeTypes) || in_array($voucher->code, $excludeCodes)) {
                        return response()->json([
                            'success' => false,
                            'message' => "Không thể kết hợp mã giảm giá này với mã '{$appliedV->code}' đã áp dụng."
                        ], 400);
                    }
                }

                // Check if the new voucher excludes any of the currently applied ones
                if ($voucher->combinable_rules) {
                    $excludeTypes = $voucher->combinable_rules['exclude_types'] ?? [];
                    $excludeCodes = $voucher->combinable_rules['exclude_codes'] ?? [];
                    if (in_array($appliedV->voucher_type, $excludeTypes) || in_array($appliedV->code, $excludeCodes)) {
                        return response()->json([
                            'success' => false,
                            'message' => "Mã giảm giá này xung đột với mã '{$appliedV->code}' đã áp dụng."
                        ], 400);
                    }
                }
            }

            // 4. Check Point Exchange Voucher Ownership
            if ($voucher->points_cost && $voucher->points_cost > 0) {
                $userVoucher = UserVoucher::where('user_id', $request->user()->user_id)
                    ->where('voucher_id', $voucher->voucher_id)
                    ->where('is_used', false)
                    ->first();

                if (!$userVoucher) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn cần đổi điểm thưởng lấy mã giảm giá này trước khi sử dụng.'
                    ], 400);
                }
            }

            // 5. Calculate discount
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

            // Ensure discount doesn't exceed original total
            if ($discount > $originalTotal) {
                $discount = $originalTotal;
            }

            // 6. Record applied voucher
            BookingVoucher::create([
                'booking_id' => $booking->booking_id,
                'voucher_id' => $voucher->voucher_id,
                'discount_amount_applied' => $discount,
            ]);

            // Recalculate total discount from all applied booking vouchers
            $newTotalDiscount = BookingVoucher::where('booking_id', $booking->booking_id)->sum('discount_amount_applied');
            if ($newTotalDiscount > $originalTotal) {
                $newTotalDiscount = $originalTotal;
            }

            $booking->update([
                'voucher_id' => $voucher->voucher_id,
                'discount_amount' => $newTotalDiscount,
                'total_amount' => $originalTotal - $newTotalDiscount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Áp dụng mã giảm giá thành công!',
                'data' => $booking->load(['voucher', 'bookingVouchers.voucher'])
            ]);
        });
    }

    /**
     * Remove a voucher (or all vouchers) from a booking.
     */
    public function remove(Request $request, $bookingId)
    {
        $voucherCode = $request->input('voucher_code');

        return DB::transaction(function () use ($request, $bookingId, $voucherCode) {
            $booking = Booking::where('user_id', $request->user()->user_id)
                ->where('booking_status', 'pending')
                ->lockForUpdate()
                ->findOrFail($bookingId);

            $originalTotal = $booking->total_amount + $booking->discount_amount;

            if ($voucherCode) {
                $voucher = Voucher::where('code', $voucherCode)->first();
                if (!$voucher) {
                    return response()->json(['success' => false, 'message' => 'Mã giảm giá không hợp lệ.'], 400);
                }

                $deleted = BookingVoucher::where('booking_id', $booking->booking_id)
                    ->where('voucher_id', $voucher->voucher_id)
                    ->delete();

                if (!$deleted) {
                    return response()->json(['success' => false, 'message' => 'Mã giảm giá chưa được áp dụng cho đơn hàng này.'], 400);
                }
            } else {
                BookingVoucher::where('booking_id', $booking->booking_id)->delete();
            }

            $newTotalDiscount = BookingVoucher::where('booking_id', $booking->booking_id)->sum('discount_amount_applied');
            $latestApplied = BookingVoucher::where('booking_id', $booking->booking_id)->orderBy('id', 'desc')->first();

            $booking->update([
                'voucher_id' => $latestApplied ? $latestApplied->voucher_id : null,
                'discount_amount' => $newTotalDiscount,
                'total_amount' => $originalTotal - $newTotalDiscount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã gỡ mã giảm giá thành công.',
                'data' => $booking->load(['bookingVouchers.voucher'])
            ]);
        });
    }

    /**
     * Standalone voucher validation via POST /vouchers/apply.
     */
    public function applyStandalone(Request $request)
    {
        $code = $request->input('voucher_code', $request->input('code'));
        $orderAmount = (float) $request->input('order_amount', $request->input('booking_amount', 0));

        $voucher = Voucher::where('code', $code)->where('is_active', true)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Mã Voucher không tồn tại hoặc đã hết hạn.'
            ], 404);
        }

        $discount = 0;
        if ($voucher->discount_type === 'fixed_amount') {
            $discount = (float) $voucher->discount_value;
        } else {
            $discount = $orderAmount * ((float) $voucher->discount_value / 100);
            if ($voucher->max_discount_value) {
                $discount = min($discount, (float) $voucher->max_discount_value);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Áp dụng Voucher hợp lệ',
            'data' => [
                'voucher_code'    => $voucher->code,
                'discount_amount' => (int) round($discount)
            ]
        ]);
    }
}

