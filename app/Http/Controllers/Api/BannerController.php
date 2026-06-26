<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Http\Resources\BannerResource;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Display a listing of active banners.
     */
    public function index()
    {
        $banners = Banner::where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('banner_id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => BannerResource::collection($banners),
        ]);
    }
}
