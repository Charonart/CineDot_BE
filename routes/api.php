<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\CinemaController;
use App\Http\Controllers\Api\ShowtimeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ── Genres ────────────────────────────────────────────────────────────────
Route::get('/genres',           [GenreController::class, 'index']);   // GET /api/genres
Route::get('/genres/{id}/movies',[GenreController::class, 'movies']); // GET /api/genres/{id}/movies

// ── Movie list routes (phải đứng trước /{id}) ──────────────────────────────
Route::get('/movies/trending',  [MovieController::class, 'trending']); // GET /api/movies/trending
Route::get('/movies/popular',   [MovieController::class, 'popular']);  // GET /api/movies/popular
Route::get('/movies',           [MovieController::class, 'index']);    // GET /api/movies?search=&status=&genre_id=

// ── Movie detail & sub-resources ──────────────────────────────────────────
Route::get('/movies/{id}',              [MovieController::class,  'show']);    // GET /api/movies/{id}
Route::get('/movies/{id}/credits',      [CreditController::class, 'show']);    // GET /api/movies/{id}/credits
Route::get('/movies/{id}/similar',      [MovieController::class,  'similar']); // GET /api/movies/{id}/similar
Route::get('/movies/{id}/showtimes',    [ShowtimeController::class,'byMovie']); // GET /api/movies/{id}/showtimes?date=

// ── Cinemas ───────────────────────────────────────────────────────────────
Route::get('/cinemas',          [CinemaController::class, 'index']); // GET /api/cinemas?city=&chain=
Route::get('/cinemas/{id}',     [CinemaController::class, 'show']);  // GET /api/cinemas/{id}

// ── Showtimes ─────────────────────────────────────────────────────────────
Route::get('/showtimes',        [ShowtimeController::class, 'index']); // GET /api/showtimes?date=&cinema_id=&movie_id=
Route::get('/showtimes/{id}',   [ShowtimeController::class, 'show']);  // GET /api/showtimes/{id}
