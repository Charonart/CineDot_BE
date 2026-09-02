<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\FilterableAndSortable;

class Movie extends Model
{
    use HasFactory, FilterableAndSortable;


    protected $table = 'movies';
    protected $primaryKey = 'movie_id';

    protected $fillable = [
        'slug',
        'title',
        'original_title',
        'overview',
        'release_date',
        'original_language',
        'popularity',
        'vote_average',
        'vote_count',
        'imdb_id',
        'tmdb_id',
        'backdrop_path',
        'poster_path',
        'duration',
        'age_rating',
        'status',
    ];

    protected $casts = [
        'popularity'   => 'decimal:3',
        'vote_average' => 'float',
        'vote_count'   => 'integer',
        'tmdb_id'      => 'integer',
        'duration'     => 'integer',
        'release_date' => 'string',
    ];

    protected $appends = ['imdb_url', 'age_info'];

    public function getImdbUrlAttribute(): ?string
    {
        return $this->imdb_id ? "https://www.imdb.com/title/{$this->imdb_id}" : null;
    }

    public function getAgeRatingAttribute($value): string
    {
        return !empty($value) ? strtoupper(trim($value)) : 'P';
    }

    public function getAdultAttribute(): bool
    {
        return $this->age_rating === 'T18';
    }

    public function getAgeInfoAttribute(): array
    {
        $code = $this->age_rating;
        return match ($code) {
            'K' => [
                'code'        => 'K',
                'label'       => 'Dưới 13 tuổi có người giám hộ',
                'short_label' => 'K - Dưới 13T có GH',
                'description' => 'Phim được phép phổ biến đến người xem dưới 13 tuổi với điều kiện xem cùng cha, mẹ hoặc người giám hộ.',
                'min_age'     => 0,
                'color'       => '#3b82f6',
                'badge_class' => 'bg-blue-600 text-white',
            ],
            'T13' => [
                'code'        => 'T13',
                'label'       => 'Khán giả từ đủ 13 tuổi (13+)',
                'short_label' => 'T13 - Từ 13 tuổi',
                'description' => 'Phim được phép phổ biến đến người xem từ đủ 13 tuổi trở lên. Rạp có thể kiểm tra giấy tờ tùy thân.',
                'min_age'     => 13,
                'color'       => '#f59e0b',
                'badge_class' => 'bg-amber-500 text-white',
            ],
            'T16' => [
                'code'        => 'T16',
                'label'       => 'Khán giả từ đủ 16 tuổi (16+)',
                'short_label' => 'T16 - Từ 16 tuổi',
                'description' => 'Phim được phép phổ biến đến người xem từ đủ 16 tuổi trở lên. Vui lòng mang giấy tờ tùy thân có ảnh.',
                'min_age'     => 16,
                'color'       => '#f97316',
                'badge_class' => 'bg-orange-500 text-white',
            ],
            'T18' => [
                'code'        => 'T18',
                'label'       => 'Khán giả từ đủ 18 tuổi (18+)',
                'short_label' => 'T18 - Từ 18 tuổi',
                'description' => 'Phim cấm khán giả dưới 18 tuổi. Khán giả bắt buộc phải xuất trình CCCD/giấy tờ tùy thân có ảnh khi vào rạp.',
                'min_age'     => 18,
                'color'       => '#ef4444',
                'badge_class' => 'bg-rose-600 text-white',
            ],
            default => [
                'code'        => 'P',
                'label'       => 'Mọi lứa tuổi (Phổ biến)',
                'short_label' => 'P - Mọi lứa tuổi',
                'description' => 'Phim được phép phổ biến rộng rãi đến người xem ở mọi độ tuổi.',
                'min_age'     => 0,
                'color'       => '#22c55e',
                'badge_class' => 'bg-emerald-600 text-white',
            ],
        };
    }

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
