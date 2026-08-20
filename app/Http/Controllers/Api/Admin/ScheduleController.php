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
     * Danh sách lịch chiếu (Admin) - Hỗ trợ Context Scoping, lọc theo rạp, ngày, phòng & thống kê ghế đặt
     */
    public function index(Request $request)
    {
        $query = Showtime::with(['movie', 'room.cinema'])
            ->withCount([
                'showtimeSeats as total_seats_count',
                'showtimeSeats as booked_seats_count' => function ($q) {
                    $q->where('status', 'booked');
                }
            ]);

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

        if ($request->has('cinema_id') && $request->cinema_id) {
            $cinemaId = $request->cinema_id;
            $query->whereHas('room', function ($q) use ($cinemaId) {
                $q->where('cinema_id', $cinemaId);
            });
        }

        if ($request->has('room_id') && $request->room_id) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->has('movie_id') && $request->movie_id) {
            $query->where('movie_id', $request->movie_id);
        }

        if ($request->has('date') && $request->date) {
            $query->whereDate('showtime_start', $request->date);
        }

        $limit = (int) $request->get('limit', 100);
        $showtimes = $query->orderBy('showtime_start', 'asc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => $showtimes
        ]);
    }

    /**
     * Sắp xếp lịch chiếu mới (POST /admin/showtimes)
     * Tự động kiểm tra xung đột thời gian (Collision Detection) & nạp sơ đồ ghế tĩnh (seat_matrix)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'movie_id'        => 'required|exists:movies,movie_id',
            'room_id'         => 'required|exists:rooms,room_id',
            'showtime_start'  => 'required_without:start_time|nullable|date',
            'start_time'      => 'required_without:showtime_start|nullable|date',
            'showtime_end'    => 'nullable|date',
            'end_time'        => 'nullable|date',
            'price'           => 'nullable|numeric|min:0',
            'base_price'      => 'nullable|numeric|min:0',
            'buffer_minutes'  => 'nullable|integer|min:0|max:60',
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
        $bufferMinutes = (int) ($validated['buffer_minutes'] ?? 15);

        $movie = Movie::findOrFail($validated['movie_id']);
        $startTime = Carbon::parse($startRaw);
        $endTime = $endRaw 
            ? Carbon::parse($endRaw) 
            : (clone $startTime)->addMinutes($movie->duration ?? 120);

        // ── 1. Collision Detection (Kiểm tra chồng chéo lịch cùng phòng chiếu) ──
        $conflict = $this->checkScheduleConflict($room->room_id, $startTime, $endTime, $bufferMinutes);
        if ($conflict) {
            $conflictMovie = $conflict->movie?->title ?? 'Suất chiếu khác';
            $cStart = Carbon::parse($conflict->showtime_start)->format('H:i');
            $cEnd = Carbon::parse($conflict->showtime_end)->format('H:i');

            return response()->json([
                'success' => false,
                'message' => "Xung đột lịch chiếu: Phòng này đã có suất phim '{$conflictMovie}' từ {$cStart} đến {$cEnd} (cần tối thiểu {$bufferMinutes} phút dọn phòng trước/sau suất).",
                'conflict' => [
                    'showtime_id'    => $conflict->showtime_id,
                    'movie_title'    => $conflictMovie,
                    'showtime_start' => $conflict->showtime_start,
                    'showtime_end'   => $conflict->showtime_end,
                ]
            ], 422);
        }

        return DB::transaction(function () use ($movie, $room, $startTime, $endTime, $basePrice) {
            // 2. Tạo Suất chiếu
            $showtime = Showtime::create([
                'movie_id'       => $movie->movie_id,
                'room_id'        => $room->room_id,
                'showtime_start' => $startTime,
                'showtime_end'   => $endTime,
                'base_price'     => $basePrice,
                'layout_snaps'   => $room->seat_matrix,
            ]);

            // 3. Nhân bản ghế cho suất chiếu từ seat_matrix của Phòng
            $matrix = $room->seat_matrix ?? [];
            $seatsToInsert = [];

            if (is_array($matrix)) {
                foreach ($matrix as $seat) {
                    if (is_array($seat)) {
                        $seatIdStr = $seat['id'] ?? $seat['seat_id'] ?? '';
                        $rowName = $seat['row_name'] ?? $seat['row'] ?? (strlen($seatIdStr) > 0 ? substr($seatIdStr, 0, 1) : 'A');
                        $seatNumber = $seat['seat_number'] ?? $seat['number'] ?? (strlen($seatIdStr) > 1 ? substr($seatIdStr, 1) : '1');

                        $rawType = (string) ($seat['seat_type'] ?? $seat['type'] ?? 'standard');
                        $seatType = \App\Models\SeatType::resolveTypeKey($rawType);

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
                'message' => 'Tạo suất chiếu và đồng bộ sơ đồ ghế thành công.',
                'data'    => $showtime->load(['movie', 'room.cinema'])
            ], 201);
        });
    }

    /**
     * Chi tiết lịch chiếu
     */
    public function show(string $id)
    {
        $showtime = Showtime::with(['movie', 'room.cinema', 'showtimeSeats'])
            ->withCount([
                'showtimeSeats as total_seats_count',
                'showtimeSeats as booked_seats_count' => function ($q) {
                    $q->where('status', 'booked');
                }
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $showtime
        ]);
    }

    /**
     * Cập nhật lịch chiếu (PUT /admin/showtimes/{id})
     */
    public function update(Request $request, string $id)
    {
        $showtime = Showtime::with(['room', 'movie'])->findOrFail($id);

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
            'movie_id'        => 'sometimes|exists:movies,movie_id',
            'room_id'         => 'sometimes|exists:rooms,room_id',
            'showtime_start'  => 'sometimes|date',
            'start_time'      => 'sometimes|date',
            'showtime_end'    => 'sometimes|date',
            'end_time'        => 'sometimes|date',
            'base_price'      => 'sometimes|numeric|min:0',
            'price'           => 'sometimes|numeric|min:0',
            'buffer_minutes'  => 'nullable|integer|min:0|max:60',
        ]);

        // Kiểm tra xem đã có vé đặt cho suất chiếu này chưa
        $bookedSeatsCount = ShowtimeSeat::where('showtime_id', $id)->where('status', 'booked')->count();
        $hasBookedTickets = $bookedSeatsCount > 0;

        if ($hasBookedTickets) {
            // Không cho phép đổi sang phim khác hoặc phòng chiếu khác
            if (isset($validated['movie_id']) && $validated['movie_id'] != $showtime->movie_id) {
                return response()->json([
                    'success' => false,
                    'message' => "Suất chiếu này đã có {$bookedSeatsCount} ghế được mua vé, không thể đổi sang phim khác."
                ], 422);
            }
            if (isset($validated['room_id']) && $validated['room_id'] != $showtime->room_id) {
                return response()->json([
                    'success' => false,
                    'message' => "Suất chiếu này đã có {$bookedSeatsCount} ghế được mua vé, không thể đổi sang phòng chiếu khác."
                ], 422);
            }
        }

        $targetRoomId = $validated['room_id'] ?? $showtime->room_id;
        $startRaw = $validated['showtime_start'] ?? $validated['start_time'] ?? $showtime->showtime_start;
        $endRaw = $validated['showtime_end'] ?? $validated['end_time'] ?? $showtime->showtime_end;
        $bufferMinutes = (int) ($validated['buffer_minutes'] ?? 15);

        $startTime = Carbon::parse($startRaw);
        $endTime = Carbon::parse($endRaw);

        // Kiểm tra xung đột thời gian (trừ chính suất chiếu này)
        $conflict = $this->checkScheduleConflict($targetRoomId, $startTime, $endTime, $bufferMinutes, (int) $id);
        if ($conflict) {
            $conflictMovie = $conflict->movie?->title ?? 'Suất chiếu khác';
            $cStart = Carbon::parse($conflict->showtime_start)->format('H:i');
            $cEnd = Carbon::parse($conflict->showtime_end)->format('H:i');

            return response()->json([
                'success' => false,
                'message' => "Xung đột lịch chiếu: Phòng này đã có suất phim '{$conflictMovie}' từ {$cStart} đến {$cEnd}.",
            ], 422);
        }

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
        unset($validated['buffer_minutes']);

        $showtime->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật lịch chiếu thành công.',
            'data'    => $showtime->load(['movie', 'room.cinema'])
        ]);
    }

    /**
     * Xóa lịch chiếu (DELETE /admin/showtimes/{id})
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

        // Chặn xóa nếu suất chiếu đã có vé bán
        $bookedSeatsCount = ShowtimeSeat::where('showtime_id', $id)->where('status', 'booked')->count();
        if ($bookedSeatsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xóa suất chiếu này vì đã có {$bookedSeatsCount} vé được khán giả đặt thành công."
            ], 422);
        }

        DB::transaction(function () use ($showtime) {
            ShowtimeSeat::where('showtime_id', $showtime->showtime_id)->delete();
            $showtime->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Xóa suất chiếu thành công.'
        ]);
    }

    /**
     * Sao chép toàn bộ lịch chiếu từ source_date sang target_date (POST /admin/showtimes/clone-date)
     */
    public function cloneDate(Request $request)
    {
        $request->validate([
            'source_date' => 'required|date_format:Y-m-d',
            'target_date' => 'required|date_format:Y-m-d|different:source_date',
            'cinema_id'   => 'nullable|exists:cinemas,cinema_id',
        ]);

        $sourceDate = $request->source_date;
        $targetDate = $request->target_date;
        $cinemaId = $request->cinema_id;

        $query = Showtime::with(['room', 'movie'])->whereDate('showtime_start', $sourceDate);
        if ($cinemaId) {
            $query->whereHas('room', function ($q) use ($cinemaId) {
                $q->where('cinema_id', $cinemaId);
            });
        }

        $sourceShowtimes = $query->get();

        if ($sourceShowtimes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy suất chiếu nào trong ngày nguồn ({$sourceDate}) để sao chép."
            ], 404);
        }

        $clonedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($sourceShowtimes, $targetDate, &$clonedCount, &$skippedCount) {
            foreach ($sourceShowtimes as $source) {
                $sStart = Carbon::parse($source->showtime_start);
                $sEnd = Carbon::parse($source->showtime_end);

                // Tính thời gian trên ngày đích
                $targetStart = Carbon::parse("{$targetDate} {$sStart->format('H:i:s')}");
                $targetEnd = Carbon::parse("{$targetDate} {$sEnd->format('H:i:s')}");
                if ($targetEnd->lessThan($targetStart)) {
                    $targetEnd->addDay();
                }

                // Kiểm tra xung đột ở ngày đích
                $conflict = $this->checkScheduleConflict($source->room_id, $targetStart, $targetEnd, 15);
                if ($conflict) {
                    $skippedCount++;
                    continue;
                }

                // Tạo suất chiếu mới
                $newShowtime = Showtime::create([
                    'movie_id'       => $source->movie_id,
                    'room_id'        => $source->room_id,
                    'showtime_start' => $targetStart,
                    'showtime_end'   => $targetEnd,
                    'base_price'     => $source->base_price,
                    'layout_snaps'   => $source->layout_snaps ?? $source->room?->seat_matrix,
                ]);

                // Clone sơ đồ ghế
                $matrix = $source->layout_snaps ?? $source->room?->seat_matrix ?? [];
                $seatsToInsert = [];
                if (is_array($matrix)) {
                    foreach ($matrix as $seat) {
                        if (is_array($seat)) {
                            $seatIdStr = $seat['id'] ?? $seat['seat_id'] ?? '';
                            $rowName = $seat['row_name'] ?? $seat['row'] ?? (strlen($seatIdStr) > 0 ? substr($seatIdStr, 0, 1) : 'A');
                            $seatNumber = $seat['seat_number'] ?? $seat['number'] ?? (strlen($seatIdStr) > 1 ? substr($seatIdStr, 1) : '1');
                            $rawType = (string) ($seat['seat_type'] ?? $seat['type'] ?? 'standard');
                            $seatType = \App\Models\SeatType::resolveTypeKey($rawType);

                            $seatsToInsert[] = [
                                'showtime_id' => $newShowtime->showtime_id,
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

                $clonedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Sao chép lịch chiếu thành công: Đã tạo {$clonedCount} suất chiếu trên ngày {$targetDate}" . ($skippedCount > 0 ? " (Bỏ qua {$skippedCount} suất do trùng lịch)." : "."),
            'data'    => [
                'cloned_count'  => $clonedCount,
                'skipped_count' => $skippedCount,
            ]
        ]);
    }

    /**
     * Helper: Kiểm tra xung đột lịch chiếu trong cùng phòng
     */
    private function checkScheduleConflict(int $roomId, Carbon $newStart, Carbon $newEnd, int $bufferMinutes = 15, ?int $excludeId = null)
    {
        $newStartWithBuffer = (clone $newStart)->subMinutes($bufferMinutes);
        $newEndWithBuffer = (clone $newEnd)->addMinutes($bufferMinutes);

        $query = Showtime::with('movie')
            ->where('room_id', $roomId)
            ->where(function ($q) use ($newStartWithBuffer, $newEndWithBuffer) {
                $q->where('showtime_start', '<', $newEndWithBuffer)
                  ->where('showtime_end', '>', $newStartWithBuffer);
            });

        if ($excludeId) {
            $query->where('showtime_id', '!=', $excludeId);
        }

        return $query->first();
    }
}
