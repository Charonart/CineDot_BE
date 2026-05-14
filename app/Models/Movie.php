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
            'id'               => $array['id'],
            'title'            => $array['title'],
            'overview'         => $array['overview'],
            'posterUrl'        => $array['poster_url'],
            'backdropUrl'      => $array['backdrop_url'],
            'releaseDate'      => $array['release_date'],
            'rating'           => $array['rating'],
            'voteCount'        => $array['vote_count'],
            'runtime'          => $array['runtime'],
            'runtimeFormatted' => $this->formatRuntime($array['runtime']),
            'genres'           => $this->relationLoaded('genres')
                ? $this->genres->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->values()
                : [],
        ];
    }

    /**
     * Chuyển số phút thành dạng "Xh Yphút"
     * Ví dụ: 139 → "2h19phút", 120 → "2h0phút", 45 → "0h45phút"
     */
    private function formatRuntime(?int $minutes): ?string
    {
        if ($minutes === null || $minutes <= 0) return null;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h > 0 ? "{$h}h{$m}phút" : "{$m}phút";
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genre');
    }

    /** Danh sách diễn viên (cast) */
    public function cast()
    {
        return $this->belongsToMany(Person::class, 'movie_cast', 'movie_id', 'person_id')
                    ->withPivot('character', 'order')
                    ->orderBy('movie_cast.order');
    }

    /** Danh sách đoàn làm phim (crew) */
    public function crew()
    {
        return $this->belongsToMany(Person::class, 'movie_crew', 'movie_id', 'person_id')
                    ->withPivot('job', 'department');
    }
}
