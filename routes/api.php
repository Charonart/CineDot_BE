<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\CreditController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ── Movie list routes (phải đứng trước /{id}) ──────────────────────────────
Route::get('/movies/trending', [MovieController::class, 'trending']);  // GET /api/movies/trending
Route::get('/movies/popular',  [MovieController::class, 'popular']);   // GET /api/movies/popular
Route::get('/movies',          [MovieController::class, 'index']);     // GET /api/movies?search=&status=&genre_id=

// ── Movie detail & sub-resources ──────────────────────────────────────────
Route::get('/movies/{id}',         [MovieController::class, 'show']);     // GET /api/movies/{id}
Route::get('/movies/{id}/credits', [CreditController::class, 'show']);    // GET /api/movies/{id}/credits
Route::get('/movies/{id}/similar', [MovieController::class, 'similar']);  // GET /api/movies/{id}/similar
