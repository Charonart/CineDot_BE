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
        $limit = (int) $request->get('limit', 15);
        $query = Banner::with('campaign');

        if ($request->filled('search')) {
            $query->where('title', 'ilike', '%' . $request->search . '%');
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
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
            'message' => 'Tạo banner quảng cáo thành công.',
            'data'    => new BannerResource($banner->load('campaign')),
        ], 201);
    }

    /**
     * Display the specified banner.
     */
    public function show(string $id)
    {
        $banner = Banner::with('campaign')->findOrFail($id);

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
            'message' => 'Cập nhật banner quảng cáo thành công.',
            'data'    => new BannerResource($banner->fresh()->load('campaign')),
        ]);
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(string $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->is_active = !$banner->is_active;
        $banner->save();

        return response()->json([
            'success' => true,
            'message' => $banner->is_active ? 'Đã kích hoạt hiển thị banner.' : 'Đã ẩn banner.',
            'data'    => new BannerResource($banner->fresh()->load('campaign')),
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
            'message' => 'Xóa banner quảng cáo thành công.',
        ]);
    }
}
