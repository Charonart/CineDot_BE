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
use App\Http\Controllers\Api\ReviewController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Auth & Users ──────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::get('/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
        Route::post('/email/verification-notification', [AuthController::class, 'verifyEmailResend'])->middleware(['auth:sanctum', 'throttle:6,1']);
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

        // ── Loyalty & Rewards ──────────────────────────────────────────────────────
        Route::get('/users/points-history', [\App\Http\Controllers\Api\PointHistoryController::class, 'index']);
        Route::post('/users/exchange-points', [\App\Http\Controllers\Api\PointExchangeController::class, 'exchange']);
        Route::post('/bookings/{id}/cancel', [\App\Http\Controllers\Api\BookingController::class, 'cancel']);

        // ── Staff Routes ──────────────────────────────────────────────────────────
        Route::prefix('staff')->middleware(['role:staff,admin'])->group(function () {
            Route::post('bookings/{code}/checkin', [\App\Http\Controllers\Api\Staff\BookingCheckInController::class, 'checkIn']);
        });
    });

    // ── Payment Webhooks & Callbacks ──────────────────────────────────────────
    Route::get('/payments/vnpay/ipn', [App\Http\Controllers\Api\PaymentCallbackController::class, 'vnpayIpn']);
    Route::get('/payments/vnpay/return', [App\Http\Controllers\Api\PaymentCallbackController::class, 'vnpayReturn']);

    // ── Master Data ───────────────────────────────────────────────────────────
    Route::get('/provinces',        [App\Http\Controllers\Api\ProvinceController::class, 'index']);
    Route::get('/banners',          [\App\Http\Controllers\Api\BannerController::class, 'index']);
    Route::get('/persons/{id}',     [App\Http\Controllers\Api\PersonController::class, 'show']);
    Route::get('/combos',           [App\Http\Controllers\Api\ComboController::class, 'index']);

    // ── Genres ────────────────────────────────────────────────────────────────
    Route::get('/genres',           [GenreController::class, 'index']);
    Route::get('/genres/{id}/movies',[GenreController::class, 'movies']);

    // ── Movies ────────────────────────────────────────────────────────────────
    Route::get('/movies/navbar',    [MovieController::class, 'navbar']);
    Route::get('/movies/search',    [MovieController::class, 'search']);
    Route::get('/movies/trending',  [MovieController::class, 'trending']);
    Route::get('/movies/popular',   [MovieController::class, 'popular']);
    Route::get('/movies',           [MovieController::class, 'index']);

    // Static/special routes above, dynamic {slug} below
    Route::get('/movies/{slug}',            [MovieController::class, 'showBySlug']);
    Route::get('/movies/{id}/credits',      [CreditController::class, 'show']);
    Route::get('/movies/{id}/similar',      [MovieController::class, 'similar']);
    Route::get('/movies/{id}/videos',       [MovieController::class, 'videos']);
    Route::get('/movies/{id}/reviews',      [ReviewController::class, 'index']);

    // ── Cinemas & Rooms ───────────────────────────────────────────────────────
    Route::get('/cinemas',          [CinemaController::class, 'index']);
    Route::get('/cinemas/pricing',  [CinemaController::class, 'pricing']);
    Route::get('/cinemas/detail/{slug}', [CinemaController::class, 'showBySlug']);
    Route::get('/cinemas/detail/{slug}/showtimes', [CinemaController::class, 'showtimes']);
    Route::get('/rooms/{id}/seats', [App\Http\Controllers\Api\RoomController::class, 'seats']);
    Route::get('/rooms/{id}/layout', [App\Http\Controllers\Api\RoomController::class, 'layout']);

    // ── Special Theaters ──────────────────────────────────────────────────────
    Route::get('/special-theaters/{type}', [CinemaController::class, 'specialTheaters']);

    // ── Showtimes ─────────────────────────────────────────────────────────────
    Route::get('/showtimes',              [ShowtimeController::class, 'index']);
    Route::get('/showtimes/{id}',         [ShowtimeController::class, 'show']);
    Route::get('/showtimes/{id}/seats',   [ShowtimeController::class, 'seats']);

    // ── Test Resend ───────────────────────────────────────────────────────────
    Route::get('/test-email', function () {
        $resend = Resend::client(env('RESEND_API_KEY'));

        try {
            $result = $resend->emails->send([
                'from' => env('RESEND_MAIL_FROM'),
                'to' => 'lequy27102006@gmail.com',
                'subject' => 'Hello World',
                'html' => '<p>Congrats on sending your <strong>first email</strong>!</p>'
            ]);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    });

    // ── Admin Routes ──────────────────────────────────────────────────────────
    Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // Movies
        Route::apiResource('movies', \App\Http\Controllers\Api\Admin\MovieController::class);
        
        // Movie Credits
        Route::get('movies/{movie}/credits', [\App\Http\Controllers\Api\Admin\MovieCreditController::class, 'index']);
        Route::post('movies/{movie}/credits', [\App\Http\Controllers\Api\Admin\MovieCreditController::class, 'store']);
        Route::delete('movies/{movie}/credits/{credit}', [\App\Http\Controllers\Api\Admin\MovieCreditController::class, 'destroy']);
        
        // Cinemas
        Route::apiResource('cinemas', \App\Http\Controllers\Api\Admin\CinemaController::class);
        // Rooms
        Route::apiResource('cinemas.rooms', \App\Http\Controllers\Api\Admin\RoomController::class)->shallow();
        
        // Seats
        Route::apiResource('rooms.seats', \App\Http\Controllers\Api\Admin\SeatController::class)->shallow()->except(['show']);
        
        // Schedules
        Route::apiResource('schedules', \App\Http\Controllers\Api\Admin\ScheduleController::class);
        
        // Users
        Route::get('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::put('users/{id}/role', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateRole']);
        
        // Bookings
        Route::get('bookings', [\App\Http\Controllers\Api\Admin\BookingController::class, 'index']);
        Route::get('bookings/{id}', [\App\Http\Controllers\Api\Admin\BookingController::class, 'show']);

        // Movie Reviews
        Route::get('reviews', [\App\Http\Controllers\Api\Admin\MovieReviewController::class, 'index']);
        Route::delete('reviews/{id}', [\App\Http\Controllers\Api\Admin\MovieReviewController::class, 'destroy']);

        // Vouchers
        Route::apiResource('vouchers', \App\Http\Controllers\Api\Admin\VoucherController::class);

        // Combos
        Route::apiResource('combos', \App\Http\Controllers\Api\Admin\ComboController::class);

        // Provinces
        Route::apiResource('provinces', \App\Http\Controllers\Api\Admin\ProvinceController::class);

        // Genres
        Route::apiResource('genres', \App\Http\Controllers\Api\Admin\GenreController::class);

        // Persons
        Route::apiResource('persons', \App\Http\Controllers\Api\Admin\PersonController::class);

        // Payments & Refunds
        Route::get('payments', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'index']);
        Route::post('payments/{id}/refund', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'refund']);

        // Banners CRUD
        Route::apiResource('banners', \App\Http\Controllers\Api\Admin\BannerController::class);
    });

}); // End of v1 prefix

// Xác thực email link
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');
