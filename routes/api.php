<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\CinemaController;
use App\Http\Controllers\Api\ShowtimeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Auth & Users ──────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/profile', [UserController::class, 'profile']);
    Route::put('/users/profile', [UserController::class, 'updateProfile']);

    // ── Bookings ──────────────────────────────────────────────────────────────
    Route::post('/bookings/hold-seats', [App\Http\Controllers\Api\BookingController::class, 'holdSeats']);
    Route::get('/users/bookings',       [App\Http\Controllers\Api\BookingController::class, 'myBookings']);
    Route::get('/bookings/{id}',        [App\Http\Controllers\Api\BookingController::class, 'show']);
    Route::post('/bookings/{id}/apply-voucher', [App\Http\Controllers\Api\VoucherController::class, 'apply']);
    Route::post('/bookings/{id}/remove-voucher', [App\Http\Controllers\Api\VoucherController::class, 'remove']);
    
    // ── Payments ──────────────────────────────────────────────────────────────
    Route::post('/payments',            [App\Http\Controllers\Api\PaymentController::class, 'process']);
    
    // ── Reviews ───────────────────────────────────────────────────────────────
    Route::post('/movies/{id}/reviews', [App\Http\Controllers\Api\ReviewController::class, 'store']);
});

// ── Master Data ───────────────────────────────────────────────────────────
Route::get('/provinces',        [App\Http\Controllers\Api\ProvinceController::class, 'index']);
Route::get('/persons/{id}',     [App\Http\Controllers\Api\PersonController::class, 'show']);
Route::get('/combos',           [App\Http\Controllers\Api\ComboController::class, 'index']);

// ── Genres ────────────────────────────────────────────────────────────────
Route::get('/genres',           [GenreController::class, 'index']);
Route::get('/genres/{id}/movies',[GenreController::class, 'movies']);

// ── Movies ────────────────────────────────────────────────────────────────
Route::get('/movies/trending',  [MovieController::class, 'trending']);
Route::get('/movies/popular',   [MovieController::class, 'popular']);
Route::get('/movies',           [MovieController::class, 'index']);

Route::get('/movies/{id}',              [MovieController::class, 'show']);
Route::get('/movies/{id}/credits',      [CreditController::class, 'show']);
Route::get('/movies/{id}/similar',      [MovieController::class, 'similar']);
Route::get('/movies/{id}/showtimes',    [ShowtimeController::class, 'byMovie']);
Route::get('/movies/{id}/videos',       [MovieController::class, 'videos']);
Route::get('/movies/{id}/reviews',      [App\Http\Controllers\Api\ReviewController::class, 'index']);

// ── Cinemas & Rooms ───────────────────────────────────────────────────────
Route::get('/cinemas',          [CinemaController::class, 'index']);
Route::get('/cinemas/{id}',     [CinemaController::class, 'show']);
Route::get('/rooms/{id}/seats', [App\Http\Controllers\Api\RoomController::class, 'seats']);

// ── Showtimes ─────────────────────────────────────────────────────────────
Route::get('/showtimes',              [ShowtimeController::class, 'index']);
Route::get('/showtimes/{id}',         [ShowtimeController::class, 'show']);
Route::get('/showtimes/{id}/seats',   [ShowtimeController::class, 'seats']);
