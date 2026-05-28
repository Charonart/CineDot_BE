<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'adult'            => 'boolean',
        'video'            => 'boolean',
        'popularity'       => 'decimal:3',
        'duration_minutes' => 'integer',
        'release_date'     => 'string',
    ];

    /**
     * Trả về dữ liệu dạng camelCase cho FE – giữ nguyên response format cũ
     */
    public function toArray()
    {
        $array = parent::toArray();

        return [
            'id'               => $array['id'],
            'title'            => $array['title'],
            'overview'         => $array['overview'] ?? null,
            'posterUrl'        => $array['poster_path'] ?? null,
            'backdropUrl'      => $array['backdrop_path'] ?? null,
            'releaseDate'      => $array['release_date'] ?? null,
            'runtime'          => $array['duration_minutes'] ?? null,
            'rating'           => isset($array['reviews_avg_rating'])
                                    ? round((float) $array['reviews_avg_rating'], 1)
                                    : null,
            'voteCount'        => $array['reviews_count'] ?? 0,
            'genres'           => $this->relationLoaded('genres')
                ? $this->genres->map(fn($g) => ['id' => $g->genre_id, 'name' => $g->genre_name])->values()
                : [],
        ];
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genres', 'movie_id', 'genre_id');
    }

    /** Credits (all) */
    public function credits()
    {
        return $this->hasMany(Credit::class, 'movie_id', 'id');
    }

    /** Danh sách diễn viên (cast) qua credits table */
    public function castCredits()
    {
        return $this->hasMany(Credit::class, 'movie_id', 'id')
                    ->where('credit_type', 'cast')
                    ->orderBy('order');
    }

    /** Danh sách crew qua credits table */
    public function crewCredits()
    {
        return $this->hasMany(Credit::class, 'movie_id', 'id')
                    ->where('credit_type', 'crew');
    }

    /** Reviews */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'movie_id', 'id');
    }

    /** Videos */
    public function videos()
    {
        return $this->hasMany(Video::class, 'movie_id', 'id');
    }

    /** Lịch chiếu */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'movie_id', 'id');
    }
}
