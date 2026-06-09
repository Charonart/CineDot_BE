<?php

namespace App\Services;

use App\Models\Movie;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MovieService
{
    public function getList(array $filters)
    {
        $query = Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'ilike', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['genre_id'])) {
            $query->whereHas('genres', function ($q) use ($filters) {
                $q->where('genres.genre_id', $filters['genre_id']);
            });
        }

        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    public function getTrending(int $perPage = 20)
    {
        return Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->paginate($perPage);
    }

    public function getPopular(int $perPage = 20)
    {
        return Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_count')
            ->paginate($perPage);
    }

    public function getDetail(int $id)
    {
        return Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->findOrFail($id);
    }

    public function getSimilar(int $movieId, int $perPage = 20)
    {
        $movie = Movie::with('genres')->findOrFail($movieId);
        $genreIds = $movie->genres->pluck('genre_id')->toArray();

        // Cải tiến: Đếm số lượng thể loại trùng, sắp xếp theo số lượng trùng giảm dần
        return Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount(['genres as matching_genres_count' => function ($q) use ($genreIds) {
                $q->whereIn('genres.genre_id', $genreIds);
            }])
            ->having('matching_genres_count', '>', 0)
            ->where('id', '!=', $movieId)
            ->orderByDesc('matching_genres_count')
            ->orderByDesc('reviews_avg_rating')
            ->paginate($perPage);
    }
}
