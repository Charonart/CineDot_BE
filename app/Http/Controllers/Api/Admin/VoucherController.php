<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Http\Requests\Admin\StoreVoucherRequest;
use App\Http\Requests\Admin\UpdateVoucherRequest;
use App\Http\Resources\AdminVoucherResource;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the resource (Standardized with FilterableAndSortable).
     */
    public function index(Request $request)
    {
        $allowedFilters = ['code', 'title', 'campaign_id', 'voucher_type', 'discount_type', 'discount_value', 'min_order_value', 'valid_from', 'valid_until', 'is_active', 'used_count'];
        $allowedSorts = ['voucher_id', 'id', 'code', 'title', 'discount_value', 'valid_from', 'valid_until', 'used_count', 'is_active', 'created_at'];
        $searchableFields = ['code', 'title', 'description'];
        $columnAliases = ['id' => 'voucher_id', 'status' => 'is_active'];

        $query = Voucher::with('campaign');

        // Backward compatibility for filters
        if ($request->filled('campaign_id') && !$request->has('filters.campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }
        if ($request->filled('voucher_type') && !$request->has('filters.voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }
        if ($request->filled('discount_type') && !$request->has('filters.discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }
        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '' && !$request->has('filters.is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Status filter (active, expired, depleted, inactive)
        if ($request->filled('status') && !$request->has('filters.status')) {
            $status = strtolower($request->status);
            $now = Carbon::now();
            if ($status === 'active') {
                $query->where('is_active', true)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
                    })
                    ->where(function ($q) {
                        $q->whereNull('system_limit')->orWhereRaw('used_count < system_limit');
                    });
            } elseif ($status === 'expired') {
                $query->whereNotNull('valid_until')->where('valid_until', '<', $now);
            } elseif ($status === 'depleted') {
                $query->whereNotNull('system_limit')->whereRaw('used_count >= system_limit');
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $query->applyDataTableQuery($request, $allowedFilters, $allowedSorts, $searchableFields, $columnAliases);

        $perPage = (int) $request->get('per_page', $request->get('limit', 15));
        $page = (int) $request->get('page', 1);
        $vouchers = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data'    => AdminVoucherResource::collection($vouchers->items()),
            'meta'    => [
                'current_page' => $vouchers->currentPage(),
                'last_page'    => $vouchers->lastPage(),
                'per_page'     => $vouchers->perPage(),
                'total'        => $vouchers->total(),
                'totalPages'   => $vouchers->lastPage(),
                'totalResults' => $vouchers->total(),
            ],
            'pagination' => [
                'page'       => $vouchers->currentPage(),
                'perPage'    => $vouchers->perPage(),
                'total'      => $vouchers->total(),
                'totalPages' => $vouchers->lastPage(),
            ]
        ]);
    }

    /**
     * Summary KPI stats for Admin Voucher Portal
     */
    public function stats()
    {
        $nowStr = Carbon::now()->toDateTimeString();
        $soonStr = Carbon::now()->addDays(7)->toDateTimeString();

        $stats = Voucher::selectRaw("
            COUNT(*) as total_vouchers,
            SUM(CASE WHEN is_active = true 
                AND (valid_from IS NULL OR valid_from <= '{$nowStr}') 
                AND (valid_until IS NULL OR valid_until >= '{$nowStr}') 
                AND (system_limit IS NULL OR used_count < system_limit) THEN 1 ELSE 0 END) as active_vouchers,
            COALESCE(SUM(used_count), 0) as total_used_count,
            SUM(CASE WHEN is_active = true 
                AND valid_until IS NOT NULL 
                AND valid_until >= '{$nowStr}' 
                AND valid_until <= '{$soonStr}' THEN 1 ELSE 0 END) as expiring_soon_count
        ")->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_vouchers'      => (int) ($stats->total_vouchers ?? 0),
                'active_vouchers'     => (int) ($stats->active_vouchers ?? 0),
                'total_used_count'    => (int) ($stats->total_used_count ?? 0),
                'expiring_soon_count' => (int) ($stats->expiring_soon_count ?? 0),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVoucherRequest $request)
    {
        $voucher = Voucher::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo mã giảm giá thành công.',
            'data'    => new AdminVoucherResource($voucher->load('campaign'))
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $voucher = Voucher::with(['campaign', 'bookings'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new AdminVoucherResource($voucher)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVoucherRequest $request, string $id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật mã giảm giá thành công.',
            'data'    => new AdminVoucherResource($voucher->fresh()->load('campaign'))
        ]);
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(string $id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->is_active = !$voucher->is_active;
        $voucher->save();

        return response()->json([
            'success' => true,
            'message' => $voucher->is_active ? 'Đã kích hoạt mã giảm giá.' : 'Đã tạm ngưng mã giảm giá.',
            'data'    => new AdminVoucherResource($voucher->fresh()->load('campaign'))
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa mã giảm giá thành công.'
        ]);
    }

    /**
     * Inline update a single field of a voucher
     */
    public function updateCell(Request $request, string $id)
    {
        $voucher = Voucher::findOrFail($id);
        $field = $request->input('field');
        $value = $request->input('value');

        $allowedFields = ['code', 'title', 'voucher_type', 'discount_type', 'discount_value', 'min_order_value', 'max_discount_value', 'valid_from', 'valid_until', 'system_limit', 'limit_per_user', 'is_active'];
        if (!in_array($field, $allowedFields, true)) {
            return response()->json([
                'success' => false,
                'message' => "Không cho phép cập nhật trường: {$field}"
            ], 422);
        }

        if ($field === 'is_active') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        $voucher->update([$field => $value]);

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$field} thành công.",
            'data'    => new AdminVoucherResource($voucher->fresh()->load('campaign'))
        ]);
    }

    /**
     * Bulk actions for vouchers
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Danh sách ID không được để trống.'
            ], 422);
        }

        $count = count($ids);

        switch ($action) {
            case 'delete':
                Voucher::whereIn('voucher_id', $ids)->delete();
                $msg = "Đã xóa {$count} mã giảm giá thành công.";
                break;

            case 'set_active':
                Voucher::whereIn('voucher_id', $ids)->update(['is_active' => true]);
                $msg = "Đã kích hoạt {$count} mã giảm giá.";
                break;

            case 'set_inactive':
                Voucher::whereIn('voucher_id', $ids)->update(['is_active' => false]);
                $msg = "Đã tạm dừng {$count} mã giảm giá.";
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => "Hành động không hợp lệ: {$action}"
                ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }
}
