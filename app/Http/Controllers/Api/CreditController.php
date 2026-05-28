<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\Movie;
use Illuminate\Support\Facades\Cache;

class CreditController extends Controller
{
    /**
     * GET /api/movies/{id}/credits
     * Trả về cast & crew của 1 bộ phim.
     * Response format khớp với movie-credits.json từ FE.
     */
    public function show($id)
    {
        // Kiểm tra phim tồn tại (cache lâu vì ít thay đổi)
        $movieExists = Cache::remember("movie.exists.{$id}", now()->addHours(1), function () use ($id) {
            return Movie::where('id', $id)->exists();
        });

        if (!$movieExists) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phim',
            ], 404);
        }

        $data = Cache::remember("movie.credits.{$id}", now()->addHours(1), function () use ($id) {
            // FIX: 1 query duy nhất thay vì 2 query riêng cho cast và crew
            $credits = Credit::with('person')
                ->where('movie_id', $id)
                ->orderBy('credit_type') // cast < crew theo alphabet
                ->orderBy('order')
                ->get();

            $cast = $credits->where('credit_type', 'cast')->map(fn($c) => [
                'id'         => $c->person->person_id,
                'name'       => $c->person->name,
                'character'  => $c->character_name,
                'profileUrl' => $c->person->profile_path,
                'order'      => $c->order,
            ])->values();

            $crew = $credits->where('credit_type', 'crew')->map(fn($c) => [
                'id'         => $c->person->person_id,
                'name'       => $c->person->name,
                'job'        => $c->job,
                'department' => $c->department,
                'profileUrl' => $c->person->profile_path,
            ])->values();

            return ['cast' => $cast, 'crew' => $crew];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
