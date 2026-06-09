<?php

namespace App\Services;

use App\Models\Genre;

class GenreService
{
    public function getAll()
    {
        return Genre::orderBy('genre_name')->get();
    }
}
