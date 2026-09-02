<?php

namespace App\Services;

use App\Models\Movie;

class MovieService
{
    public function getList(array $filters)
    {
        $query = Movie::with(['genres', 'videos']);

        if (!empty($filters['status'])) {
            $status = strtolower($filters['status']);
            if ($status === 'coming_soon' || $status === 'upcoming' || $status === 'coming-soon') {
                $query->whereIn('status', ['upcoming', 'coming_soon']);
            } elseif ($status === 'now_showing' || $status === 'now-showing') {
                $query->where('status', 'now_showing');
            } elseif ($status === 'ended' || $status === 'stopped') {
                $query->where('status', 'ended');
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

        if (!empty($filters['sort'])) {
            if ($filters['sort'] === 'top_rated' || $filters['sort'] === 'rating' || $filters['sort'] === 'vote_average') {
                $query->orderByDesc('vote_average');
            } elseif ($filters['sort'] === 'popularity') {
                $query->orderByDesc('popularity');
            } elseif ($filters['sort'] === 'release_date') {
                $query->orderByDesc('release_date');
            } else {
                $query->orderByDesc('created_at');
            }
        } else {
            $query->orderByDesc('created_at');
        }

        $perPage = $filters['limit'] ?? ($filters['per_page'] ?? 20);
        return $query->paginate($perPage);
    }

    public function getTrending(int $perPage = 20)
    {
        return Movie::with(['genres', 'videos'])
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function getPopular(int $perPage = 20)
    {
        return Movie::with(['genres', 'videos'])
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function getNavbar()
    {
        return \Illuminate\Support\Facades\Cache::remember('movies:navbar', 600, function () {
            $nowShowing = Movie::with(['genres', 'videos'])
                ->where('status', 'now_showing')
                ->orderByDesc('popularity')
                ->limit(4)
                ->get();

            if ($nowShowing->isEmpty()) {
                $nowShowing = Movie::with(['genres', 'videos'])
                    ->orderByDesc('popularity')
                    ->limit(4)
                    ->get();
            }

            $comingSoon = Movie::with(['genres', 'videos'])
                ->where('status', 'upcoming')
                ->orderByDesc('popularity')
                ->limit(4)
                ->get();

            if ($comingSoon->isEmpty()) {
                $comingSoon = Movie::with(['genres', 'videos'])
                    ->orderByDesc('created_at')
                    ->skip(4)
                    ->limit(4)
                    ->get();
            }

            $trending = Movie::with(['genres', 'videos'])
                ->orderByDesc('popularity')
                ->limit(4)
                ->get();

            return [
                'now_showing' => $nowShowing,
                'coming_soon' => $comingSoon,
                'trending'    => $trending,
            ];
        });
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

        if (empty($genreIds)) {
            return Movie::with(['genres', 'videos'])
                ->where('movie_id', '!=', $movieId)
                ->orderByDesc('popularity')
                ->paginate($perPage);
        }

        return Movie::with(['genres', 'videos'])
            ->whereHas('genres', function ($q) use ($genreIds) {
                $q->whereIn('genres.genre_id', $genreIds);
            })
            ->where('movie_id', '!=', $movieId)
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function getSimilarBySlug(string $slug, int $perPage = 20)
    {
        $movie = Movie::with('genres')->where('slug', $slug)->firstOrFail();
        return $this->getSimilar($movie->movie_id, $perPage);
    }
}
