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

    public function getDetail(int $id)
    {
        return Cinema::with(['province', 'rooms'])->findOrFail($id);
    }
}
