<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomFormatCatalog;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Get list of rooms with optional filters (screen_type, sound_technology, cinema_id).
     */
    public function index(Request $request)
    {
        $query = Room::with('cinema.province')
            ->where('is_active', true);

        if ($request->filled('cinema_id')) {
            $query->where('cinema_id', $request->query('cinema_id'));
        }

        if ($request->filled('screen_type')) {
            $query->where('screen_type', $request->query('screen_type'));
        }

        if ($request->filled('sound_technology')) {
            $query->where('sound_technology', $request->query('sound_technology'));
        }

        if ($request->filled('room_type')) {
            $query->where('room_type', 'ILIKE', '%' . $request->query('room_type') . '%');
        }

        $rooms = $query->orderBy('room_name')->get();

        return response()->json([
            'success' => true,
            'data'    => $rooms
        ]);
    }

    /**
     * Get all master room formats, screen types, sound technologies, and templates.
     */
    public function formats()
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'screen_types'       => array_values(RoomFormatCatalog::getScreenTypes()),
                'sound_technologies' => array_values(RoomFormatCatalog::getSoundTechnologies()),
                'room_templates'     => RoomFormatCatalog::getRoomTemplates(),
            ]
        ]);
    }

    public function seats($id)
    {
        $room = Room::with('seats.seatType')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data'    => $room->seats->map(function ($s) {
                return [
                    'seat_id'     => $s->seat_id,
                    'seat_code'   => $s->seat_code,
                    'row_name'    => $s->row_name,
                    'seat_number' => (string) $s->seat_number,
                    'seat_type'   => $s->seat_type,
                    'type_name'   => $s->seatType?->type_name ?? ucfirst($s->seat_type),
                    'color_code'  => $s->seatType?->color_code ?? '#64748B',
                    'icon_name'   => $s->seatType?->icon_name ?? 'seat',
                    'cx'          => (int) $s->coord_x,
                    'cy'          => (int) $s->coord_y,
                    'angle'       => (int) $s->angle,
                    'is_active'   => (bool) $s->is_active,
                ];
            })
        ]);
    }

    /**
     * Get static seat layout and canvas screen config for a room.
     * Response matches Postman collection spec: room_id, room_name, screen, total_seats, seats array with cx, cy.
     */
    public function layout($id)
    {
        $room = Room::with('seats')->findOrFail($id);

        return response()->json($room->formatted_layout);
    }
}
