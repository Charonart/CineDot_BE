<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Http\Resources\BannerResource;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Display a listing of banners.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Banner::query();

        if ($request->has('search')) {
            $query->where('title', 'ilike', '%' . $request->search . '%');
        }

        $banners = $query->orderBy('order', 'asc')
            ->orderBy('banner_id', 'desc')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $banners->currentPage(),
                'results'      => BannerResource::collection($banners->items()),
                'totalPages'   => $banners->lastPage(),
                'totalResults' => $banners->total(),
            ]
        ]);
    }

    /**
     * Store a newly created banner.
     */
    public function store(StoreBannerRequest $request)
    {
        $banner = Banner::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tạo banner thành công.',
            'data'    => new BannerResource($banner),
        ], 201);
    }

    /**
     * Display the specified banner.
     */
    public function show(string $id)
    {
        $banner = Banner::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new BannerResource($banner),
        ]);
    }

    /**
     * Update the specified banner.
     */
    public function update(UpdateBannerRequest $request, string $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật banner thành công.',
            'data'    => new BannerResource($banner),
        ]);
    }

    /**
     * Remove the specified banner.
     */
    public function destroy(string $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa banner thành công.',
        ]);
    }
}
