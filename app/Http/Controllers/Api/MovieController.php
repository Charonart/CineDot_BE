<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    /**
     * Lấy danh sách tất cả các phim kèm theo thể loại (hỗ trợ lọc và tìm kiếm)
     */
    public function index(Request $request)
    {
        $query = Movie::with('genres');

        // 1. Lọc theo trạng thái phim (now_showing, coming_soon, ended)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 2. Tìm kiếm theo tên phim
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // 3. Lọc theo thể loại phim
        if ($request->has('genre_id')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre_id);
            });
        }

        $movies = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $movies
        ]);
    }

    /**
     * Lấy chi tiết một bộ phim theo ID
     */
    public function show($id)
    {
        $movie = Movie::with('genres')->find($id);

        if (!$movie) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy phim'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $movie
        ]);
    }
}
