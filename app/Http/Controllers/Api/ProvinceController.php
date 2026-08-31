<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Http\Resources\ProvinceResource;

class ProvinceController extends Controller
{
    public function index()
    {
        $provinces = \Illuminate\Support\Facades\Cache::remember('provinces:all', 3600, function () {
            return Province::all();
        });

        return response()->json([
            'success' => true,
            'data'    => ProvinceResource::collection($provinces)
        ]);
    }
}
