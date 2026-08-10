<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\Request;
use App\Http\Resources\ReviewResource;
use App\Http\Requests\ReviewRequest;

class ReviewController extends Controller
{
    private function findMovie($identifier)
    {
        return is_numeric($identifier)
            ? Movie::find((int) $identifier)
            : Movie::where('slug', $identifier)->first();
    }

    public function index($movieId, Request $request)
    {
        $movie = $this->findMovie($movieId);
        if (!$movie) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bộ phim.'
            ], 404);
        }

        $perPage = (int) $request->get('per_page', 20);
        
        $paginated = Review::with('user')
            ->where('movie_id', $movie->movie_id)
            ->orderByDesc('review_id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $paginated->currentPage(),
                'results'      => ReviewResource::collection($paginated->items()),
                'totalPages'   => $paginated->lastPage(),
                'totalResults' => $paginated->total(),
            ]
        ]);
    }

    public function store($movieId, ReviewRequest $request)
    {
        $movie = $this->findMovie($movieId);
        if (!$movie) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bộ phim.'
            ], 404);
        }

        $userId = $request->user()->user_id;

        $existing = Review::where('movie_id', $movie->movie_id)->where('user_id', $userId)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đánh giá bộ phim này rồi.'
            ], 400);
        }

        $review = Review::create([
            'movie_id' => $movie->movie_id,
            'user_id'  => $userId,
            'rating'   => $request->rating,
            'comment'  => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cảm ơn bạn đã gửi đánh giá!',
            'data'    => new ReviewResource($review->load('user'))
        ], 201);
    }
}
