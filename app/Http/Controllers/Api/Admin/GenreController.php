<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Http\Requests\Admin\StoreGenreRequest;
use App\Http\Requests\Admin\UpdateGenreRequest;
use App\Http\Resources\AdminGenreResource;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Genre::withCount('movies');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('genre_name', 'like', '%' . $search . '%');
        }

        $page = (int) $request->get('page', 1);
        $genres = $query->orderBy('genre_id', 'asc')->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $genres->currentPage(),
                'results'      => AdminGenreResource::collection($genres->items()),
                'totalPages'   => $genres->lastPage(),
                'totalResults' => $genres->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGenreRequest $request)
    {
        $genre = Genre::create($request->validated());
        \Illuminate\Support\Facades\Cache::forget('genres:all');

        return response()->json([
            'success' => true,
            'message' => 'Tạo thể loại thành công.',
            'data'    => new AdminGenreResource($genre)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $genre = Genre::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new AdminGenreResource($genre)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGenreRequest $request, string $id)
    {
        $genre = Genre::findOrFail($id);
        $genre->update($request->validated());
        \Illuminate\Support\Facades\Cache::forget('genres:all');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thể loại thành công.',
            'data'    => new AdminGenreResource($genre)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $genre = Genre::findOrFail($id);
        $genre->delete();
        \Illuminate\Support\Facades\Cache::forget('genres:all');

        return response()->json([
            'success' => true,
            'message' => 'Xóa thể loại thành công.'
        ]);
    }
}
