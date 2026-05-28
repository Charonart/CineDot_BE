<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Support\Str;

class GenreController extends Controller
{
    /**
     * GET /api/genres
     * Trả về toàn bộ danh sách thể loại phim.
     * Response format: { success, data: [ {id, name, slug} ] }
     */
    public function index()
    {
        $genres = Genre::orderBy('genre_name')->get()->map(fn($g) => [
            'id'   => $g->genre_id,
            'name' => $g->genre_name,
            'slug' => $g->slug,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $genres,
        ]);
    }

    /**
     * GET /api/genres/{id}/movies
     * Lấy danh sách phim thuộc 1 thể loại.
     */
    public function movies($id)
    {
        $genre = Genre::with(['movies' => function ($q) {
            $q->with('genres')
              ->withAvg('reviews', 'rating')
              ->withCount('reviews')
              ->orderByDesc('reviews_avg_rating');
        }])->find($id);

        if (!$genre) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thể loại',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'genre'        => ['id' => $genre->genre_id, 'name' => $genre->genre_name, 'slug' => $genre->slug],
                'totalResults' => $genre->movies->count(),
                'results'      => $genre->movies->values(),
            ],
        ]);
    }
}
