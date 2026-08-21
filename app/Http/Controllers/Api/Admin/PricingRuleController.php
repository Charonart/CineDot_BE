<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
    /**
     * Danh sách quy tắc giá (Phân trang / Lọc / Tìm kiếm)
     */
    public function index(Request $request)
    {
        $query = PricingRule::query();

        if ($request->has('rule_category') && !empty($request->rule_category)) {
            $query->where('rule_category', $request->rule_category);
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        $query->orderByDesc('priority')->orderByDesc('pricing_rule_id');

        $perPage = (int) $request->input('per_page', 15);
        $rules = $query->paginate($perPage);

        $items = collect($rules->items())->map(function ($r) {
            $arr = $r->toArray();
            $arr['id'] = $r->pricing_rule_id;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $rules->currentPage(),
                'last_page'    => $rules->lastPage(),
                'per_page'     => $rules->perPage(),
                'total'        => $rules->total(),
            ]
        ]);
    }

    /**
     * Tạo mới quy tắc giá
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'rule_category'  => 'nullable|string|max:100',
            'conditions'     => 'nullable',
            'modifier_type'  => 'required|string|in:percentage,fixed_amount',
            'modifier_value' => 'required|numeric',
            'priority'       => 'nullable|integer',
            'is_active'      => 'nullable|boolean',
        ]);

        $conditions = $validated['conditions'] ?? [];
        if (is_string($conditions)) {
            $decoded = json_decode($conditions, true);
            if (is_array($decoded)) {
                $conditions = $decoded;
            }
        }

        $rule = PricingRule::create([
            'name'           => $validated['name'],
            'rule_category'  => $validated['rule_category'] ?? 'general',
            'conditions'     => $conditions,
            'modifier_type'  => $validated['modifier_type'],
            'modifier_value' => $validated['modifier_value'],
            'priority'       => $validated['priority'] ?? 0,
            'is_active'      => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo quy tắc giá thành công.',
            'data'    => $rule
        ], 201);
    }

    /**
     * Chi tiết quy tắc giá
     */
    public function show(string $id)
    {
        $rule = PricingRule::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $rule
        ]);
    }

    /**
     * Cập nhật quy tắc giá
     */
    public function update(Request $request, string $id)
    {
        $rule = PricingRule::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'sometimes|required|string|max:150',
            'rule_category'  => 'nullable|string|max:100',
            'conditions'     => 'nullable',
            'modifier_type'  => 'sometimes|required|string|in:percentage,fixed_amount',
            'modifier_value' => 'sometimes|required|numeric',
            'priority'       => 'nullable|integer',
            'is_active'      => 'nullable|boolean',
        ]);

        if (isset($validated['conditions']) && is_string($validated['conditions'])) {
            $decoded = json_decode($validated['conditions'], true);
            if (is_array($decoded)) {
                $validated['conditions'] = $decoded;
            }
        }

        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật quy tắc giá thành công.',
            'data'    => $rule->fresh()
        ]);
    }

    /**
     * Xóa quy tắc giá
     */
    public function destroy(string $id)
    {
        $rule = PricingRule::findOrFail($id);
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa quy tắc giá thành công.'
        ]);
    }

    /**
     * Bật / Tắt trạng thái hoạt động quy tắc giá
     */
    public function toggleActive(string $id)
    {
        $rule = PricingRule::findOrFail($id);
        $rule->update(['is_active' => !$rule->is_active]);

        return response()->json([
            'success' => true,
            'message' => $rule->is_active ? 'Đã kích hoạt quy tắc giá.' : 'Đã tạm dừng quy tắc giá.',
            'data'    => $rule->fresh()
        ]);
    }
}
