<?php

namespace App\Services;

use App\Models\Schedule;

class ShowtimeService
{
    public function getGroupedShowtimes(array $filters)
    {
        $date = $filters['date'] ?? now()->toDateString();
        
        $query = Schedule::with(['movie.genres', 'room.cinema.province'])
            ->withCount(['scheduleSeats as available_seats' => fn($q) => $q->where('status', 'available')])
            ->whereDate('schedule_date', $date)
            ->orderBy('schedule_start');

        if (!empty($filters['cinema_id'])) {
            $query->whereHas('room', fn($q) => $q->where('cinema_id', $filters['cinema_id']));
        }

        if (!empty($filters['movie_id'])) {
            $query->where('movie_id', $filters['movie_id']);
        }

        if (!empty($filters['province'])) {
            $province = $filters['province'];
            $query->whereHas('room.cinema.province', fn($q) => $q->where('province_name', $province));
        }

        $schedules = $query->get();

        return $schedules->groupBy('movie_id')->map(function ($items) {
            $movie = $items->first()->movie;
            
            $cinemaGroups = $items->groupBy(fn($s) => $s->room->cinema_id)->map(function ($cinemaItems) {
                $cinema = $cinemaItems->first()->room->cinema;
                return [
                    'cinema' => $cinema,
                    'times'  => $cinemaItems->values()
                ];
            })->values();

            return [
                'movie'   => $movie,
                'cinemas' => $cinemaGroups,
            ];
        })->values();
    }

    public function getShowtimesByMovie(int $movieId, array $filters)
    {
        $query = Schedule::with(['room.cinema.province'])
            ->withCount(['scheduleSeats as available_seats' => fn($q) => $q->where('status', 'available')])
            ->where('movie_id', $movieId)
            ->orderBy('schedule_date')
            ->orderBy('schedule_start');

        if (!empty($filters['date'])) {
            $query->whereDate('schedule_date', $filters['date']);
        } else {
            $query->whereDate('schedule_date', '>=', now()->toDateString());
        }

        if (!empty($filters['cinema_id'])) {
            $query->whereHas('room', fn($q) => $q->where('cinema_id', $filters['cinema_id']));
        }

        $schedules = $query->get();

        return $schedules->groupBy(fn($s) => $s->schedule_date->format('Y-m-d'))
            ->map(function ($dateItems, $date) {
                $cinemaGroups = $dateItems->groupBy(fn($s) => $s->room->cinema_id)->map(function ($cinemaItems) {
                    $cinema = $cinemaItems->first()->room->cinema;
                    return [
                        'cinema' => $cinema,
                        'times'  => $cinemaItems->values()
                    ];
                })->values();

                return [
                    'date'    => $date,
                    'cinemas' => $cinemaGroups,
                ];
            })->values();
    }
    
    public function getShowtimeDetail(int $id)
    {
        return Schedule::with(['movie.genres', 'room.cinema.province'])
            ->withCount(['scheduleSeats as available_seats' => fn($q) => $q->where('status', 'available')])
            ->findOrFail($id);
    }
}
