<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Showtime;

class ShowtimeService
{
    public function getGroupedShowtimes(array $filters)
    {
        $date = $filters['date'] ?? now()->toDateString();
        
        $query = Showtime::with(['movie.genres', 'room.cinema.province'])
            ->withCount(['showtimeSeats as available_seats' => fn($q) => $q->where('status', 'available')])
            ->whereDate('showtime_start', $date)
            ->orderBy('showtime_start');

        if (!empty($filters['cinema_id'])) {
            $query->whereHas('room', fn($q) => $q->where('cinema_id', $filters['cinema_id']));
        }

        $movieParam = $filters['movie_slug'] ?? $filters['movie'] ?? $filters['movie_id'] ?? null;
        if (!empty($movieParam)) {
            if (is_numeric($movieParam)) {
                $query->where('movie_id', (int) $movieParam);
            } else {
                $query->whereHas('movie', fn($q) => $q->where('slug', $movieParam));
            }
        }

        if (!empty($filters['province'])) {
            $province = $filters['province'];
            $query->whereHas('room.cinema.province', fn($q) => $q->where('province_name', $province));
        }

        if (!empty($filters['screen_type'])) {
            $screenType = $filters['screen_type'];
            $query->whereHas('room', fn($q) => $q->where('screen_type', $screenType));
        }

        if (!empty($filters['sound_technology'])) {
            $soundTech = $filters['sound_technology'];
            $query->whereHas('room', fn($q) => $q->where('sound_technology', $soundTech));
        }

        if (!empty($filters['room_type'])) {
            $roomType = $filters['room_type'];
            $query->whereHas('room', fn($q) => $q->where('room_type', 'ILIKE', "%{$roomType}%"));
        }

        $showtimes = $query->get();

        return $showtimes->groupBy('movie_id')->map(function ($items) {
            $movie = $items->first()->movie;
            
            $formatGroups = $items->groupBy(fn($s) => $s->room->screen_type ?? 'standard_2d')->map(function ($formatItems, $screenType) {
                $cinemaGroups = $formatItems->groupBy(fn($s) => $s->room->cinema_id)->map(function ($cinemaItems) {
                    $cinema = $cinemaItems->first()->room->cinema;
                    return [
                        'cinema' => $cinema,
                        'times'  => $cinemaItems->values()
                    ];
                })->values();

                $catalog = \App\Services\RoomFormatCatalog::getScreenTypes();
                $formatInfo = $catalog[$screenType] ?? null;

                return [
                    'screen_type' => $screenType,
                    'format_name' => $formatInfo ? $formatInfo['name'] : '2D Digital Tiêu Chuẩn',
                    'cinemas'     => $cinemaGroups,
                ];
            })->values();

            return [
                'movie'   => $movie,
                'formats' => $formatGroups,
            ];
        })->values();
    }

    public function getShowtimesByMovie($movieIdentifier, array $filters)
    {
        $movie = $movieIdentifier instanceof Movie
            ? $movieIdentifier
            : (is_numeric($movieIdentifier)
                ? Movie::findOrFail((int) $movieIdentifier)
                : Movie::where('slug', $movieIdentifier)->firstOrFail());

        $query = Showtime::with(['room.cinema.province'])
            ->withCount(['showtimeSeats as available_seats' => fn($q) => $q->where('status', 'available')])
            ->where('movie_id', $movie->movie_id)
            ->orderBy('showtime_start');

        if (!empty($filters['date'])) {
            $query->whereDate('showtime_start', $filters['date']);
        } else {
            $query->whereDate('showtime_start', '>=', now()->toDateString());
        }

        if (!empty($filters['cinema_id'])) {
            $query->whereHas('room', fn($q) => $q->where('cinema_id', $filters['cinema_id']));
        }

        if (!empty($filters['screen_type'])) {
            $screenType = $filters['screen_type'];
            $query->whereHas('room', fn($q) => $q->where('screen_type', $screenType));
        }

        if (!empty($filters['sound_technology'])) {
            $soundTech = $filters['sound_technology'];
            $query->whereHas('room', fn($q) => $q->where('sound_technology', $soundTech));
        }

        if (!empty($filters['room_type'])) {
            $roomType = $filters['room_type'];
            $query->whereHas('room', fn($q) => $q->where('room_type', 'ILIKE', "%{$roomType}%"));
        }

        $showtimes = $query->get();

        return $showtimes->groupBy(fn($s) => $s->showtime_start ? $s->showtime_start->format('Y-m-d') : '')
            ->map(function ($dateItems, $date) {
                $formatGroups = $dateItems->groupBy(fn($s) => $s->room->screen_type ?? 'standard_2d')->map(function ($formatItems, $screenType) {
                    $cinemaGroups = $formatItems->groupBy(fn($s) => $s->room->cinema_id)->map(function ($cinemaItems) {
                        $cinema = $cinemaItems->first()->room->cinema;
                        return [
                            'cinema' => $cinema,
                            'times'  => $cinemaItems->values()
                        ];
                    })->values();

                    $catalog = \App\Services\RoomFormatCatalog::getScreenTypes();
                    $formatInfo = $catalog[$screenType] ?? null;

                    return [
                        'screen_type' => $screenType,
                        'format_name' => $formatInfo ? $formatInfo['name'] : '2D Digital Tiêu Chuẩn',
                        'cinemas'     => $cinemaGroups,
                    ];
                })->values();

                return [
                    'date'    => $date,
                    'formats' => $formatGroups,
                ];
            })->values();
    }
    
    public function getShowtimeDetail(int $id)
    {
        return Showtime::with(['movie', 'room.cinema', 'showtimeSeats.seat.seatType'])
            ->withCount(['showtimeSeats as available_seats' => fn($q) => $q->where('status', 'available')])
            ->findOrFail($id);
    }
}
