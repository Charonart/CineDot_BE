<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Http\Requests\Admin\StoreComboRequest;
use App\Http\Requests\Admin\UpdateComboRequest;
use App\Http\Resources\AdminComboResource;
use Illuminate\Http\Request;

class ComboController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Combo::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'ilike', '%' . $search . '%');
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $combos = $query->orderBy('created_at', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $combos->currentPage(),
                'results'      => AdminComboResource::collection($combos->items()),
                'totalPages'   => $combos->lastPage(),
                'totalResults' => $combos->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreComboRequest $request)
    {
        $combo = Combo::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo combo thành công.',
            'data'    => new AdminComboResource($combo)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $combo = Combo::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new AdminComboResource($combo)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComboRequest $request, string $id)
    {
        $combo = Combo::findOrFail($id);
        $combo->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật combo thành công.',
            'data'    => new AdminComboResource($combo)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $combo = Combo::findOrFail($id);
        $combo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa combo thành công.'
        ]);
    }
}
