<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CinemaController extends Controller
{
    /**
     * GET /api/cinemas
     * Danh sách tất cả rạp, lọc theo province nếu có.
     * Query params:
     *   ?province=Hà Nội   → lọc theo tỉnh/thành
     */
    public function index(Request $request)
    {
        $province = $request->get('province', 'all');
        $cacheKey = "cinemas.list.province.{$province}";

        // Cache lâu vì danh sách rạp ít thay đổi
        $cinemas = Cache::remember($cacheKey, now()->addHours(1), function () use ($province) {
            $query = Cinema::with('province');

            if ($province !== 'all') {
                $query->whereHas('province', fn($q) => $q->where('province_name', $province));
            }

            return $query->orderBy('cinema_name')->get()->map(fn($c) => [
                'id'       => $c->cinema_id,
                'name'     => $c->cinema_name,
                'address'  => $c->cinema_address,
                'province' => $c->province?->province_name,
                'phone'    => $c->phone,
                'email'    => $c->email,
                'isActive' => $c->is_active,
            ]);
        });

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
        $data = Cache::remember("cinema.{$id}", now()->addHours(1), function () use ($id) {
            $cinema = Cinema::with(['province', 'rooms'])->find($id);

            if (!$cinema) {
                return null;
            }

            return [
                'id'          => $cinema->cinema_id,
                'name'        => $cinema->cinema_name,
                'address'     => $cinema->cinema_address,
                'province'    => $cinema->province?->province_name,
                'phone'       => $cinema->phone,
                'email'       => $cinema->email,
                'description' => $cinema->description,
                'isActive'    => $cinema->is_active,
                'rooms'       => $cinema->rooms->map(fn($r) => [
                    'id'         => $r->room_id,
                    'name'       => $r->room_name,
                    'type'       => $r->room_type,
                    'totalSeats' => $r->total_seats,
                    'isActive'   => $r->is_active,
                ]),
            ];
        });

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy rạp',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
