<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeatType;
use App\Models\ShowtimeSeat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeatTypeController extends Controller
{
    /**
     * Display a listing of all seat types with usage stats.
     */
    public function index()
    {
        $seatTypes = SeatType::orderBy('sort_order', 'asc')
            ->orderBy('surcharge_amount', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $seatTypes,
            'meta'    => [
                'current_page' => 1,
                'last_page'    => 1,
                'per_page'     => $seatTypes->count(),
                'total'        => $seatTypes->count(),
            ]
        ]);
    }

    /**
     * Store a newly created seat type in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'seat_type'        => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', 'unique:seat_types,seat_type'],
            'type_name'        => ['required', 'string', 'max:100'],
            'surcharge_amount' => ['required', 'numeric', 'min:0'],
            'color_code'       => ['nullable', 'string', 'max:20'],
            'icon_name'        => ['nullable', 'string', 'max:30'],
            'description'      => ['nullable', 'string'],
            'is_active'        => ['nullable', 'boolean'],
            'sort_order'       => ['nullable', 'integer'],
        ]);

        $validated['seat_type'] = strtolower(trim($validated['seat_type']));
        $validated['color_code'] = $validated['color_code'] ?? '#64748B';
        $validated['icon_name'] = $validated['icon_name'] ?? 'seat';
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 10;

        $seatType = SeatType::create($validated);
        \Illuminate\Support\Facades\Cache::forget('seat_types:keys');
        \Illuminate\Support\Facades\Cache::forget('seat_types:active_list');

        return response()->json([
            'success' => true,
            'message' => "Tạo loại ghế '{$seatType->type_name}' thành công.",
            'data'    => $seatType,
        ], 201);
    }

    /**
     * Display the specified seat type.
     */
    public function show(string $id)
    {
        $seatType = SeatType::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $seatType,
        ]);
    }

    /**
     * Update the specified seat type in storage.
     */
    public function update(Request $request, string $id)
    {
        $seatType = SeatType::findOrFail($id);

        $validated = $request->validate([
            'type_name'        => ['sometimes', 'required', 'string', 'max:100'],
            'surcharge_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'color_code'       => ['nullable', 'string', 'max:20'],
            'icon_name'        => ['nullable', 'string', 'max:30'],
            'description'      => ['nullable', 'string'],
            'is_active'        => ['nullable', 'boolean'],
            'sort_order'       => ['nullable', 'integer'],
        ]);

        $seatType->update($validated);
        \Illuminate\Support\Facades\Cache::forget('seat_types:keys');
        \Illuminate\Support\Facades\Cache::forget('seat_types:active_list');

        return response()->json([
            'success' => true,
            'message' => "Cập nhật loại ghế '{$seatType->type_name}' thành công.",
            'data'    => $seatType,
        ]);
    }

    /**
     * Remove the specified seat type from storage.
     */
    public function destroy(string $id)
    {
        $seatType = SeatType::findOrFail($id);

        // Disallow deletion of standard seat
        if ($seatType->seat_type === 'standard') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa loại Ghế Tiêu Chuẩn (Standard) mặc định của hệ thống.',
            ], 422);
        }

        // Check if any showtime seats are currently using this seat type
        $usageCount = ShowtimeSeat::where('seat_type', $seatType->seat_type)->count();
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xóa loại ghế '{$seatType->type_name}' vì đang được sử dụng trong {$usageCount} suất chiếu. Hãy chuyển trạng thái sang ngưng kích hoạt.",
            ], 422);
        }

        $seatType->delete();
        \Illuminate\Support\Facades\Cache::forget('seat_types:keys');
        \Illuminate\Support\Facades\Cache::forget('seat_types:active_list');

        return response()->json([
            'success' => true,
            'message' => "Đã xóa loại ghế '{$seatType->type_name}' thành công.",
        ]);
    }
}
