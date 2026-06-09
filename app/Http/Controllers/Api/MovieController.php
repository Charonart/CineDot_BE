<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetMoviesRequest;
use App\Http\Resources\MovieResource;
use App\Http\Resources\MovieDetailResource;
use App\Services\MovieService;

class MovieController extends Controller
{
    public function __construct(private MovieService $movieService)
    {
    }

    public function index(GetMoviesRequest $request)
    {
        $paginated = $this->movieService->getList($request->validated());

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $paginated->currentPage(),
                'results'      => MovieResource::collection($paginated->items()),
                'totalPages'   => $paginated->lastPage(),
                'totalResults' => $paginated->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $movie = $this->movieService->getDetail($id);

        return response()->json([
            'success' => true,
            'data'    => new MovieDetailResource($movie),
        ]);
    }

    public function trending(GetMoviesRequest $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        $paginated = $this->movieService->getTrending($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $paginated->currentPage(),
                'results'      => MovieResource::collection($paginated->items()),
                'totalPages'   => $paginated->lastPage(),
                'totalResults' => $paginated->total(),
            ],
        ]);
    }

    public function popular(GetMoviesRequest $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        $paginated = $this->movieService->getPopular($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $paginated->currentPage(),
                'results'      => MovieResource::collection($paginated->items()),
                'totalPages'   => $paginated->lastPage(),
                'totalResults' => $paginated->total(),
            ],
        ]);
    }

    public function similar(GetMoviesRequest $request, $id)
    {
        $perPage = (int) $request->get('per_page', 20);
        $paginated = $this->movieService->getSimilar($id, $perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $paginated->currentPage(),
                'results'      => MovieResource::collection($paginated->items()),
                'totalPages'   => $paginated->lastPage(),
                'totalResults' => $paginated->total(),
            ],
        ]);
    }

    public function videos($id)
    {
        $movie = \App\Models\Movie::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => \App\Http\Resources\VideoResource::collection($movie->videos),
        ]);
    }
}
