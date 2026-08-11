<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\Room;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Danh sách lịch chiếu (Admin)
     */
    public function index(Request $request)
    {
        $query = Showtime::with(['movie', 'room.cinema']);

        if ($request->has('cinema_id')) {
            $cinemaId = $request->cinema_id;
            $query->whereHas('room', function ($q) use ($cinemaId) {
                $q->where('cinema_id', $cinemaId);
            });
        }

        if ($request->has('movie_id')) {
            $query->where('movie_id', $request->movie_id);
        }

        if ($request->has('date')) {
            $query->whereDate('start_time', $request->date);
        }

        $showtimes = $query->orderBy('start_time', 'desc')->paginate($request->get('limit', 20));

        return response()->json([
            'success' => true,
            'data'    => $showtimes
        ]);
    }

    /**
     * Sắp xếp lịch chiếu mới (POST /admin/showtimes)
     * Tự động sinh danh sách showtime_seats dựa theo sơ đồ ghế tĩnh (seat_matrix) của phòng.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'movie_id'   => 'required|exists:movies,movie_id',
            'room_id'    => 'required|exists:rooms,room_id',
            'start_time' => 'required|date',
            'end_time'   => 'nullable|date|after:start_time',
            'price'      => 'nullable|numeric|min:0',
            'base_price' => 'nullable|numeric|min:0',
        ]);

        $basePrice = $validated['base_price'] ?? $validated['price'] ?? 100000;
        $movie = Movie::findOrFail($validated['movie_id']);
        $room = Room::findOrFail($validated['room_id']);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = isset($validated['end_time']) 
            ? Carbon::parse($validated['end_time']) 
            : (clone $startTime)->addMinutes($movie->duration ?? 120);

        return DB::transaction(function () use ($validated, $movie, $room, $startTime, $endTime, $basePrice) {
            // 1. Tạo Suất chiếu
            $showtime = Showtime::create([
                'movie_id'   => $movie->movie_id,
                'room_id'    => $room->room_id,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'base_price' => $basePrice,
            ]);

            // 2. Tạo toàn bộ Ghế cho suất chiếu từ seat_matrix của Phòng
            $matrix = $room->seat_matrix ?? [];
            $seatsToInsert = [];

            if (is_array($matrix)) {
                foreach ($matrix as $seat) {
                    if (is_array($seat)) {
                        $rowName = $seat['row_name'] ?? (isset($seat['seat_id']) ? substr($seat['seat_id'], 0, 1) : 'A');
                        $seatNumber = $seat['seat_number'] ?? (isset($seat['seat_id']) ? substr($seat['seat_id'], 1) : '1');
                        $seatType = $seat['seat_type'] ?? $seat['type'] ?? 'STANDARD';

                        $seatsToInsert[] = [
                            'showtime_id' => $showtime->showtime_id,
                            'row_name'    => (string) $rowName,
                            'seat_number' => (string) $seatNumber,
                            'seat_type'   => strtoupper((string) $seatType),
                            'status'      => 'available',
                        ];
                    }
                }
            }

            if (!empty($seatsToInsert)) {
                ShowtimeSeat::insert($seatsToInsert);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tạo lịch chiếu và nạp danh sách ghế thành công.',
                'data'    => $showtime->load(['movie', 'room.cinema'])
            ], 201);
        });
    }

    /**
     * Chi tiết lịch chiếu
     */
    public function show(string $id)
    {
        $showtime = Showtime::with(['movie', 'room.cinema', 'showtimeSeats'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $showtime
        ]);
    }

    /**
     * Cập nhật lịch chiếu
     */
    public function update(Request $request, string $id)
    {
        $showtime = Showtime::findOrFail($id);
        
        $validated = $request->validate([
            'movie_id'   => 'sometimes|exists:movies,movie_id',
            'room_id'    => 'sometimes|exists:rooms,room_id',
            'start_time' => 'sometimes|date',
            'end_time'   => 'sometimes|date|after:start_time',
            'base_price' => 'sometimes|numeric|min:0',
            'price'      => 'sometimes|numeric|min:0',
        ]);

        if (isset($validated['price'])) {
            $validated['base_price'] = $validated['price'];
            unset($validated['price']);
        }

        $showtime->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật lịch chiếu thành công.',
            'data'    => $showtime->load(['movie', 'room.cinema'])
        ]);
    }

    /**
     * Xóa lịch chiếu
     */
    public function destroy(string $id)
    {
        $showtime = Showtime::findOrFail($id);
        $showtime->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa lịch chiếu thành công.'
        ]);
    }
}
