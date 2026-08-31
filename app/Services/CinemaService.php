<?php

namespace App\Services;

use App\Models\Cinema;
use App\Models\Showtime;

class CinemaService
{
    public function getList($filter = null)
    {
        $query = Cinema::with('province')->where('is_active', true);

        if (is_array($filter)) {
            $code = $filter['code'] ?? $filter['province_code'] ?? null;
            $provinceId = $filter['province_id'] ?? null;
            $provinceName = $filter['province'] ?? $filter['city'] ?? null;

            if ($code && $code !== 'all' && $code !== 'Tất cả thành phố') {
                $query->whereHas('province', fn($q) => $q->where('province_code', $code)->orWhere('province_name', $code));
            } elseif ($provinceId && $provinceId !== 'all') {
                $query->where('province_id', (int) $provinceId);
            } elseif (!empty($provinceName) && $provinceName !== 'Toàn quốc') {
                $query->whereHas('province', fn($q) => $q->where('province_name', 'like', '%' . $provinceName . '%')->orWhere('province_code', $provinceName));
            }
        } elseif (!empty($filter) && $filter !== 'all') {
            $query->whereHas('province', fn($q) => $q->where('province_code', $filter)->orWhere('province_name', 'like', '%' . $filter . '%'));
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
        
        $showtimes = Showtime::with(['movie', 'room'])
            ->whereHas('room', function ($q) use ($cinema) {
                $q->where('cinema_id', $cinema->cinema_id);
            })
            ->whereDate('showtime_start', $date)
            ->orderBy('showtime_start')
            ->get();

        $grouped = $showtimes->groupBy('movie_id')->map(function ($times) {
            return [
                'movie' => $times->first()->movie,
                'times' => $times->values(),
            ];
        })->values();

        return $grouped;
    }

    public function getDetail(int $id)
    {
        return Cinema::with(['province', 'rooms'])->findOrFail($id);
    }
}
