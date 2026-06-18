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
}
