<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Credit;

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

        // Cast
        $cast = Credit::with('person')
            ->where('movie_id', $id)
            ->where('credit_type', 'cast')
            ->orderBy('order')
            ->get()
            ->map(fn($c) => [
                'id'         => $c->person->person_id,
                'name'       => $c->person->name,
                'character'  => $c->character_name,
                'profileUrl' => $c->person->profile_path,
                'order'      => $c->order,
            ])->values();

        // Crew
        $crew = Credit::with('person')
            ->where('movie_id', $id)
            ->where('credit_type', 'crew')
            ->get()
            ->map(fn($c) => [
                'id'         => $c->person->person_id,
                'name'       => $c->person->name,
                'job'        => $c->job,
                'department' => $c->department,
                'profileUrl' => $c->person->profile_path,
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
