<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;

class GenreController extends Controller
{
    /**
     * GET /api/genres
     * Trả về toàn bộ danh sách thể loại phim.
     * Response format: { success, data: [ {id, name, slug} ] }
     */
    public function index()
    {
        $genres = Genre::orderBy('name')->get(['id', 'name', 'slug']);

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
            $q->with('genres')->orderByDesc('rating');
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
                'genre'        => ['id' => $genre->id, 'name' => $genre->name, 'slug' => $genre->slug],
                'totalResults' => $genre->movies->count(),
                'results'      => $genre->movies->values(),
            ],
        ]);
    }
}
