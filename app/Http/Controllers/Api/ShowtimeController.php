<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Models\Movie;
use App\Models\Cinema;
use Illuminate\Http\Request;

class ShowtimeController extends Controller
{
    /**
     * GET /api/showtimes
     * Xem lịch chiếu theo Ngày và/hoặc Cụm rạp.
     *
     * Query params:
     *   ?date=2025-06-01          → lọc theo ngày chiếu (bắt buộc hoặc mặc định hôm nay)
     *   ?cinema_id=1              → lọc theo rạp cụ thể
     *   ?movie_id=3               → lọc theo phim cụ thể
     *   ?city=Hà+Nội              → lọc theo thành phố (qua cinema)
     *   ?format=IMAX              → lọc theo định dạng chiếu
     *
     * Response group theo movie → cinemas → showtimes
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $query = Showtime::with(['movie.genres', 'cinema'])
            ->where('show_date', $date)
            ->orderBy('start_time');

        if ($request->filled('cinema_id')) {
            $query->where('cinema_id', $request->cinema_id);
        }

        if ($request->filled('movie_id')) {
            $query->where('movie_id', $request->movie_id);
        }

        if ($request->filled('format')) {
            $query->where('format', $request->format);
        }

        if ($request->filled('city')) {
            $query->whereHas('cinema', fn($q) => $q->where('city', $request->city));
        }

        $showtimes = $query->get();

        // Group: movie → cinemas → showtimes
        $grouped = $showtimes->groupBy('movie_id')->map(function ($items) {
            $movie = $items->first()->movie;

            $cinemaGroups = $items->groupBy('cinema_id')->map(function ($cinemaItems) {
                $cinema = $cinemaItems->first()->cinema;

                return [
                    'cinema' => [
                        'id'      => $cinema->id,
                        'name'    => $cinema->name,
                        'chain'   => $cinema->chain,
                        'city'    => $cinema->city,
                        'address' => $cinema->address,
                    ],
                    'times' => $cinemaItems->map(fn($s) => [
                        'id'             => $s->id,
                        'startTime'      => $s->start_time,
                        'endTime'        => $s->end_time,
                        'screen'         => $s->screen,
                        'format'         => $s->format,
                        'price'          => $s->price,
                        'availableSeats' => $s->available_seats,
                    ])->values(),
                ];
            })->values();

            return [
                'movie'   => $movie,
                'cinemas' => $cinemaGroups,
            ];
        })->values();

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
        $showtime = Showtime::with(['movie.genres', 'cinema'])->find($id);

        if (!$showtime) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy suất chiếu',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $showtime->id,
                'showDate'       => $showtime->show_date,
                'startTime'      => $showtime->start_time,
                'endTime'        => $showtime->end_time,
                'screen'         => $showtime->screen,
                'format'         => $showtime->format,
                'price'          => $showtime->price,
                'availableSeats' => $showtime->available_seats,
                'movie'          => $showtime->movie,
                'cinema'         => $showtime->cinema,
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
        $movie = Movie::find($movieId);

        if (!$movie) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phim',
            ], 404);
        }

        $query = Showtime::with('cinema')
            ->where('movie_id', $movieId)
            ->orderBy('show_date')
            ->orderBy('start_time');

        if ($request->filled('date')) {
            $query->where('show_date', $request->date);
        } else {
            // Mặc định: từ hôm nay trở đi
            $query->where('show_date', '>=', now()->toDateString());
        }

        if ($request->filled('cinema_id')) {
            $query->where('cinema_id', $request->cinema_id);
        }

        $showtimes = $query->get();

        // Group theo ngày → rạp
        $grouped = $showtimes->groupBy(fn($s) => $s->show_date->format('Y-m-d'))
            ->map(function ($dateItems, $date) {
                $cinemaGroups = $dateItems->groupBy('cinema_id')->map(function ($cinemaItems) {
                    $cinema = $cinemaItems->first()->cinema;

                    return [
                        'cinema' => [
                            'id'    => $cinema->id,
                            'name'  => $cinema->name,
                            'chain' => $cinema->chain,
                            'city'  => $cinema->city,
                        ],
                        'times' => $cinemaItems->map(fn($s) => [
                            'id'             => $s->id,
                            'startTime'      => $s->start_time,
                            'endTime'        => $s->end_time,
                            'format'         => $s->format,
                            'price'          => $s->price,
                            'availableSeats' => $s->available_seats,
                        ])->values(),
                    ];
                })->values();

                return [
                    'date'    => $date,
                    'cinemas' => $cinemaGroups,
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'movie'   => ['id' => $movie->id, 'title' => $movie->title, 'posterUrl' => $movie->poster_url],
                'results' => $grouped,
            ],
        ]);
    }
}
