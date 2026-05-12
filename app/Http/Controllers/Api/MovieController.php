<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    /**
     * Lấy danh sách tất cả các phim kèm theo thể loại
     */
    public function index()
    {
        $movies = Movie::with('genres')->get();

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
