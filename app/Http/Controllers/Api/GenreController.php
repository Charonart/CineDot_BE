<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GenreController extends Controller
{
    /**
     * GET /api/genres
     * Trả về toàn bộ danh sách thể loại phim.
     * Response format: { success, data: [ {id, name, slug} ] }
     */
    public function index()
    {
        // Cache lâu vì danh sách genre ít thay đổi
        $genres = Cache::remember('genres.all', now()->addHours(6), function () {
            return Genre::orderBy('genre_name')
                ->get()
                ->map(fn($g) => [
                    'id'   => $g->genre_id,
                    'name' => $g->genre_name,
                    'slug' => $g->slug,
                ]);
        });

        return response()->json([
            'success' => true,
            'data'    => $genres,
        ]);
    }

    /**
     * GET /api/genres/{id}/movies
     * Lấy danh sách phim thuộc 1 thể loại – có pagination.
     * Query params: ?per_page=20&page=1
     */
    public function movies($id, Request $request)
    {
        $perPage  = (int) $request->get('per_page', 20);
        $page     = (int) $request->get('page', 1);
        $cacheKey = "genre.{$id}.movies.page{$page}.perPage{$perPage}";

        // Kiểm tra genre tồn tại
        $genre = Cache::remember("genre.{$id}", now()->addHours(6), function () use ($id) {
            return Genre::select('genre_id', 'genre_name', 'slug')->find($id);
        });

        if (!$genre) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thể loại',
            ], 404);
        }

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($id, $perPage) {
            // FIX: dùng paginate thay vì load toàn bộ vào RAM
            $paginated = Movie::with('genres')
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->whereHas('genres', fn($q) => $q->where('genres.genre_id', $id))
                ->orderByDesc('reviews_avg_rating')
                ->paginate($perPage);

            return [
                'page'         => $paginated->currentPage(),
                'results'      => $paginated->items(),
                'totalPages'   => $paginated->lastPage(),
                'totalResults' => $paginated->total(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'genre'        => ['id' => $genre->genre_id, 'name' => $genre->genre_name, 'slug' => $genre->slug],
                'totalResults' => $data['totalResults'],
                'results'      => $data['results'],
            ],
        ]);
    }
}
