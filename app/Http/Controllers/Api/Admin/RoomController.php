<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoomRequest;
use App\Http\Requests\Admin\UpdateRoomRequest;
use App\Models\Room;
use App\Models\Cinema;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Get rooms of a specific cinema
     */
    public function index($cinemaId)
    {
        $cinema = Cinema::findOrFail($cinemaId);
        
        $rooms = Room::where('cinema_id', $cinemaId)
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
        
        // Additional check to ensure cinema exists
        $cinema = Cinema::findOrFail($cinemaId);

        $room = Room::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tạo phòng chiếu thành công.',
            'data'    => $room
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $room = Room::with('cinema')->findOrFail($id);

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
        $room->delete(); // Soft delete because of the trait

        return response()->json([
            'success' => true,
            'message' => 'Xóa phòng chiếu thành công.'
        ]);
    }
}
