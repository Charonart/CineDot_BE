<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ShowtimeController extends Controller
{
    /**
     * GET /api/showtimes
     * Xem lịch chiếu theo Ngày và/hoặc Rạp.
     *
     * Query params:
     *   ?date=2025-06-01          → lọc theo ngày chiếu (mặc định hôm nay)
     *   ?cinema_id=1              → lọc theo rạp cụ thể
     *   ?movie_id=3               → lọc theo phim cụ thể
     *   ?province=Hà Nội          → lọc theo tỉnh/thành
     *
     * Response group theo movie → cinemas → showtimes
     */
    public function index(Request $request)
    {
        $date      = $request->get('date', now()->toDateString());
        $cinemaId  = $request->get('cinema_id');
        $movieId   = $request->get('movie_id');
        $province  = $request->get('province');

        // Cache key phân biệt theo tham số
        $cacheKey = "showtimes.index.{$date}.c{$cinemaId}.m{$movieId}.p{$province}";

        $grouped = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($date, $cinemaId, $movieId, $province) {
            $query = Schedule::with(['movie.genres', 'room.cinema.province'])
                // FIX: dùng withCount thay vì gọi ->count() trong vòng lặp (N+1)
                ->withCount(['scheduleSeats as available_seats' => fn($q) => $q->where('status', 'available')])
                ->where('schedule_date', $date)
                ->orderBy('schedule_start');

            if ($cinemaId) {
                $query->whereHas('room', fn($q) => $q->where('cinema_id', $cinemaId));
            }

            if ($movieId) {
                $query->where('movie_id', $movieId);
            }

            if ($province) {
                $query->whereHas('room.cinema.province', fn($q) => $q->where('province_name', $province));
            }

            $schedules = $query->get();

            // Group: movie → cinemas → showtimes
            return $schedules->groupBy('movie_id')->map(function ($items) {
                $movie = $items->first()->movie;

                $cinemaGroups = $items->groupBy(fn($s) => $s->room->cinema_id)->map(function ($cinemaItems) {
                    $cinema = $cinemaItems->first()->room->cinema;

                    return [
                        'cinema' => [
                            'id'       => $cinema->cinema_id,
                            'name'     => $cinema->cinema_name,
                            'province' => $cinema->province?->province_name,
                            'address'  => $cinema->cinema_address,
                        ],
                        'times' => $cinemaItems->map(fn($s) => [
                            'id'             => $s->schedule_id,
                            'startTime'      => $s->schedule_start,
                            'endTime'        => $s->schedule_end,
                            'screen'         => $s->room->room_name,
                            'format'         => $s->room->room_type,
                            'price'          => $s->base_price,
                            'availableSeats' => $s->available_seats, // Từ withCount, không query thêm
                        ])->values(),
                    ];
                })->values();

                return [
                    'movie'   => $movie,
                    'cinemas' => $cinemaGroups,
                ];
            })->values();
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'date'    => $date,
                'results' => $grouped,
            ],
        ]);
    }

    /**
     * GET /api/showtimes/{id}
     * Chi tiết 1 suất chiếu cụ thể.
     */
    public function show($id)
    {
        $schedule = Cache::remember("showtime.{$id}", now()->addMinutes(5), function () use ($id) {
            return Schedule::with(['movie.genres', 'room.cinema.province'])
                ->withCount(['scheduleSeats as available_seats' => fn($q) => $q->where('status', 'available')])
                ->find($id);
        });

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy suất chiếu',
            ], 404);
        }

        $cinema = $schedule->room->cinema;

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $schedule->schedule_id,
                'showDate'       => $schedule->schedule_date->format('Y-m-d'),
                'startTime'      => $schedule->schedule_start,
                'endTime'        => $schedule->schedule_end,
                'screen'         => $schedule->room->room_name,
                'format'         => $schedule->room->room_type,
                'price'          => $schedule->base_price,
                'availableSeats' => $schedule->available_seats,
                'movie'          => $schedule->movie,
                'cinema'         => [
                    'id'       => $cinema->cinema_id,
                    'name'     => $cinema->cinema_name,
                    'province' => $cinema->province?->province_name,
                    'address'  => $cinema->cinema_address,
                ],
            ],
        ]);
    }

    /**
     * GET /api/movies/{id}/showtimes
     * Tất cả lịch chiếu của 1 phim (group theo ngày → rạp).
     * Query params: ?date=2025-06-01  (optional)
     */
    public function byMovie(Request $request, $movieId)
    {
        $movie = Cache::remember("movie.basic.{$movieId}", now()->addMinutes(30), function () use ($movieId) {
            return Movie::select('id', 'title', 'poster_path')->find($movieId);
        });

        if (!$movie) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phim',
            ], 404);
        }

        $date     = $request->get('date');
        $cinemaId = $request->get('cinema_id');
        $cacheKey = "showtimes.movie.{$movieId}.d{$date}.c{$cinemaId}";

        $grouped = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($movieId, $date, $cinemaId) {
            $query = Schedule::with('room.cinema.province')
                // FIX: dùng withCount thay vì gọi ->count() trong vòng lặp (N+1)
                ->withCount(['scheduleSeats as available_seats' => fn($q) => $q->where('status', 'available')])
                ->where('movie_id', $movieId)
                ->orderBy('schedule_date')
                ->orderBy('schedule_start');

            if ($date) {
                $query->where('schedule_date', $date);
            } else {
                // Mặc định: từ hôm nay trở đi
                $query->where('schedule_date', '>=', now()->toDateString());
            }

            if ($cinemaId) {
                $query->whereHas('room', fn($q) => $q->where('cinema_id', $cinemaId));
            }

            $schedules = $query->get();

            // Group theo ngày → rạp
            return $schedules->groupBy(fn($s) => $s->schedule_date->format('Y-m-d'))
                ->map(function ($dateItems, $date) {
                    $cinemaGroups = $dateItems->groupBy(fn($s) => $s->room->cinema_id)->map(function ($cinemaItems) {
                        $cinema = $cinemaItems->first()->room->cinema;

                        return [
                            'cinema' => [
                                'id'       => $cinema->cinema_id,
                                'name'     => $cinema->cinema_name,
                                'province' => $cinema->province?->province_name,
                            ],
                            'times' => $cinemaItems->map(fn($s) => [
                                'id'             => $s->schedule_id,
                                'startTime'      => $s->schedule_start,
                                'endTime'        => $s->schedule_end,
                                'format'         => $s->room->room_type,
                                'price'          => $s->base_price,
                                'availableSeats' => $s->available_seats, // Từ withCount, không query thêm
                            ])->values(),
                        ];
                    })->values();

                    return [
                        'date'    => $date,
                        'cinemas' => $cinemaGroups,
                    ];
                })->values();
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'movie'   => ['id' => $movie->id, 'title' => $movie->title, 'posterUrl' => $movie->poster_path],
                'results' => $grouped,
            ],
        ]);
    }
}
