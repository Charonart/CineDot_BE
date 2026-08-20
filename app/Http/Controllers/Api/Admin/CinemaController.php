<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCinemaRequest;
use App\Http\Requests\Admin\UpdateCinemaRequest;
use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CinemaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the resource (Standardized with FilterableAndSortable).
     */
    public function index(Request $request)
    {
        $allowedFilters = ['cinema_name', 'province_id', 'phone', 'email', 'is_active', 'province'];
        $allowedSorts = ['cinema_id', 'id', 'cinema_name', 'province_id', 'is_active', 'created_at'];
        $searchableFields = ['cinema_name', 'cinema_address', 'phone', 'email'];
        $columnAliases = ['id' => 'cinema_id', 'name' => 'cinema_name', 'address' => 'cinema_address', 'status' => 'is_active'];

        $query = Cinema::with(['province', 'rooms']);

        // Handle simple province_id filter if passed directly
        if ($request->has('province_id') && !empty($request->province_id) && !$request->has('filters.province_id')) {
            $query->where('province_id', $request->province_id);
        }

        $query->applyDataTableQuery($request, $allowedFilters, $allowedSorts, $searchableFields, $columnAliases);

        $perPage = (int) $request->get('per_page', $request->get('limit', 15));
        $cinemas = $query->paginate($perPage);

        $items = collect($cinemas->items())->map(function ($c) {
            $arr = $c->toArray();
            $arr['id'] = $c->cinema_id;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $cinemas->currentPage(),
                'last_page'    => $cinemas->lastPage(),
                'per_page'     => $cinemas->perPage(),
                'total'        => $cinemas->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCinemaRequest $request)
    {
        $data = $request->validated();
        
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['cinema_name']) . '-' . time();
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $cinema = Cinema::create($data);
        $cinema->load(['province', 'rooms']);

        return response()->json([
            'success' => true,
            'message' => 'Tạo rạp chiếu thành công.',
            'data'    => $cinema
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cinema = Cinema::with(['province', 'rooms'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $cinema
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCinemaRequest $request, string $id)
    {
        $cinema = Cinema::findOrFail($id);
        $data = $request->validated();

        if (!empty($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        } elseif (isset($data['cinema_name']) && $data['cinema_name'] !== $cinema->cinema_name && empty($cinema->slug)) {
            $data['slug'] = Str::slug($data['cinema_name']) . '-' . time();
        }

        $cinema->update($data);
        $cinema->load(['province', 'rooms']);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật rạp chiếu thành công.',
            'data'    => $cinema
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cinema = Cinema::findOrFail($id);
        $cinema->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa rạp chiếu thành công.'
        ]);
    }

    /**
     * Inline update a single field of a cinema
     */
    public function updateCell(Request $request, string $id)
    {
        $cinema = Cinema::findOrFail($id);
        $field = $request->input('field');
        $value = $request->input('value');

        $allowedFields = ['cinema_name', 'cinema_address', 'province_id', 'phone', 'email', 'description', 'is_active'];
        if (!in_array($field, $allowedFields, true)) {
            return response()->json([
                'success' => false,
                'message' => "Không cho phép cập nhật trường: {$field}"
            ], 422);
        }

        if ($field === 'is_active') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        $cinema->update([$field => $value]);
        $arr = $cinema->fresh(['province', 'rooms'])->toArray();
        $arr['id'] = $cinema->cinema_id;

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$field} thành công.",
            'data'    => $arr
        ]);
    }

    /**
     * Toggle active status of a cinema
     */
    public function toggleStatus(string $id)
    {
        $cinema = Cinema::findOrFail($id);
        $cinema->update(['is_active' => !$cinema->is_active]);

        $arr = $cinema->fresh(['province', 'rooms'])->toArray();
        $arr['id'] = $cinema->cinema_id;

        return response()->json([
            'success' => true,
            'message' => 'Đã thay đổi trạng thái hoạt động của rạp.',
            'data'    => $arr
        ]);
    }

    /**
     * Bulk actions for cinemas
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
                Cinema::whereIn('cinema_id', $ids)->delete();
                $msg = "Đã xóa {$count} cụm rạp thành công.";
                break;

            case 'set_active':
                Cinema::whereIn('cinema_id', $ids)->update(['is_active' => true]);
                $msg = "Đã mở hoạt động {$count} cụm rạp.";
                break;

            case 'set_inactive':
                Cinema::whereIn('cinema_id', $ids)->update(['is_active' => false]);
                $msg = "Đã tạm dừng hoạt động {$count} cụm rạp.";
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
