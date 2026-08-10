<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Models\Room;
use App\Models\ScheduleSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    /**
     * Danh sách suất chiếu với bộ lọc + phân trang.
     */
    public function index(Request $request)
    {
        $query = Schedule::with(['movie:id,title,slug,poster_path,duration_minutes', 'room:room_id,room_name,room_type,cinema_id']);

        // Lọc theo ngày chiếu
        if ($request->has('date')) {
            $query->where('schedule_date', $request->date);
        }

        // Lọc theo phim
        if ($request->has('movie_id')) {
            $query->where('movie_id', $request->movie_id);
        }

        // Lọc theo phòng
        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Lọc theo rạp (qua room)
        if ($request->has('cinema_id')) {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('cinema_id', $request->cinema_id);
            });
        }

        $schedules = $query
            ->withCount('scheduleSeats')
            ->orderBy('schedule_date', 'desc')
            ->orderBy('schedule_start', 'asc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $schedules
        ]);
    }

    /**
     * Tạo suất chiếu mới.
     * Tự động copy ghế từ phòng → schedule_seats với giá = base_price + surcharge.
     */
    public function store(StoreScheduleRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $schedule = Schedule::create($data);

            // Lấy tất cả ghế active của phòng
            $room = Room::with(['seats' => function ($q) {
                $q->where('is_active', true);
            }])->findOrFail($data['room_id']);

            // Copy ghế vào schedule_seats với giá = base_price + surcharge
            $scheduleSeats = [];
            $now = now();

            foreach ($room->seats as $seat) {
                $scheduleSeats[] = [
                    'schedule_id' => $schedule->schedule_id,
                    'seat_id'     => $seat->seat_id,
                    'status'      => 'available',
                    'price'       => $data['base_price'] + $seat->surcharge,
                ];
            }

            // Bulk insert
            if (!empty($scheduleSeats)) {
                ScheduleSeat::insert($scheduleSeats);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo suất chiếu thành công. Đã tạo ' . count($scheduleSeats) . ' ghế.',
                'data'    => $schedule->load(['movie:id,title,slug', 'room:room_id,room_name,room_type'])
                                      ->loadCount('scheduleSeats')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo suất chiếu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chi tiết 1 suất chiếu.
     */
    public function show(string $id)
    {
        $schedule = Schedule::with([
            'movie:id,title,slug,poster_path,duration_minutes',
            'room:room_id,room_name,room_type,cinema_id',
            'room.cinema:cinema_id,cinema_name,slug',
        ])
        ->withCount([
            'scheduleSeats',
            'scheduleSeats as available_seats_count' => function ($q) {
                $q->where('status', 'available');
            },
            'scheduleSeats as booked_seats_count' => function ($q) {
                $q->where('status', 'booked');
            },
        ])
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $schedule
        ]);
    }

    /**
     * Cập nhật suất chiếu (chỉ khi chưa có booking).
     */
    public function update(UpdateScheduleRequest $request, string $id)
    {
        $schedule = Schedule::findOrFail($id);

        // Kiểm tra đã có booking chưa
        if ($schedule->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật suất chiếu đã có người đặt vé.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();
            $oldRoomId = $schedule->room_id;
            $oldBasePrice = $schedule->base_price;

            $schedule->update($data);

            // Nếu đổi phòng → xóa ghế cũ, sinh ghế mới
            if (isset($data['room_id']) && $data['room_id'] != $oldRoomId) {
                $schedule->scheduleSeats()->delete();

                $room = Room::with(['seats' => function ($q) {
                    $q->where('is_active', true);
                }])->findOrFail($data['room_id']);

                $basePrice = $data['base_price'] ?? $schedule->base_price;
                $scheduleSeats = [];

                foreach ($room->seats as $seat) {
                    $scheduleSeats[] = [
                        'schedule_id' => $schedule->schedule_id,
                        'seat_id'     => $seat->seat_id,
                        'status'      => 'available',
                        'price'       => $basePrice + $seat->surcharge,
                    ];
                }

                if (!empty($scheduleSeats)) {
                    ScheduleSeat::insert($scheduleSeats);
                }
            }
            // Nếu chỉ đổi base_price (không đổi phòng) → cập nhật giá tất cả ghế
            elseif (isset($data['base_price']) && $data['base_price'] != $oldBasePrice) {
                $room = Room::with(['seats' => function ($q) {
                    $q->where('is_active', true);
                }])->findOrFail($schedule->room_id);

                foreach ($room->seats as $seat) {
                    $schedule->scheduleSeats()
                        ->where('seat_id', $seat->seat_id)
                        ->update(['price' => $data['base_price'] + $seat->surcharge]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật suất chiếu thành công.',
                'data'    => $schedule->fresh()->load(['movie:id,title,slug', 'room:room_id,room_name,room_type'])
                                               ->loadCount('scheduleSeats')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật suất chiếu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa suất chiếu (chỉ khi chưa có booking).
     * schedule_seats sẽ cascade delete theo FK constraint.
     */
    public function destroy(string $id)
    {
        $schedule = Schedule::findOrFail($id);

        // Kiểm tra đã có booking chưa
        if ($schedule->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa suất chiếu đã có người đặt vé.'
            ], 422);
        }

        $schedule->scheduleSeats()->delete();
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa suất chiếu thành công.'
        ]);
    }
}
