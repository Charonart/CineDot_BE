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
    public function index($movieId, Request $request)
    {
        Movie::findOrFail($movieId);
        $perPage = (int) $request->get('per_page', 20);
        
        $paginated = Review::with('user')
            ->where('movie_id', $movieId)
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
        Movie::findOrFail($movieId);
        $userId = $request->user()->user_id;

        $existing = Review::where('movie_id', $movieId)->where('user_id', $userId)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đánh giá bộ phim này rồi.'
            ], 400);
        }

        $review = Review::create([
            'movie_id' => $movieId,
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
