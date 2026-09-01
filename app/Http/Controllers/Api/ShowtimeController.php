<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetShowtimesRequest;
use App\Http\Resources\CinemaResource;
use App\Http\Resources\MovieResource;
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

        $grouped = $this->showtimeService->getShowtimesByMovie($movie, $request->validated());

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
        $data = $this->seatService->getScheduleSeats((int) $id);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Get real-time seat status map for showtime.
     */
    public function seatStatus($id)
    {
        $showtimeSeats = \App\Models\ShowtimeSeat::with('seat')->where('showtime_id', $id)->get();
        if ($showtimeSeats->isEmpty()) {
            $this->seatService->getScheduleSeats((int) $id);
            $showtimeSeats = \App\Models\ShowtimeSeat::with('seat')->where('showtime_id', $id)->get();
        }
        $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);

        $pendingSeatIds = \App\Models\BookingSeat::whereHas('booking', function ($query) use ($id, $ttlSeconds) {
            $query->where('showtime_id', $id)
                  ->where('booking_status', 'pending')
                  ->where('created_at', '>=', \Carbon\Carbon::now()->subSeconds($ttlSeconds));
        })->pluck('showtime_seat_id')->flip()->toArray();

        // Single batch Redis pipeline lookup for all seats
        $heldInRedisMap = [];
        try {
            $pipelineResults = \Illuminate\Support\Facades\Redis::pipeline(function ($pipe) use ($id, $showtimeSeats) {
                foreach ($showtimeSeats as $seat) {
                    $pipe->exists("hold:showtime:{$id}:seat:{$seat->showtime_seat_id}");
                }
            });

            foreach ($showtimeSeats as $idx => $seat) {
                if (!empty($pipelineResults[$idx])) {
                    $heldInRedisMap[$seat->showtime_seat_id] = true;
                }
            }
        } catch (\Throwable $e) {
            // Redis fallback
        }

        $seatsData = [];
        foreach ($showtimeSeats as $seat) {
            $status = strtolower($seat->status);
            
            if ($status === 'available') {
                $isHeldInDb = isset($pendingSeatIds[$seat->showtime_seat_id]);
                $isHeldInRedis = isset($heldInRedisMap[$seat->showtime_seat_id]);

                if ($isHeldInDb || $isHeldInRedis) {
                    $status = 'holding';
                }
            }

            $physical = $seat->seat;
            $rName = $physical ? $physical->row_name : '';
            $sNum = $physical ? (string) $physical->seat_number : '';
            $sType = $physical ? $physical->seat_type : 'standard';

            $seatsData[] = [
                'showtime_seat_id' => $seat->showtime_seat_id,
                'showtime_id'      => (int) $seat->showtime_id,
                'row_name'         => $rName,
                'seat_number'      => $sNum,
                'seat_code'        => $rName . $sNum,
                'seat_type'        => $sType,
                'status'           => $status,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'showtime_id' => (int) $id,
                'total_seats' => count($seatsData),
                'seats'       => $seatsData,
            ]
        ]);
    }
}

