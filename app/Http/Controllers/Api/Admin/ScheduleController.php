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
     * Danh sách lịch chiếu (Admin) - Hỗ trợ Context Scoping
     */
    public function index(Request $request)
    {
        $query = Showtime::with(['movie', 'room.cinema']);

        // Data Scoping theo rạp/khu vực được phân quyền
        $user = $request->user();
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:showtime', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $query->whereHas('room', function ($q) use ($allowedCinemaIds) {
                    $q->whereIn('cinema_id', $allowedCinemaIds);
                });
            }
        }

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
            $query->whereDate('showtime_start', $request->date);
        }

        $showtimes = $query->orderBy('showtime_start', 'desc')->paginate($request->get('limit', 20));

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
            'movie_id'       => 'required|exists:movies,movie_id',
            'room_id'        => 'required|exists:rooms,room_id',
            'showtime_start' => 'required_without:start_time|nullable|date',
            'start_time'     => 'required_without:showtime_start|nullable|date',
            'showtime_end'   => 'nullable|date',
            'end_time'       => 'nullable|date',
            'price'          => 'nullable|numeric|min:0',
            'base_price'     => 'nullable|numeric|min:0',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        // Check context permission for target cinema
        $user = $request->user();
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('create:showtime', 'cinema');
            if (!in_array('*', $allowedCinemaIds) && !in_array($room->cinema_id, $allowedCinemaIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xếp lịch chiếu cho rạp này.'
                ], 403);
            }
        }

        $startRaw = $validated['showtime_start'] ?? $validated['start_time'];
        $endRaw = $validated['showtime_end'] ?? $validated['end_time'] ?? null;

        $basePrice = $validated['base_price'] ?? $validated['price'] ?? 100000;
        $movie = Movie::findOrFail($validated['movie_id']);

        $startTime = Carbon::parse($startRaw);
        $endTime = $endRaw 
            ? Carbon::parse($endRaw) 
            : (clone $startTime)->addMinutes($movie->duration ?? 120);

        return DB::transaction(function () use ($validated, $movie, $room, $startTime, $endTime, $basePrice) {
            // 1. Tạo Suất chiếu
            $showtime = Showtime::create([
                'movie_id'       => $movie->movie_id,
                'room_id'        => $room->room_id,
                'showtime_start' => $startTime,
                'showtime_end'   => $endTime,
                'base_price'     => $basePrice,
            ]);

            // 2. Tạo toàn bộ Ghế cho suất chiếu từ seat_matrix của Phòng
            $matrix = $room->seat_matrix ?? [];
            $seatsToInsert = [];

            if (is_array($matrix)) {
                foreach ($matrix as $seat) {
                    if (is_array($seat)) {
                        $seatIdStr = $seat['id'] ?? $seat['seat_id'] ?? '';
                        $rowName = $seat['row_name'] ?? (strlen($seatIdStr) > 0 ? substr($seatIdStr, 0, 1) : 'A');
                        $seatNumber = $seat['seat_number'] ?? (strlen($seatIdStr) > 1 ? substr($seatIdStr, 1) : '1');

                        $rawType = strtolower(trim((string) ($seat['seat_type'] ?? $seat['type'] ?? 'standard')));
                        $seatType = match ($rawType) {
                            'std', 'standard' => 'standard',
                            'vip' => 'vip',
                            'couple', 'double' => 'couple',
                            default => 'standard',
                        };

                        $seatsToInsert[] = [
                            'showtime_id' => $showtime->showtime_id,
                            'row_name'    => (string) $rowName,
                            'seat_number' => (string) $seatNumber,
                            'seat_type'   => $seatType,
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
        $showtime = Showtime::with('room')->findOrFail($id);

        $user = $request->user();
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('edit:showtime', 'cinema');
            if (!in_array('*', $allowedCinemaIds) && !in_array($showtime->room?->cinema_id, $allowedCinemaIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền chỉnh sửa lịch chiếu của rạp này.'
                ], 403);
            }
        }
        
        $validated = $request->validate([
            'movie_id'       => 'sometimes|exists:movies,movie_id',
            'room_id'        => 'sometimes|exists:rooms,room_id',
            'showtime_start' => 'sometimes|date',
            'start_time'     => 'sometimes|date',
            'showtime_end'   => 'sometimes|date',
            'end_time'       => 'sometimes|date',
            'base_price'     => 'sometimes|numeric|min:0',
            'price'          => 'sometimes|numeric|min:0',
        ]);

        if (isset($validated['start_time'])) {
            $validated['showtime_start'] = $validated['start_time'];
            unset($validated['start_time']);
        }
        if (isset($validated['end_time'])) {
            $validated['showtime_end'] = $validated['end_time'];
            unset($validated['end_time']);
        }
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
    public function destroy(Request $request, string $id)
    {
        $showtime = Showtime::with('room')->findOrFail($id);

        $user = $request->user();
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('delete:showtime', 'cinema');
            if (!in_array('*', $allowedCinemaIds) && !in_array($showtime->room?->cinema_id, $allowedCinemaIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa lịch chiếu của rạp này.'
                ], 403);
            }
        }

        $showtime->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa lịch chiếu thành công.'
        ]);
    }
}
