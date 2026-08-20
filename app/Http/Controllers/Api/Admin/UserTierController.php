<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserTier;
use Illuminate\Http\Request;

class UserTierController extends Controller
{
    /**
     * Danh sách cấp bậc hội viên kèm số lượng thành viên đạt được
     * GET /api/v1/admin/user-tiers
     */
    public function index()
    {
        $tiers = UserTier::orderBy('min_points', 'asc')->get();

        $tierData = $tiers->map(function ($tier, $index) use ($tiers) {
            $nextTier = $tiers->get($index + 1);

            // Tính số lượng user thuộc khoảng điểm của tier này
            $userCountQuery = User::where('total_points', '>=', $tier->min_points);
            if ($nextTier) {
                $userCountQuery->where('total_points', '<', $nextTier->min_points);
            }
            $membersCount = $userCountQuery->count();

            return [
                'id'               => $tier->user_tier_id,
                'user_tier_id'     => $tier->user_tier_id,
                'tier'             => $tier->tier,
                'name'             => $tier->tier,
                'min_points'       => (int) $tier->min_points,
                'discount_percent' => (float) $tier->discount_percent,
                'members_count'    => $membersCount,
                'created_at'       => $tier->created_at?->toISOString(),
                'updated_at'       => $tier->updated_at?->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $tierData,
            'meta'    => [
                'current_page' => 1,
                'last_page'    => 1,
                'per_page'     => $tierData->count(),
                'total'        => $tierData->count(),
            ]
        ]);
    }

    /**
     * Tạo cấp bậc hội viên mới
     * POST /api/v1/admin/user-tiers
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tier'             => 'required|string|max:50|unique:user_tiers,tier',
            'min_points'       => 'required|integer|min:0',
            'discount_percent' => 'required|numeric|min:0|max:100',
        ]);

        $tier = UserTier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo cấp bậc hội viên mới thành công.',
            'data'    => [
                'id'               => $tier->user_tier_id,
                'user_tier_id'     => $tier->user_tier_id,
                'tier'             => $tier->tier,
                'name'             => $tier->tier,
                'min_points'       => (int) $tier->min_points,
                'discount_percent' => (float) $tier->discount_percent,
                'members_count'    => 0,
            ]
        ], 201);
    }

    /**
     * Cập nhật chính sách cấp bậc
     * PUT /api/v1/admin/user-tiers/{id}
     */
    public function update(Request $request, $id)
    {
        $tier = UserTier::findOrFail($id);

        $validated = $request->validate([
            'tier'             => 'sometimes|required|string|max:50|unique:user_tiers,tier,' . $tier->user_tier_id . ',user_tier_id',
            'min_points'       => 'sometimes|required|integer|min:0',
            'discount_percent' => 'sometimes|required|numeric|min:0|max:100',
        ]);

        $tier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật chính sách cấp bậc thành công.',
            'data'    => [
                'id'               => $tier->user_tier_id,
                'user_tier_id'     => $tier->user_tier_id,
                'tier'             => $tier->tier,
                'name'             => $tier->tier,
                'min_points'       => (int) $tier->min_points,
                'discount_percent' => (float) $tier->discount_percent,
            ]
        ]);
    }

    /**
     * Xóa cấp bậc hội viên
     * DELETE /api/v1/admin/user-tiers/{id}
     */
    public function destroy($id)
    {
        $tier = UserTier::findOrFail($id);

        if (UserTier::count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Hệ thống cần duy trì tối thiểu 1 cấp bậc hội viên.',
            ], 422);
        }

        $tier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa cấp bậc hội viên thành công.',
        ]);
    }
}
