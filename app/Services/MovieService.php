<?php

namespace App\Services;

use App\Models\Movie;

class MovieService
{
    public function getList(array $filters)
    {
        $query = Movie::with('genres');

        if (!empty($filters['status'])) {
            $status = strtolower($filters['status']);
            if ($status === 'coming_soon' || $status === 'upcoming') {
                $query->whereIn('status', ['upcoming', 'coming_soon']);
            } else {
                $query->where('status', $status);
            }
        }

        if (!empty($filters['category'])) {
            $category = strtolower($filters['category']);
            if ($category === 'now-showing' || $category === 'now_showing') {
                $query->where('status', 'now_showing');
            } elseif ($category === 'coming-soon' || $category === 'coming_soon' || $category === 'upcoming') {
                $query->whereIn('status', ['upcoming', 'coming_soon']);
            }
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['genre_id'])) {
            $query->whereHas('genres', function ($q) use ($filters) {
                $q->where('genres.genre_id', $filters['genre_id']);
            });
        }

        $perPage = $filters['limit'] ?? ($filters['per_page'] ?? 20);
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getTrending(int $perPage = 20)
    {
        return Movie::with('genres')
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function getPopular(int $perPage = 20)
    {
        return Movie::with('genres')
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function getNavbar()
    {
        $nowShowing = Movie::with('genres')
            ->where('status', 'now_showing')
            ->orderByDesc('popularity')
            ->limit(4)
            ->get();

        if ($nowShowing->isEmpty()) {
            $nowShowing = Movie::with('genres')
                ->orderByDesc('popularity')
                ->limit(4)
                ->get();
        }

        $comingSoon = Movie::with('genres')
            ->where('status', 'coming_soon')
            ->orderByDesc('popularity')
            ->limit(4)
            ->get();

        if ($comingSoon->isEmpty()) {
            $comingSoon = Movie::with('genres')
                ->orderByDesc('created_at')
                ->skip(4)
                ->limit(4)
                ->get();
        }

        $trending = Movie::with('genres')
            ->orderByDesc('popularity')
            ->limit(4)
            ->get();

        return [
            'now_showing' => $nowShowing,
            'coming_soon' => $comingSoon,
            'trending'    => $trending,
        ];
    }

    public function search(string $keyword, int $perPage = 20)
    {
        return Movie::with('genres')
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                  ->orWhere('original_title', 'like', '%' . $keyword . '%');
            })
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function getDetailBySlug(string $slug)
    {
        return Movie::with(['genres', 'castCredits.person', 'crewCredits.person', 'videos'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function getDetail(int $id)
    {
        return Movie::with(['genres', 'castCredits.person', 'crewCredits.person', 'videos'])
            ->findOrFail($id);
    }


    public function getSimilar(int $movieId, int $perPage = 20)
    {
        $movie = Movie::with('genres')->findOrFail($movieId);
        $genreIds = $movie->genres->pluck('genre_id')->toArray();

        return Movie::with('genres')
            ->withCount(['genres as matching_genres_count' => function ($q) use ($genreIds) {
                $q->whereIn('genres.genre_id', $genreIds);
            }])
            ->having('matching_genres_count', '>', 0)
            ->where('movie_id', '!=', $movieId)
            ->orderByDesc('matching_genres_count')
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function getSimilarBySlug(string $slug, int $perPage = 20)
    {
        $movie = Movie::with('genres')->where('slug', $slug)->firstOrFail();
        $genreIds = $movie->genres->pluck('genre_id')->toArray();

        return Movie::with('genres')
            ->withCount(['genres as matching_genres_count' => function ($q) use ($genreIds) {
                $q->whereIn('genres.genre_id', $genreIds);
            }])
            ->having('matching_genres_count', '>', 0)
            ->where('movie_id', '!=', $movie->movie_id)
            ->orderByDesc('matching_genres_count')
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }
}
