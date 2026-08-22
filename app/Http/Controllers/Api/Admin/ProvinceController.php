<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Http\Requests\Admin\StoreProvinceRequest;
use App\Http\Requests\Admin\UpdateProvinceRequest;
use App\Http\Resources\AdminProvinceResource;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Province::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('province_name', 'like', '%' . $search . '%');
        }

        $page = (int) $request->get('page', 1);
        $provinces = $query->orderBy('province_id', 'asc')->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $provinces->currentPage(),
                'results'      => AdminProvinceResource::collection($provinces->items()),
                'totalPages'   => $provinces->lastPage(),
                'totalResults' => $provinces->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProvinceRequest $request)
    {
        $province = Province::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo tỉnh thành thành công.',
            'data'    => new AdminProvinceResource($province)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $province = Province::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new AdminProvinceResource($province)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProvinceRequest $request, string $id)
    {
        $province = Province::findOrFail($id);
        $province->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tỉnh thành thành công.',
            'data'    => new AdminProvinceResource($province)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $province = Province::findOrFail($id);
        $province->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa tỉnh thành thành công.'
        ]);
    }
}
