<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetCinemasRequest;
use App\Http\Resources\CinemaResource;
use App\Services\CinemaService;

class CinemaController extends Controller
{
    public function __construct(private CinemaService $cinemaService)
    {
    }

    public function index(GetCinemasRequest $request)
    {
        $province = $request->get('province', 'all');
        $cinemas = $this->cinemaService->getList($province);

        return response()->json([
            'success' => true,
            'data'    => CinemaResource::collection($cinemas),
        ]);
    }

    public function show($id)
    {
        $cinema = $this->cinemaService->getDetail($id);

        return response()->json([
            'success' => true,
            'data'    => new CinemaResource($cinema),
        ]);
    }
}
