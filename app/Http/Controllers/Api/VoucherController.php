<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Voucher;
use App\Services\PricingEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VoucherController extends Controller
{
    public function __construct(private PricingEngineService $pricingEngineService)
    {
    }

    /**
     * Apply a voucher to a pending booking.
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

            $voucher = Voucher::where('code', strtoupper($request->voucher_code))
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$voucher) {
                return response()->json(['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn.'], 400);
            }

            $now = Carbon::now();
            if ($voucher->valid_from && $now->lt($voucher->valid_from)) {
                return response()->json(['success' => false, 'message' => 'Mã giảm giá chưa đến thời gian áp dụng.'], 400);
            }

            if ($voucher->valid_until && $now->gt($voucher->valid_until)) {
                return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết hạn sử dụng.'], 400);
            }

            if ($voucher->system_limit !== null && $voucher->used_count >= $voucher->system_limit) {
                return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng trong hệ thống.'], 400);
            }

            // Extract items from price_breakdown if present to recompute summary with PricingEngineService
            $breakdown = $booking->price_breakdown;
            $showtimeSeatIds = $booking->bookingSeats()->pluck('showtime_seat_id')->toArray();
            $combos = $booking->bookingCombos()->get()->map(function ($c) {
                return [
                    'combo_id' => $c->combo_id,
                    'quantity' => $c->quantity,
                ];
            })->toArray();

            $summary = $this->pricingEngineService->calculateSummary(
                $booking->showtime_id,
                $showtimeSeatIds,
                $combos,
                $voucher->code,
                $request->user(),
                $booking->booking_code
            );

            $booking->update([
                'voucher_id'      => $voucher->voucher_id,
                'price_breakdown' => $summary,
                'discount_amount' => $summary['financial_breakdown']['total_discount_amount'],
                'final_amount'    => $summary['financial_breakdown']['final_amount_to_pay'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Áp dụng mã giảm giá thành công!',
                'data'    => $booking->load('voucher')
            ]);
        });
    }

    /**
     * Remove a voucher from a pending booking.
     */
    public function remove(Request $request, $bookingId)
    {
        return DB::transaction(function () use ($request, $bookingId) {
            $booking = Booking::where('user_id', $request->user()->user_id)
                ->where('booking_status', 'pending')
                ->lockForUpdate()
                ->findOrFail($bookingId);

            $showtimeSeatIds = $booking->bookingSeats()->pluck('showtime_seat_id')->toArray();
            $combos = $booking->bookingCombos()->get()->map(function ($c) {
                return [
                    'combo_id' => $c->combo_id,
                    'quantity' => $c->quantity,
                ];
            })->toArray();

            $summary = $this->pricingEngineService->calculateSummary(
                $booking->showtime_id,
                $showtimeSeatIds,
                $combos,
                null,
                $request->user(),
                $booking->booking_code
            );

            $booking->update([
                'voucher_id'      => null,
                'price_breakdown' => $summary,
                'discount_amount' => $summary['financial_breakdown']['total_discount_amount'],
                'final_amount'    => $summary['financial_breakdown']['final_amount_to_pay'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã gỡ mã giảm giá thành công.',
                'data'    => $booking->load('voucher')
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

        $voucher = Voucher::where('code', strtoupper($code))->where('is_active', true)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Mã Voucher không tồn tại hoặc đã hết hạn.'
            ], 404);
        }

        $now = Carbon::now();
        if ($voucher->valid_from && $now->lt($voucher->valid_from)) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá chưa đến thời gian áp dụng.'], 400);
        }

        if ($voucher->valid_until && $now->gt($voucher->valid_until)) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết hạn sử dụng.'], 400);
        }

        if ($voucher->system_limit !== null && $voucher->used_count >= $voucher->system_limit) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng trong hệ thống.'], 400);
        }

        if ($voucher->min_order_value && $orderAmount < (float) $voucher->min_order_value) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($voucher->min_order_value) . 'đ để áp dụng voucher này.'
            ], 400);
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

    /**
     * List active available vouchers for members.
     */
    public function listActive(Request $request)
    {
        $now = Carbon::now();

        $vouchers = Voucher::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->orderByDesc('created_at')
            ->get();

        $mapped = $vouchers->map(function ($v) {
            $formattedDiscount = $v->discount_type === 'fixed_amount'
                ? number_format($v->discount_value) . 'đ'
                : $v->discount_value . '%';

            return [
                'id'                 => $v->voucher_id,
                'code'               => $v->code,
                'title'              => $v->title ?: ('Voucher Giảm ' . $formattedDiscount),
                'description'        => $v->description ?: ('Giảm ' . $formattedDiscount . ($v->min_order_value ? ' cho đơn từ ' . number_format($v->min_order_value) . 'đ' : '')),
                'discount_type'      => $v->discount_type,
                'discount_value'     => (float) $v->discount_value,
                'min_order_value'    => (float) ($v->min_order_value ?? 0),
                'max_discount_value' => (float) ($v->max_discount_value ?? 0),
                'valid_until'        => $v->valid_until?->format('d/m/Y') ?: 'Không thời hạn',
                'is_active'          => (bool) $v->is_active,
                'voucher_type'       => $v->voucher_type ?: 'all',
                'category'           => $v->voucher_type === 'combo' ? 'FNB' : 'TICKET',
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $mapped,
        ]);
    }
}
