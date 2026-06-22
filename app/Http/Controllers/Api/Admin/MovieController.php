<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMovieRequest;
use App\Http\Requests\Admin\UpdateMovieRequest;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Movie::with('genres');

        if ($request->has('search')) {
            $query->where('title', 'ilike', '%' . $request->search . '%')
                  ->orWhere('original_title', 'ilike', '%' . $request->search . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $movies = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $movies
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMovieRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            
            // Auto generate slug
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['title']) . '-' . time();
            }

            $movie = Movie::create($data);

            if ($request->has('genre_ids')) {
                $movie->genres()->attach($request->genre_ids);
            }

            if ($request->has('trailer_url') && !empty($request->trailer_url)) {
                $movie->videos()->create([
                    'name' => 'Trailer',
                    'key' => $this->extractYoutubeKey($request->trailer_url),
                    'site' => 'YouTube',
                    'type' => 'Trailer',
                    'official' => true,
                    'published_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo phim thành công.',
                'data'    => $movie->load('genres', 'videos')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo phim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $movie = Movie::with(['genres', 'videos', 'castCredits.person', 'crewCredits.person'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $movie
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMovieRequest $request, string $id)
    {
        $movie = Movie::findOrFail($id);

        DB::beginTransaction();
        try {
            $data = $request->validated();

            if (isset($data['title']) && $data['title'] !== $movie->title) {
                $data['slug'] = Str::slug($data['title']) . '-' . time();
            }

            $movie->update($data);

            if ($request->has('genre_ids')) {
                $movie->genres()->sync($request->genre_ids);
            }

            if ($request->has('trailer_url')) {
                // Remove old trailer and add new one
                $movie->videos()->where('type', 'Trailer')->delete();
                if (!empty($request->trailer_url)) {
                    $movie->videos()->create([
                        'name' => 'Trailer',
                        'key' => $this->extractYoutubeKey($request->trailer_url),
                        'site' => 'YouTube',
                        'type' => 'Trailer',
                        'official' => true,
                        'published_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật phim thành công.',
                'data'    => $movie->load('genres', 'videos')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật phim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $movie = Movie::findOrFail($id);
        
        // Soft delete
        $movie->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa phim thành công.'
        ]);
    }

    /**
     * Helper to extract Youtube video key from URL
     */
    private function extractYoutubeKey($url)
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return isset($match[1]) ? $match[1] : null;
    }
}
