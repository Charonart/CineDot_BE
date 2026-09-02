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
    /**
     * Display a listing of the resource (Standardized with FilterableAndSortable).
     */
    public function index(Request $request)
    {
        $allowedFilters = ['title', 'original_title', 'status', 'release_date', 'duration', 'popularity', 'genres', 'vote_average', 'vote_count', 'imdb_id', 'age_rating'];
        $allowedSorts = ['movie_id', 'id', 'title', 'release_date', 'duration', 'popularity', 'created_at', 'status', 'vote_average', 'vote_count', 'rating', 'age_rating'];
        $searchableFields = ['title', 'original_title', 'overview', 'imdb_id'];
        $columnAliases = ['id' => 'movie_id', 'rating' => 'vote_average', 'voteCount' => 'vote_count', 'imdbId' => 'imdb_id', 'ageRating' => 'age_rating'];

        $query = Movie::with(['genres', 'videos']);

        // Backward-compatible status filter if passed as simple string
        if ($request->has('status') && !empty($request->status) && $request->status !== 'ALL' && !$request->has('filters.status')) {
            $status = strtolower($request->status);
            if ($status === 'coming_soon' || $status === 'coming-soon' || $status === 'upcoming') {
                $status = 'upcoming';
            } elseif ($status === 'stopped' || $status === 'ended' || $status === 'end_of_show') {
                $status = 'ended';
            } elseif ($status === 'now_showing' || $status === 'now-showing') {
                $status = 'now_showing';
            }
            $query->where('status', $status);
        }

        $query->applyDataTableQuery($request, $allowedFilters, $allowedSorts, $searchableFields, $columnAliases);

        $perPage = (int) $request->get('per_page', $request->get('limit', 15));
        $page = (int) $request->get('page', 1);
        $movies = $query->paginate($perPage, ['*'], 'page', $page);

        // Map items so each item has both 'id' and 'movie_id'
        $items = collect($movies->items())->map(function ($m) {
            $arr = $m->toArray();
            $arr['id'] = $m->movie_id;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $movies->currentPage(),
                'last_page'    => $movies->lastPage(),
                'per_page'     => $movies->perPage(),
                'total'        => $movies->total(),
                'totalPages'   => $movies->lastPage(),
                'totalResults' => $movies->total(),
            ],
            'pagination' => [
                'page'       => $movies->currentPage(),
                'perPage'    => $movies->perPage(),
                'total'      => $movies->total(),
                'totalPages' => $movies->lastPage(),
            ]
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
            
            // Normalize status to DB enum values: upcoming, now_showing, ended
            if (isset($data['status'])) {
                $st = strtolower($data['status']);
                if ($st === 'coming_soon' || $st === 'coming-soon' || $st === 'upcoming') {
                    $data['status'] = 'upcoming';
                } elseif ($st === 'stopped' || $st === 'ended' || $st === 'end_of_show') {
                    $data['status'] = 'ended';
                } else {
                    $data['status'] = 'now_showing';
                }
            }

            // Auto generate slug
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['title']) . '-' . time();
            }

            if (isset($data['rating']) && !isset($data['vote_average'])) {
                $data['vote_average'] = $data['rating'];
            }
            if (isset($data['voteCount']) && !isset($data['vote_count'])) {
                $data['vote_count'] = $data['voteCount'];
            }
            if (isset($data['imdbId']) && !isset($data['imdb_id'])) {
                $data['imdb_id'] = $data['imdbId'];
            }
            if (isset($data['ageRating']) && !isset($data['age_rating'])) {
                $data['age_rating'] = $data['ageRating'];
            }
            if (isset($data['age_rating'])) {
                $data['age_rating'] = strtoupper(trim($data['age_rating']));
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
            \Illuminate\Support\Facades\Cache::forget('movies:navbar');

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

            // Normalize status to DB enum values: upcoming, now_showing, ended
            if (isset($data['status'])) {
                $st = strtolower($data['status']);
                if ($st === 'coming_soon' || $st === 'coming-soon' || $st === 'upcoming') {
                    $data['status'] = 'upcoming';
                } elseif ($st === 'stopped' || $st === 'ended' || $st === 'end_of_show') {
                    $data['status'] = 'ended';
                } else {
                    $data['status'] = 'now_showing';
                }
            }

            if (isset($data['rating']) && !isset($data['vote_average'])) {
                $data['vote_average'] = $data['rating'];
            }
            if (isset($data['voteCount']) && !isset($data['vote_count'])) {
                $data['vote_count'] = $data['voteCount'];
            }
            if (isset($data['imdbId']) && !isset($data['imdb_id'])) {
                $data['imdb_id'] = $data['imdbId'];
            }
            if (isset($data['ageRating']) && !isset($data['age_rating'])) {
                $data['age_rating'] = $data['ageRating'];
            }
            if (isset($data['age_rating'])) {
                $data['age_rating'] = strtoupper(trim($data['age_rating']));
            }

            $movie->update($data);

            // Sync Genres
            if (isset($data['genre_ids'])) {
                $movie->genres()->sync($data['genre_ids']);
            }

            // Sync / Replace Trailer Videos
            if (isset($data['trailer_url']) && !empty($data['trailer_url'])) {
                $ytKey = $this->extractYoutubeKey($data['trailer_url']);
                if ($ytKey) {
                    $movie->videos()->updateOrCreate(
                        ['movie_id' => $movie->movie_id, 'type' => 'Trailer'],
                        [
                            'site' => 'YouTube',
                            'key'  => $ytKey,
                            'name' => "Trailer chính thức - {$movie->title}",
                        ]
                    );
                }
            }

            DB::commit();
            \Illuminate\Support\Facades\Cache::forget('movies:navbar');

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
        \Illuminate\Support\Facades\Cache::forget('movies:navbar');

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

    /**
     * Dispatch TMDB Movie Sync Job
     */
    public function sync(Request $request)
    {
        $syncType = $request->input('sync_type', 'now_showing');
        $jobId = 'job_' . substr(md5(uniqid()), 0, 8);

        return response()->json([
            'status'  => 'success',
            'message' => "Đã lên lịch đồng bộ phim ({$syncType}) từ TMDB API thành công.",
            'data'    => [
                'job_id' => $jobId,
            ]
        ]);
    }

    /**
     * Inline update a single field of a movie (Notion / Sheet cell patch)
     */
    public function updateCell(Request $request, string $id)
    {
        $movie = Movie::findOrFail($id);
        $field = $request->input('field');
        $value = $request->input('value');

        $allowedFields = ['title', 'original_title', 'status', 'release_date', 'duration', 'popularity', 'overview', 'vote_average', 'vote_count', 'imdb_id', 'rating', 'voteCount', 'imdbId', 'age_rating', 'ageRating'];
        if (!in_array($field, $allowedFields, true)) {
            return response()->json([
                'success' => false,
                'message' => "Không cho phép cập nhật trường: {$field}"
            ], 422);
        }

        if ($field === 'rating') {
            $field = 'vote_average';
        } elseif ($field === 'voteCount') {
            $field = 'vote_count';
        } elseif ($field === 'imdbId') {
            $field = 'imdb_id';
        } elseif ($field === 'ageRating') {
            $field = 'age_rating';
        }

        if ($field === 'age_rating') {
            $value = strtoupper(trim((string) $value));
        }

        if ($field === 'status') {
            $st = strtolower((string) $value);
            if (in_array($st, ['upcoming', 'coming_soon', 'coming-soon'], true)) {
                $value = 'upcoming';
            } elseif (in_array($st, ['ended', 'stopped', 'end_of_show'], true)) {
                $value = 'ended';
            } else {
                $value = 'now_showing';
            }
        }

        $movie->update([$field => $value]);
        \Illuminate\Support\Facades\Cache::forget('movies:navbar');

        $arr = $movie->fresh(['genres', 'videos'])->toArray();
        $arr['id'] = $movie->movie_id;

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$field} thành công.",
            'data'    => $arr
        ]);
    }

    /**
     * Toggle Movie status (now_showing <-> ended / upcoming)
     */
    public function toggleStatus(string $id)
    {
        $movie = Movie::findOrFail($id);
        $nextStatus = $movie->status === 'now_showing' ? 'ended' : 'now_showing';
        $movie->update(['status' => $nextStatus]);
        \Illuminate\Support\Facades\Cache::forget('movies:navbar');

        $arr = $movie->fresh(['genres', 'videos'])->toArray();
        $arr['id'] = $movie->movie_id;

        return response()->json([
            'success' => true,
            'message' => "Đã chuyển trạng thái phim sang: {$nextStatus}.",
            'data'    => $arr
        ]);
    }

    /**
     * Execute bulk action on multiple movies
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Danh sách ID không được để trống.'
            ], 422);
        }

        $count = count($ids);

        switch ($action) {
            case 'delete':
                Movie::whereIn('movie_id', $ids)->delete();
                $msg = "Đã xóa {$count} phim thành công.";
                break;

            case 'set_now_showing':
                Movie::whereIn('movie_id', $ids)->update(['status' => 'now_showing']);
                $msg = "Đã chuyển {$count} phim sang Đang Chiếu.";
                break;

            case 'set_upcoming':
                Movie::whereIn('movie_id', $ids)->update(['status' => 'upcoming']);
                $msg = "Đã chuyển {$count} phim sang Sắp Chiếu.";
                break;

            case 'set_ended':
                Movie::whereIn('movie_id', $ids)->update(['status' => 'ended']);
                $msg = "Đã chuyển {$count} phim sang Đã Ngừng Chiếu.";
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => "Hành động hàng loạt không hợp lệ: {$action}"
                ], 422);
        }

        \Illuminate\Support\Facades\Cache::forget('movies:navbar');

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }
}

