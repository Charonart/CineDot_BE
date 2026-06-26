<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointHistory;
use App\Http\Resources\PointHistoryResource;
use Illuminate\Http\Request;

class PointHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = $request->get('limit', 15);

        $history = PointHistory::with('booking')
            ->where('user_id', $user->user_id)
            ->orderBy('id', 'desc')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => [
                'currentPoints' => $user->point,
                'page'          => $history->currentPage(),
                'results'       => PointHistoryResource::collection($history->items()),
                'totalPages'    => $history->lastPage(),
                'totalResults'  => $history->total(),
            ]
        ]);
    }
}
