<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditResource;
use App\Models\Credit;
use App\Models\Movie;

class CreditController extends Controller
{
    public function show($id)
    {
        // Fail quickly if movie doesn't exist
        Movie::findOrFail($id);

        $credits = Credit::with('person')
            ->where('movie_id', $id)
            ->orderBy('credit_type')
            ->orderBy('order')
            ->get();

        $cast = $credits->where('credit_type', 'cast')->values();
        $crew = $credits->where('credit_type', 'crew')->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'cast' => CreditResource::collection($cast),
                'crew' => CreditResource::collection($crew),
            ],
        ]);
    }
}
