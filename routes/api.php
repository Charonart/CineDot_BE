<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MovieController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Movie routes
Route::get('/movies/trending', [MovieController::class, 'trending']);  // GET /api/movies/trending
Route::get('/movies/popular',  [MovieController::class, 'popular']);   // GET /api/movies/popular
Route::get('/movies',          [MovieController::class, 'index']);     // GET /api/movies?search=&status=&genre_id=
Route::get('/movies/{id}',     [MovieController::class, 'show']);      // GET /api/movies/{id}
