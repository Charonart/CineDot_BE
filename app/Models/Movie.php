<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;


    protected $table = 'movies';
    protected $primaryKey = 'movie_id';

    protected $fillable = [
        'slug',
        'title',
        'original_title',
        'overview',
        'release_date',
        'original_language',
        'adult',
        'video',
        'popularity',
        'backdrop_path',
        'poster_path',
        'duration',
        'status',
    ];

    protected $casts = [
        'adult'        => 'boolean',
        'video'        => 'boolean',
        'popularity'   => 'decimal:3',
        'duration'     => 'integer',
        'release_date' => 'string',
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genres', 'movie_id', 'genre_id');
    }

    /** Tất cả credits (cast + crew) */
    public function credits()
    {
        return $this->hasMany(Credit::class, 'movie_id', 'movie_id');
    }

    /** Danh sách diễn viên (cast) qua credits */
    public function castCredits()
    {
        return $this->hasMany(Credit::class, 'movie_id', 'movie_id')
                    ->where('credit_type', 'cast')
                    ->orderBy('order');
    }

    /** Danh sách crew qua credits */
    public function crewCredits()
    {
        return $this->hasMany(Credit::class, 'movie_id', 'movie_id')
                    ->where('credit_type', 'crew');
    }


    /** Video trailer */
    public function videos()
    {
        return $this->hasMany(Video::class, 'movie_id', 'movie_id');
    }

    /** Suất chiếu */
    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'movie_id', 'movie_id');
    }
}
