<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Http\Resources\ProvinceResource;

class ProvinceController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => ProvinceResource::collection(Province::all())
        ]);
    }
}
