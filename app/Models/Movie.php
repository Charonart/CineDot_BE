<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'overview',
        'poster_url',
        'backdrop_url',
        'trailer_url',
        'status',
        'runtime',
        'release_date',
        'rating',
        'vote_count',
    ];

    protected $casts = [
        'rating'       => 'float',
        'vote_count'   => 'integer',
        'runtime'      => 'integer',
        'release_date' => 'string',
    ];

    /**
     * Trả về dữ liệu dạng camelCase cho FE
     */
    public function toArray()
    {
        $array = parent::toArray();

        return [
            'id'          => $array['id'],
            'title'       => $array['title'],
            'overview'    => $array['overview'],
            'posterUrl'   => $array['poster_url'],
            'backdropUrl' => $array['backdrop_url'],
            'releaseDate' => $array['release_date'],
            'rating'      => $array['rating'],
            'voteCount'   => $array['vote_count'],
            'runtime'     => $array['runtime'],
            'genres'      => $this->relationLoaded('genres')
                ? $this->genres->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->values()
                : [],
        ];
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genre');
    }
}
