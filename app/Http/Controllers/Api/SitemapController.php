<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Cinema;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Unified Sitemap dataset for Next.js app/sitemap.ts
     */
    public function index()
    {
        $data = Cache::remember('seo:sitemap:all', 3600, function () {
            $movies = Movie::whereIn('status', ['now_showing', 'upcoming'])
                ->select(['movie_id', 'slug', 'title', 'status', 'updated_at'])
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn($m) => [
                    'slug'        => $m->slug,
                    'title'       => $m->title,
                    'status'      => $m->status,
                    'updated_at'  => $m->updated_at?->toISOString() ?? $m->updated_at,
                ]);

            $cinemas = Cinema::with('province')
                ->where('is_active', true)
                ->select(['cinema_id', 'province_id', 'slug', 'cinema_name', 'is_active', 'updated_at'])
                ->orderBy('cinema_name')
                ->get()
                ->map(fn($c) => [
                    'slug'        => $c->slug,
                    'name'        => $c->cinema_name,
                    'city'        => $c->province?->province_name,
                    'is_active'   => (bool) $c->is_active,
                    'updated_at'  => $c->updated_at?->toISOString() ?? $c->updated_at,
                ]);

            return [
                'movies'  => $movies,
                'cinemas' => $cinemas,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Public active movies list for sitemap
     */
    public function movies()
    {
        $movies = Cache::remember('seo:sitemap:movies', 3600, function () {
            return Movie::whereIn('status', ['now_showing', 'upcoming'])
                ->select(['movie_id', 'slug', 'title', 'status', 'updated_at'])
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn($m) => [
                    'slug'        => $m->slug,
                    'title'       => $m->title,
                    'status'      => $m->status,
                    'updated_at'  => $m->updated_at?->toISOString() ?? $m->updated_at,
                ]);
        });

        return response()->json([
            'success' => true,
            'data'    => $movies,
        ]);
    }

    /**
     * Public active cinemas list for sitemap
     */
    public function cinemas()
    {
        $cinemas = Cache::remember('seo:sitemap:cinemas', 3600, function () {
            return Cinema::with('province')
                ->where('is_active', true)
                ->select(['cinema_id', 'province_id', 'slug', 'cinema_name', 'is_active', 'updated_at'])
                ->orderBy('cinema_name')
                ->get()
                ->map(fn($c) => [
                    'slug'        => $c->slug,
                    'name'        => $c->cinema_name,
                    'city'        => $c->province?->province_name,
                    'is_active'   => (bool) $c->is_active,
                    'updated_at'  => $c->updated_at?->toISOString() ?? $c->updated_at,
                ]);
        });

        return response()->json([
            'success' => true,
            'data'    => $cinemas,
        ]);
    }
}
