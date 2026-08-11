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
            'data'    => BaseSeatResource::collection($room->seats ?? [])
        ]);
    }

    /**
     * Get static seat layout for a room.
     * Response matches Postman collection spec: room_id, room_name, total_seats, seats array with cx, cy.
     */
    public function layout($id)
    {
        $room = Room::findOrFail($id);

        return response()->json($room->formatted_layout);
    }
}

