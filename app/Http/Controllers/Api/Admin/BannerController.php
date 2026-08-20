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
    /**
     * Display a listing of banners (Standardized with FilterableAndSortable).
     */
    public function index(Request $request)
    {
        $allowedFilters = ['title', 'campaign_id', 'is_active', 'order'];
        $allowedSorts = ['banner_id', 'id', 'title', 'order', 'is_active', 'created_at'];
        $searchableFields = ['title', 'link_url'];
        $columnAliases = ['id' => 'banner_id', 'status' => 'is_active'];

        $query = Banner::with('campaign');

        if ($request->filled('campaign_id') && !$request->has('filters.campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '' && !$request->has('filters.is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $query->applyDataTableQuery($request, $allowedFilters, $allowedSorts, $searchableFields, $columnAliases);

        $perPage = (int) $request->get('per_page', $request->get('limit', 15));
        $banners = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => BannerResource::collection($banners->items()),
            'meta'    => [
                'current_page' => $banners->currentPage(),
                'last_page'    => $banners->lastPage(),
                'per_page'     => $banners->perPage(),
                'total'        => $banners->total(),
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

    /**
     * Inline update a single field of a banner
     */
    public function updateCell(Request $request, string $id)
    {
        $banner = Banner::findOrFail($id);
        $field = $request->input('field');
        $value = $request->input('value');

        $allowedFields = ['title', 'link_url', 'image_url', 'order', 'is_active'];
        if (!in_array($field, $allowedFields, true)) {
            return response()->json([
                'success' => false,
                'message' => "Không cho phép cập nhật trường: {$field}"
            ], 422);
        }

        if ($field === 'is_active') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif ($field === 'order') {
            $value = (int) $value;
        }

        $banner->update([$field => $value]);

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$field} thành công.",
            'data'    => new BannerResource($banner->fresh()->load('campaign'))
        ]);
    }

    /**
     * Bulk actions for banners
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
                Banner::whereIn('banner_id', $ids)->delete();
                $msg = "Đã xóa {$count} banner thành công.";
                break;

            case 'set_active':
                Banner::whereIn('banner_id', $ids)->update(['is_active' => true]);
                $msg = "Đã hiển thị {$count} banner.";
                break;

            case 'set_inactive':
                Banner::whereIn('banner_id', $ids)->update(['is_active' => false]);
                $msg = "Đã ẩn {$count} banner.";
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
