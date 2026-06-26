<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Http\Resources\AdminReviewResource;
use Illuminate\Http\Request;

class MovieReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $query = Review::with(['user', 'movie']);

        if ($request->has('movie_id')) {
            $query->where('movie_id', $request->movie_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('fullname', 'ilike', '%' . $search . '%')
                  ->orWhere('email', 'ilike', '%' . $search . '%');
            });
        }

        $reviews = $query->orderBy('created_at', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $reviews->currentPage(),
                'results'      => AdminReviewResource::collection($reviews->items()),
                'totalPages'   => $reviews->lastPage(),
                'totalResults' => $reviews->total(),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa đánh giá thành công.'
        ]);
    }
}
