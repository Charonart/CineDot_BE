<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetMoviesRequest;
use App\Http\Resources\GenreResource;
use App\Http\Resources\MovieResource;
use App\Services\GenreService;
use App\Models\Movie;
use App\Models\Genre;

class GenreController extends Controller
{
    public function __construct(private GenreService $genreService)
    {
    }

    public function index()
    {
        $genres = $this->genreService->getAll();

        return response()->json([
            'success' => true,
            'data'    => GenreResource::collection($genres),
        ]);
    }

    public function movies($id, GetMoviesRequest $request)
    {
        $genre = Genre::findOrFail($id);
        
        $perPage = (int) $request->get('per_page', 20);
        $paginated = Movie::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->whereHas('genres', fn($q) => $q->where('genres.genre_id', $id))
            ->orderByDesc('reviews_avg_rating')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'genre'        => new GenreResource($genre),
                'totalResults' => $paginated->total(),
                'results'      => MovieResource::collection($paginated->items()),
            ],
        ]);
    }
}
