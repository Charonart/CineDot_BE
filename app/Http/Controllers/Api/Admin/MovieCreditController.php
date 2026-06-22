<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMovieCreditRequest;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieCreditController extends Controller
{
    /**
     * Danh sách Cast và Crew của 1 bộ phim
     */
    public function index($movieId)
    {
        $movie = Movie::findOrFail($movieId);
        
        $cast = $movie->castCredits()->with('person')->orderBy('order')->get();
        $crew = $movie->crewCredits()->with('person')->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'cast' => $cast,
                'crew' => $crew
            ]
        ]);
    }

    /**
     * Thêm người vào phim (Cast hoặc Crew)
     */
    public function store(StoreMovieCreditRequest $request, $movieId)
    {
        $movie = Movie::findOrFail($movieId);
        $data = $request->validated();

        $credit = clone $movie; // Just to make logic simpler

        if ($data['credit_type'] === 'cast') {
            $created = $movie->castCredits()->create([
                'person_id'      => $data['person_id'],
                'character_name' => $data['character_name'] ?? null,
                'order'          => $data['order'] ?? 0,
            ]);
        } else {
            $created = $movie->crewCredits()->create([
                'person_id'      => $data['person_id'],
                'job'            => $data['character_name'] ?? 'Director', // 'job' map character_name temporary if needed, wait, let's look at migration
                'order'          => $data['order'] ?? 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thêm nhân sự thành công.',
            'data'    => $created->load('person')
        ], 201);
    }

    /**
     * Xóa 1 người khỏi phim
     */
    public function destroy($movieId, $creditId, Request $request)
    {
        $movie = Movie::findOrFail($movieId);
        $type = $request->query('type', 'cast'); // expected 'cast' or 'crew'

        if ($type === 'cast') {
            $credit = $movie->castCredits()->findOrFail($creditId);
        } else {
            $credit = $movie->crewCredits()->findOrFail($creditId);
        }

        $credit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa nhân sự thành công.'
        ]);
    }
}
