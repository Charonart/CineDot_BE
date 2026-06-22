<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeatRequest;
use App\Http\Requests\Admin\UpdateSeatRequest;
use App\Models\Seat;
use App\Models\Room;
use Illuminate\Http\Request;

class SeatController extends Controller
{
    /**
     * Lấy toàn bộ ghế của 1 phòng
     */
    public function index($roomId)
    {
        $room = Room::findOrFail($roomId);

        $seats = Seat::where('room_id', $roomId)
                     ->orderBy('seat_row')
                     ->orderBy('seat_number')
                     ->get();

        return response()->json([
            'success' => true,
            'data'    => $seats
        ]);
    }

    /**
     * Tạo mới 1 ghế trong phòng
     */
    public function store(StoreSeatRequest $request, $roomId)
    {
        $room = Room::findOrFail($roomId);
        $data = $request->validated();
        $data['room_id'] = $roomId;

        // Bắt lỗi trùng ghế (Unique constraint)
        $exists = Seat::where('room_id', $roomId)
                      ->where('seat_row', $data['seat_row'])
                      ->where('seat_number', $data['seat_number'])
                      ->first();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ghế này đã tồn tại trong phòng chiếu.'
            ], 422);
        }

        $seat = Seat::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tạo ghế thành công.',
            'data'    => $seat
        ], 201);
    }

    /**
     * Cập nhật thông tin 1 ghế
     */
    public function update(UpdateSeatRequest $request, string $id)
    {
        $seat = Seat::findOrFail($id);
        $data = $request->validated();

        if (isset($data['seat_row']) && isset($data['seat_number'])) {
            $exists = Seat::where('room_id', $seat->room_id)
                          ->where('seat_row', $data['seat_row'])
                          ->where('seat_number', $data['seat_number'])
                          ->where('seat_id', '!=', $seat->seat_id)
                          ->first();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tọa độ ghế mới bị trùng với ghế khác trong phòng.'
                ], 422);
            }
        }

        $seat->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ghế thành công.',
            'data'    => $seat
        ]);
    }

    /**
     * Xóa 1 ghế khỏi phòng (Soft Delete)
     */
    public function destroy(string $id)
    {
        $seat = Seat::findOrFail($id);
        $seat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa ghế thành công.'
        ]);
    }
}
