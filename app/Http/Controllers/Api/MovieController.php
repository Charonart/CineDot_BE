<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    /**
     * GET /api/movies
     * Lấy danh sách phim với pagination, lọc theo status và search theo title.
     * Response format khớp với movies-popular.json / movies-trending.json / movies-search.json từ FE.
     */
    public function index(Request $request)
    {
        $query = Movie::with('genres');

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
                $q->where('genres.id', $request->genre_id);
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
     * Response format khớp với movie-detail.json từ FE.
     */
    public function show($id)
    {
        $movie = Movie::with('genres')->find($id);

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
     * Phim trending (sắp xếp theo rating giảm dần).
     * Response format khớp với movies-trending.json từ FE.
     */
    public function trending(Request $request)
    {
        $perPage   = (int) $request->get('per_page', 20);
        $paginated = Movie::with('genres')
            ->orderByDesc('rating')
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

    /**
     * GET /api/movies/popular
     * Phim phổ biến (sắp xếp theo vote_count giảm dần).
     * Response format khớp với movies-popular.json từ FE.
     */
    public function popular(Request $request)
    {
        $perPage   = (int) $request->get('per_page', 20);
        $paginated = Movie::with('genres')
            ->orderByDesc('vote_count')
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
