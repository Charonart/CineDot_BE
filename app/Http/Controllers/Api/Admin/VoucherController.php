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
    public function index(Request $request)
    {
        $limit = (int) $request->get('limit', 15);
        $query = Voucher::with('campaign');

        // 1. Search code or title
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'ilike', '%' . $search . '%')
                  ->orWhere('title', 'ilike', '%' . $search . '%');
            });
        }

        // 2. Campaign filter
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // 3. Voucher Type filter (ticket, combo, order, all)
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        // 4. Discount Type filter (percentage, fixed_amount)
        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        // 5. Active boolean filter
        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // 6. Status filter (active, expired, depleted, inactive)
        if ($request->filled('status')) {
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

        $vouchers = $query->orderBy('created_at', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $vouchers->currentPage(),
                'results'      => AdminVoucherResource::collection($vouchers->items()),
                'totalPages'   => $vouchers->lastPage(),
                'totalResults' => $vouchers->total(),
            ]
        ]);
    }

    /**
     * Summary KPI stats for Admin Voucher Portal
     */
    public function stats()
    {
        $now = Carbon::now();
        $soon = Carbon::now()->addDays(7);

        $totalVouchers = Voucher::count();
        $activeVouchers = Voucher::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('system_limit')->orWhereRaw('used_count < system_limit');
            })
            ->count();

        $totalUsedCount = (int) Voucher::sum('used_count');
        $expiringSoonCount = Voucher::where('is_active', true)
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [$now, $soon])
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_vouchers'      => $totalVouchers,
                'active_vouchers'     => $activeVouchers,
                'total_used_count'    => $totalUsedCount,
                'expiring_soon_count' => $expiringSoonCount,
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
}
