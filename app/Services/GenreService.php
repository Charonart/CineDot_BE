<?php

namespace App\Services;

use App\Models\Genre;

class GenreService
{
    public function getAll()
    {
        return \Illuminate\Support\Facades\Cache::remember('genres:all', 3600, function () {
            return Genre::orderBy('genre_name')->get();
        });
    }
}
