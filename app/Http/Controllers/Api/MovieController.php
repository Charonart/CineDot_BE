<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{
    /**
     * GET /api/movies
     * Lấy danh sách phim với pagination, lọc theo status và search theo title.
     */
    public function index(Request $request)
    {
        $query = Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // 1. Lọc theo trạng thái (now_showing, coming_soon, ended)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 2. Tìm kiếm theo tên phim
        if ($request->filled('search')) {
            $query->where('title', 'ilike', '%' . $request->search . '%');
        }

        // 3. Lọc theo thể loại
        if ($request->filled('genre_id')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.genre_id', $request->genre_id);
            });
        }

        // 4. Pagination (mặc định 20 kết quả mỗi trang)
        $perPage     = (int) $request->get('per_page', 20);
        $paginated   = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $paginated->currentPage(),
                'results'      => $paginated->items(),
                'totalPages'   => $paginated->lastPage(),
                'totalResults' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/movies/{id}
     * Lấy chi tiết một bộ phim theo ID.
     */
    public function show($id)
    {
        $movie = Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->find($id);

        if (!$movie) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phim',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $movie,
        ]);
    }

    /**
     * GET /api/movies/trending
     * Phim trending (sắp xếp theo rating trung bình giảm dần).
     */
    public function trending(Request $request)
    {
        $perPage  = (int) $request->get('per_page', 20);
        $page     = (int) $request->get('page', 1);
        $cacheKey = "movies.trending.page{$page}.perPage{$perPage}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($perPage) {
            $paginated = Movie::with('genres')
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
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
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/movies/popular
     * Phim phổ biến (sắp xếp theo số lượng review giảm dần).
     */
    public function popular(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        $page    = (int) $request->get('page', 1);
        $cacheKey = "movies.popular.page{$page}.perPage{$perPage}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($perPage) {
            $paginated = Movie::with('genres')
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderByDesc('reviews_count')
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
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/movies/{id}/similar
     * Phim tương tự (cùng genre, trừ chính phim đó).
     */
    public function similar(Request $request, $id)
    {
        $movie = Movie::with('genres')->find($id);

        if (!$movie) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phim',
            ], 404);
        }

        $genreIds = $movie->genres->pluck('genre_id');
        $perPage  = (int) $request->get('per_page', 20);

        $paginated = Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('id', '!=', $id)
            ->whereHas('genres', fn($q) => $q->whereIn('genres.genre_id', $genreIds))
            ->orderByDesc('reviews_avg_rating')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $paginated->currentPage(),
                'results'      => $paginated->items(),
                'totalPages'   => $paginated->lastPage(),
                'totalResults' => $paginated->total(),
            ],
        ]);
    }
}
