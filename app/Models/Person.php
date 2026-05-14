<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'profile_url'];

    /** Phim mà người này đóng (cast) */
    public function castMovies()
    {
        return $this->belongsToMany(Movie::class, 'movie_cast', 'person_id', 'movie_id')
                    ->withPivot('character', 'order');
    }

    /** Phim mà người này làm crew */
    public function crewMovies()
    {
        return $this->belongsToMany(Movie::class, 'movie_crew', 'person_id', 'movie_id')
                    ->withPivot('job', 'department');
    }
}
