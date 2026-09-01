<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoomRequest;
use App\Http\Requests\Admin\UpdateRoomRequest;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Cinema;
use App\Services\RoomFormatCatalog;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    /**
     * Get rooms of a specific cinema
     */
    public function index($cinemaId)
    {
        $cinema = Cinema::findOrFail($cinemaId);
        
        $rooms = Room::with('seats')
                     ->where('cinema_id', $cinemaId)
                     ->orderBy('room_name')
                     ->get();

        return response()->json([
            'success' => true,
            'data'    => $rooms
        ]);
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(StoreRoomRequest $request, $cinemaId)
    {
        $data = $request->validated();
        $data['cinema_id'] = $cinemaId;
        $seatsData = $request->input('seats', $request->input('seat_matrix', []));
        unset($data['seat_matrix'], $data['seats']);
        
        $cinema = Cinema::findOrFail($cinemaId);

        if (empty($data['screen_config']) && !empty($data['screen_type'])) {
            $data['screen_config'] = RoomFormatCatalog::getDefaultScreenConfig($data['screen_type']);
        }

        $room = DB::transaction(function () use ($data, $seatsData) {
            $room = Room::create($data);

            if (!empty($seatsData) && is_array($seatsData)) {
                $seatsToInsert = [];
                foreach ($seatsData as $s) {
                    if (!is_array($s)) continue;
                    $seatIdStr = $s['seat_id'] ?? $s['id'] ?? '';
                    $rowName = $s['row_name'] ?? $s['row'] ?? (strlen($seatIdStr) > 0 ? substr($seatIdStr, 0, 1) : 'A');
                    $seatNumber = $s['seat_number'] ?? $s['number'] ?? (strlen($seatIdStr) > 1 ? substr($seatIdStr, 1) : '1');
                    $rawType = (string) ($s['seat_type'] ?? $s['type'] ?? 'standard');
                    $seatType = \App\Models\SeatType::resolveTypeKey($rawType);

                    $seatsToInsert[] = [
                        'room_id'     => $room->room_id,
                        'seat_type'   => $seatType,
                        'row_name'    => (string) $rowName,
                        'seat_number' => (string) $seatNumber,
                        'coord_x'     => isset($s['cx']) ? (int) $s['cx'] : (isset($s['position_x']) ? (int) $s['position_x'] : 0),
                        'coord_y'     => isset($s['cy']) ? (int) $s['cy'] : (isset($s['position_y']) ? (int) $s['position_y'] : 0),
                        'angle'       => isset($s['angle']) ? (int) $s['angle'] : 0,
                        'is_active'   => isset($s['is_active']) ? (bool) $s['is_active'] : (isset($s['status']) && $s['status'] === 'blocked' ? false : true),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
                if (!empty($seatsToInsert)) {
                    Seat::insert($seatsToInsert);
                    $room->update(['total_seats' => count($seatsToInsert)]);
                }
            }
            return $room;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tạo phòng chiếu thành công.',
            'data'    => $room->load(['cinema', 'seats'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $room = Room::with(['cinema', 'seats.seatType'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $room
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomRequest $request, string $id)
    {
        $room = Room::findOrFail($id);
        $data = $request->validated();
        $seatsData = $request->input('seats', $request->input('seat_matrix', null));
        unset($data['seat_matrix'], $data['seats']);

        if (empty($data['screen_config']) && !empty($data['screen_type']) && $data['screen_type'] !== $room->screen_type) {
            $data['screen_config'] = RoomFormatCatalog::getDefaultScreenConfig($data['screen_type']);
        }

        $room = DB::transaction(function () use ($room, $data, $seatsData) {
            $room->update($data);

            if (is_array($seatsData)) {
                Seat::where('room_id', $room->room_id)->delete();
                $seatsToInsert = [];
                foreach ($seatsData as $s) {
                    if (!is_array($s)) continue;
                    $seatIdStr = $s['seat_id'] ?? $s['id'] ?? '';
                    $rowName = $s['row_name'] ?? $s['row'] ?? (strlen($seatIdStr) > 0 ? substr($seatIdStr, 0, 1) : 'A');
                    $seatNumber = $s['seat_number'] ?? $s['number'] ?? (strlen($seatIdStr) > 1 ? substr($seatIdStr, 1) : '1');
                    $rawType = (string) ($s['seat_type'] ?? $s['type'] ?? 'standard');
                    $seatType = \App\Models\SeatType::resolveTypeKey($rawType);

                    $seatsToInsert[] = [
                        'room_id'     => $room->room_id,
                        'seat_type'   => $seatType,
                        'row_name'    => (string) $rowName,
                        'seat_number' => (string) $seatNumber,
                        'coord_x'     => isset($s['cx']) ? (int) $s['cx'] : (isset($s['position_x']) ? (int) $s['position_x'] : 0),
                        'coord_y'     => isset($s['cy']) ? (int) $s['cy'] : (isset($s['position_y']) ? (int) $s['position_y'] : 0),
                        'angle'       => isset($s['angle']) ? (int) $s['angle'] : 0,
                        'is_active'   => isset($s['is_active']) ? (bool) $s['is_active'] : (isset($s['status']) && $s['status'] === 'blocked' ? false : true),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
                if (!empty($seatsToInsert)) {
                    Seat::insert($seatsToInsert);
                    $room->update(['total_seats' => count($seatsToInsert)]);
                }
            }
            return $room;
        });

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin phòng chiếu thành công.',
            'data'    => $room->load(['cinema', 'seats'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $room = Room::findOrFail($id);
        
        $hasShowtimes = $room->showtimes()->where('showtime_start', '>=', now())->exists();
        if ($hasShowtimes) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa phòng chiếu đang có lịch chiếu sắp tới.'
            ], 422);
        }

        DB::transaction(function () use ($room) {
            Seat::where('room_id', $room->room_id)->delete();
            $room->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Xóa phòng chiếu thành công.'
        ]);
    }
}
