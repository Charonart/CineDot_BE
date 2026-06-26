<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Voucher;
use App\Models\UserVoucher;
use App\Models\PointHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointExchangeController extends Controller
{
    /**
     * Exchange user loyalty points for a voucher.
     */
    public function exchange(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|integer',
        ]);

        $voucherId = $request->voucher_id;
        $userId = $request->user()->user_id;

        return DB::transaction(function () use ($userId, $voucherId) {
            // 1. Pessimistic lock the user row to prevent race conditions on points
            $user = User::where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Lock the voucher row as well to safely verify active status and limits
            $voucher = Voucher::where('voucher_id', $voucherId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá không tồn tại hoặc đã bị ngừng hoạt động.'
                ], 400);
            }

            if (!$voucher->points_cost || $voucher->points_cost <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá này không áp dụng để quy đổi bằng điểm.'
                ], 400);
            }

            // 3. Calculate dynamic points cost based on user tier discount
            $userTier = $user->userTier;
            $discountPercent = $userTier ? $userTier->discount_percent : 0;
            $finalCost = (int) round($voucher->points_cost * (1 - $discountPercent / 100));

            // 4. Verify points balance
            if ($user->point < $finalCost) {
                return response()->json([
                    'success' => false,
                    'message' => "Bạn không đủ điểm để quy đổi voucher này. Cần {$finalCost} điểm, hiện tại bạn có {$user->point} điểm."
                ], 400);
            }

            // 5. Verify usage/exchange limit of the voucher
            if ($voucher->usage_limit !== null) {
                $exchangedCount = UserVoucher::where('voucher_id', $voucher->voucher_id)->count();
                if ($exchangedCount >= $voucher->usage_limit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mã giảm giá này đã hết lượt quy đổi.'
                    ], 400);
                }
            }

            // 6. Deduct points and create points log
            $user->decrement('point', $finalCost);

            PointHistory::create([
                'user_id' => $user->user_id,
                'amount' => -$finalCost,
                'action' => 'spend_voucher',
            ]);

            // 7. Grant the voucher to the user
            $userVoucher = UserVoucher::create([
                'user_id' => $user->user_id,
                'voucher_id' => $voucher->voucher_id,
                'is_used' => false,
                'exchanged_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đổi điểm lấy mã giảm giá thành công!',
                'data' => [
                    'currentPoints' => $user->point,
                    'userVoucherId' => $userVoucher->user_voucher_id,
                    'voucher' => [
                        'id' => $voucher->voucher_id,
                        'code' => $voucher->code,
                        'discountType' => $voucher->discount_type,
                        'discountValue' => $voucher->discount_value,
                        'pointsCost' => $voucher->points_cost,
                        'finalCost' => $finalCost,
                    ]
                ]
            ]);
        });
    }
}
