<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use Illuminate\Http\Request;

class CinemaController extends Controller
{
    /**
     * GET /api/cinemas
     * Danh sách tất cả rạp, lọc theo city nếu có.
     * Query params:
     *   ?city=Hà Nội   → lọc theo thành phố
     *   ?chain=CGV     → lọc theo chuỗi rạp
     */
    public function index(Request $request)
    {
        $query = Cinema::query();

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('chain')) {
            $query->where('chain', $request->chain);
        }

        $cinemas = $query->orderBy('chain')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $cinemas,
        ]);
    }

    /**
     * GET /api/cinemas/{id}
     * Chi tiết 1 rạp.
     */
    public function show($id)
    {
        $cinema = Cinema::find($id);

        if (!$cinema) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy rạp',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $cinema,
        ]);
    }
}
