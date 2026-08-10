<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Http\Resources\BaseSeatResource;

class RoomController extends Controller
{
    public function seats($id)
    {
        $room = Room::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data'    => BaseSeatResource::collection($room->seats)
        ]);
    }

    /**
     * Trả về tọa độ sơ đồ ghế dạng JSON phẳng, tối ưu cho cache.
     *
     * Gọi 1 lần, cache lại ở LocalStorage hoặc CDN.
     * Hiếm khi thay đổi (chỉ khi rạp bị dỡ và xây lại).
     *
     * Response format:
     * [
     *   { "id": "A1", "type": "STD", "cx": 10, "cy": 20, "angle": 0 },
     *   ...
     * ]
     */
    public function layout($id)
    {
        $room = Room::findOrFail($id);

        // Mapping từ DB value sang viết tắt cho front-end
        $typeMap = [
            'standard' => 'STD',
            'vip'      => 'VIP',
            'couple'   => 'CPL',
        ];

        $seats = $room->seats()
            ->where('is_active', true)
            ->orderBy('seat_row')
            ->orderBy('seat_number')
            ->get();

        $layout = $seats->map(function ($seat) use ($typeMap) {
            return [
                'id'    => $seat->seat_row . $seat->seat_number,
                'type'  => $typeMap[$seat->seat_type] ?? 'STD',
                'cx'    => $seat->position_x,
                'cy'    => $seat->position_y,
                'angle' => (float) $seat->angle,
            ];
        });

        // Trả JSON phẳng (flat array), không bọc trong data/success
        // Cache-Control: 1 năm (31536000 giây)
        return response()
            ->json($layout)
            ->header('Cache-Control', 'public, max-age=31536000');
    }
}

