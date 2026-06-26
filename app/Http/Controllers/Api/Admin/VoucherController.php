<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Http\Requests\Admin\StoreVoucherRequest;
use App\Http\Requests\Admin\UpdateVoucherRequest;
use App\Http\Resources\AdminVoucherResource;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Voucher::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('code', 'ilike', '%' . $search . '%');
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        if ($request->has('discount_type')) {
            $query->where('discount_type', $request->discount_type);
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
     * Store a newly created resource in storage.
     */
    public function store(StoreVoucherRequest $request)
    {
        $voucher = Voucher::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo mã giảm giá thành công.',
            'data'    => new AdminVoucherResource($voucher)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $voucher = Voucher::findOrFail($id);

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
            'data'    => new AdminVoucherResource($voucher)
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
