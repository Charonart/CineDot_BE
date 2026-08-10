<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetShowtimesRequest;
use App\Http\Resources\CinemaResource;
use App\Http\Resources\MovieResource;
use App\Http\Resources\SeatResource;
use App\Http\Resources\ShowtimeResource;
use App\Services\SeatService;

use App\Services\ShowtimeService;
use App\Models\Movie;

class ShowtimeController extends Controller
{
    public function __construct(
        private ShowtimeService $showtimeService,
        private SeatService $seatService
    ) {}

    public function index(GetShowtimesRequest $request)
    {
        $filters = $request->validated();
        $date = $filters['date'] ?? now()->toDateString();
        
        $grouped = $this->showtimeService->getGroupedShowtimes($filters);

        $results = $grouped->map(function ($item) {
            return [
                'movie'   => new MovieResource($item['movie']),
                'cinemas' => $item['cinemas']->map(function ($c) {
                    return [
                        'cinema' => new CinemaResource($c['cinema']),
                        'times'  => ShowtimeResource::collection($c['times']),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'date'    => $date,
                'results' => $results,
            ],
        ]);
    }

    public function show($id)
    {
        $schedule = $this->showtimeService->getShowtimeDetail($id);
        $cinema = $schedule->room->cinema;

        $showtimeData = (new ShowtimeResource($schedule))->resolve();
        $showtimeData['movie'] = new MovieResource($schedule->movie);
        $showtimeData['cinema'] = new CinemaResource($cinema);

        $cinema = $schedule->room->cinema;

        return response()->json([
            'success' => true,
            'data'    => $showtimeData,
        ]);
    }

    public function byMovie(GetShowtimesRequest $request, $identifier)
    {
        $movie = is_numeric($identifier)
            ? Movie::findOrFail((int) $identifier)
            : Movie::where('slug', $identifier)->firstOrFail();

        $grouped = $this->showtimeService->getShowtimesByMovie($movie->movie_id, $request->validated());

        $results = $grouped->map(function ($item) {
            return [
                'date'    => $item['date'],
                'cinemas' => $item['cinemas']->map(function ($c) {
                    return [
                        'cinema' => new CinemaResource($c['cinema']),
                        'times'  => ShowtimeResource::collection($c['times']),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'movie'   => [
                    'id'        => $movie->movie_id, 
                    'slug'      => $movie->slug,
                    'title'     => $movie->title, 
                    'posterUrl' => $movie->poster_path
                ],
                'results' => $results,
            ],
        ]);
    }


    public function seats($id)
    {
        $seats = $this->seatService->getScheduleSeats($id);

        return response()->json([
            'success' => true,
            'data'    => SeatResource::collection($seats),
        ]);
    }

    /**
     * Get real-time seat status map (Key-Value) for showtime.
     */
    public function seatStatus($id)
    {
        $showtimeSeats = \App\Models\ShowtimeSeat::where('showtime_id', $id)->get();

        $statusMap = [];
        foreach ($showtimeSeats as $seat) {
            $status = strtoupper($seat->status);
            
            // Check Redis hold key if available in DB
            if ($status === 'AVAILABLE') {
                $redisKey = "hold:showtime:{$id}:seat:{$seat->showtime_seat_id}";
                if (\Illuminate\Support\Facades\Redis::exists($redisKey)) {
                    $status = 'HOLD';
                }
            }

            $statusMap[$seat->showtime_seat_id] = $status;
        }

        return response()->json($statusMap);
    }
}

