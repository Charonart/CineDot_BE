<?php

namespace App\Services;

use App\Models\Cinema;

class CinemaService
{
    public function getList(?string $province = null)
    {
        $query = Cinema::with('province');

        if ($province && $province !== 'all') {
            $query->whereHas('province', fn($q) => $q->where('province_name', $province));
        }

        return $query->orderBy('cinema_name')->get();
    }

    public function getDetailBySlug(string $slug)
    {
        return Cinema::with(['province', 'rooms'])->where('slug', $slug)->firstOrFail();
    }

    public function getShowtimesBySlug(string $slug, string $date)
    {
        $cinema = $this->getDetailBySlug($slug);
        
        $schedules = \App\Models\Schedule::with(['movie', 'room'])
            ->whereHas('room', function ($q) use ($cinema) {
                $q->where('cinema_id', $cinema->cinema_id);
            })
            ->whereDate('start_time', $date)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get();

        $grouped = $schedules->groupBy('movie_id')->map(function ($times) {
            $movie = $times->first()->movie;
            return [
                'movie' => $movie,
                'times' => $times,
            ];
        })->values();

        return $grouped;
    }

    public function getDetail(int $id)
    {
        return Cinema::with(['province', 'rooms'])->findOrFail($id);
    }
}
