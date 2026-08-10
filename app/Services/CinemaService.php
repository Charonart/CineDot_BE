<?php

namespace App\Services;

use App\Models\Cinema;
use App\Models\Showtime;

class CinemaService
{
    public function getList(?string $province = null)
    {
        $query = Cinema::with('province');

        if ($province && $province !== 'all') {
            $query->whereHas('province', fn($q) => $q->where('province_name', $province));
        }

        return $query->orderBy('name')->get();
    }

    public function getDetailBySlug(string $slug)
    {
        return Cinema::with(['province', 'rooms'])->where('slug', $slug)->firstOrFail();
    }

    public function getShowtimesBySlug(string $slug, string $date)
    {
        $cinema = $this->getDetailBySlug($slug);
        
        $showtimes = Showtime::with(['movie', 'room'])
            ->whereHas('room', function ($q) use ($cinema) {
                $q->where('cinema_id', $cinema->cinema_id);
            })
            ->whereDate('showtime_start', $date)
            ->orderBy('showtime_start')
            ->get();

        $grouped = $showtimes->groupBy('movie_id')->map(function ($times) {
            $movie = $times->first()->movie;
            return [
                'movie' => $movie,
                'times' => \App\Http\Resources\ShowtimeResource::collection($times),
            ];
        })->values();

        return $grouped;
    }

    public function getDetail(int $id)
    {
        return Cinema::with(['province', 'rooms'])->findOrFail($id);
    }
}
