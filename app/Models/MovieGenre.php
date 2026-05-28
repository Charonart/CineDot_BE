<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MovieGenre extends Pivot
{
    protected $table = 'movie_genres';
    protected $primaryKey = 'movie_genre_id';
    public $timestamps = false;
    public $incrementing = true;
}
