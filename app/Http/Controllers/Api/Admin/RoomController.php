<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoomRequest;
use App\Http\Requests\Admin\UpdateRoomRequest;
use App\Models\Room;
use App\Models\Cinema;
use App\Services\SeatLayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function __construct(
        private SeatLayoutService $seatLayoutService
    ) {}

    /**
     * Get rooms of a specific cinema
     */
    public function index($cinemaId)
    {
        $cinema = Cinema::findOrFail($cinemaId);
        
        $rooms = Room::where('cinema_id', $cinemaId)
                     ->withCount('seats')
                     ->orderBy('room_name')
                     ->get();

        return response()->json([
            'success' => true,
            'data'    => $rooms
        ]);
    }

    /**
     * Store a newly created room in storage.
     * Tự động sinh sơ đồ ghế theo room_type.
     */
    public function store(StoreRoomRequest $request, $cinemaId)
    {
        $cinema = Cinema::findOrFail($cinemaId);

        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['cinema_id'] = $cinemaId;

            $room = Room::create($data);

            // Tự động sinh 100 ghế theo bố cục
            $this->seatLayoutService->generateLayout($room, $data['room_type']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo phòng chiếu thành công. Đã tự động sinh ' . $room->fresh()->total_seats . ' ghế.',
                'data'    => $room->load('seats')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo phòng chiếu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $room = Room::with(['cinema', 'seats'])->findOrFail($id);

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

        $room->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật phòng chiếu thành công.',
            'data'    => $room
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa phòng chiếu thành công.'
        ]);
    }
}

