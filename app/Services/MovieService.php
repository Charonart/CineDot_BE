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

        if (!empty($filters['category'])) {
            $category = $filters['category'];
            if ($category === 'now-showing') {
                $query->where('status', 'now_showing');
            } elseif ($category === 'coming-soon') {
                $query->where('status', 'coming_soon');
            }
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'ilike', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['genre_id'])) {
            $query->whereHas('genres', function ($q) use ($filters) {
                $q->where('genres.genre_id', $filters['genre_id']);
            });
        }

        $perPage = $filters['limit'] ?? ($filters['per_page'] ?? 20);
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

    public function getNavbar()
    {
        $nowShowing = Movie::where('status', 'now_showing')
            ->orderByDesc('popularity')
            ->limit(5)
            ->get();

        $trending = Movie::withAvg('reviews', 'rating')
            ->orderByDesc('reviews_avg_rating')
            ->limit(5)
            ->get();

        return [
            'now_showing' => $nowShowing,
            'trending'    => $trending,
        ];
    }

    public function search(string $keyword, int $perPage = 20)
    {
        return Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'ilike', '%' . $keyword . '%')
                  ->orWhere('original_title', 'ilike', '%' . $keyword . '%');
            })
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function getDetailBySlug(string $slug)
    {
        return Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('slug', $slug)
            ->firstOrFail();
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

    public function getSimilarBySlug(string $slug, int $perPage = 20)
    {
        $movie = Movie::with('genres')->where('slug', $slug)->firstOrFail();
        $genreIds = $movie->genres->pluck('genre_id')->toArray();

        return Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount(['genres as matching_genres_count' => function ($q) use ($genreIds) {
                $q->whereIn('genres.genre_id', $genreIds);
            }])
            ->having('matching_genres_count', '>', 0)
            ->where('id', '!=', $movie->id)
            ->orderByDesc('matching_genres_count')
            ->orderByDesc('reviews_avg_rating')
            ->paginate($perPage);
    }
}
