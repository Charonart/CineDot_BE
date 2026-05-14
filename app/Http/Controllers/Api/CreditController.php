<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;

class CreditController extends Controller
{
    /**
     * GET /api/movies/{id}/credits
     * Trả về cast & crew của 1 bộ phim.
     * Response format khớp với movie-credits.json từ FE.
     */
    public function show($id)
    {
        $movie = Movie::find($id);

        if (!$movie) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phim',
            ], 404);
        }

        $cast = $movie->cast()->get()->map(fn($p) => [
            'id'         => $p->id,
            'name'       => $p->name,
            'character'  => $p->pivot->character,
            'profileUrl' => $p->profile_url,
            'order'      => $p->pivot->order,
        ])->values();

        $crew = $movie->crew()->get()->map(fn($p) => [
            'id'         => $p->id,
            'name'       => $p->name,
            'job'        => $p->pivot->job,
            'department' => $p->pivot->department,
            'profileUrl' => $p->profile_url,
        ])->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'cast' => $cast,
                'crew' => $crew,
            ],
        ]);
    }
}
