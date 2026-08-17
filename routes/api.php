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
| API Routes (CineDot Core API Specification v1.1.0)
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
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:1,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
        Route::post('/email/verification-notification', [AuthController::class, 'verifyEmailResend'])->middleware(['auth:sanctum', 'throttle:6,1']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users/profile', [UserController::class, 'profile']);
        Route::match(['put', 'patch'], '/users/profile', [UserController::class, 'updateProfile']);
        Route::post('/users/change-password', [UserController::class, 'changePassword']);
        Route::get('/users/transactions', [UserController::class, 'transactions']);

        // ── Bookings ──────────────────────────────────────────────────────────────
        Route::post('/bookings/hold-seats', [App\Http\Controllers\Api\BookingController::class, 'holdSeats']);
        Route::post('/bookings/release-seats', [App\Http\Controllers\Api\BookingController::class, 'releaseSeats']);
        Route::post('/bookings/calculate-summary', [App\Http\Controllers\Api\BookingController::class, 'calculateSummary']);
        Route::get('/users/bookings',       [App\Http\Controllers\Api\BookingController::class, 'myBookings']);
        Route::get('/users/fnb-orders',     [App\Http\Controllers\Api\BookingController::class, 'myFnbOrders']);
        Route::get('/bookings/history',     [App\Http\Controllers\Api\BookingController::class, 'myBookings']);
        Route::get('/bookings/{id}',        [App\Http\Controllers\Api\BookingController::class, 'show']);
        Route::post('/bookings/{id}/apply-voucher', [App\Http\Controllers\Api\VoucherController::class, 'apply']);
        Route::post('/bookings/{id}/remove-voucher', [App\Http\Controllers\Api\VoucherController::class, 'remove']);
        Route::post('/vouchers/apply',      [App\Http\Controllers\Api\VoucherController::class, 'applyStandalone']);
        Route::get('/vouchers',             [App\Http\Controllers\Api\VoucherController::class, 'listActive']);
        
        // ── Payments ──────────────────────────────────────────────────────────────
        Route::post('/payments',            [App\Http\Controllers\Api\PaymentController::class, 'process']);
        Route::post('/payments/create-url', [App\Http\Controllers\Api\PaymentController::class, 'createUrl']);
        

        Route::post('/bookings/{id}/cancel', [\App\Http\Controllers\Api\BookingController::class, 'cancel']);

        // ── Staff Routes ──────────────────────────────────────────────────────────
        Route::prefix('staff')->middleware(['role:staff,admin'])->group(function () {
            Route::post('check-in', [\App\Http\Controllers\Api\Staff\BookingCheckInController::class, 'checkInByQr']);
            Route::post('bookings/{code}/checkin', [\App\Http\Controllers\Api\Staff\BookingCheckInController::class, 'checkIn']);
            Route::post('fnb/claim', [\App\Http\Controllers\Api\Staff\BookingCheckInController::class, 'claimFnb']);
            Route::post('pos/create-order', [\App\Http\Controllers\Api\Staff\BookingCheckInController::class, 'createPosOrder']);
        });
    });

    // ── Payment Webhooks & Callbacks ──────────────────────────────────────────
    Route::post('/webhooks/payment', [App\Http\Controllers\Api\PaymentCallbackController::class, 'paymentWebhook']);
    Route::get('/payments/vnpay/ipn', [App\Http\Controllers\Api\PaymentCallbackController::class, 'vnpayIpn']);
    Route::get('/payments/vnpay/return', [App\Http\Controllers\Api\PaymentCallbackController::class, 'vnpayReturn']);
    Route::get('/payments/vnpay/return/payment-result', [App\Http\Controllers\Api\PaymentCallbackController::class, 'vnpayReturn']);

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
    Route::get('/movies/{identifier}/showtimes', [ShowtimeController::class, 'byMovie']);
    Route::get('/movies/{id}/credits',      [CreditController::class, 'show']);
    Route::get('/movies/{id}/similar',      [MovieController::class, 'similar']);
    Route::get('/movies/{id}/videos',       [MovieController::class, 'videos']);


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
    Route::get('/showtimes/{id}/seat-status', [ShowtimeController::class, 'seatStatus']);

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
        // Movies & TMDB Sync
        Route::post('movies/sync', [\App\Http\Controllers\Api\Admin\MovieController::class, 'sync']);
        Route::apiResource('movies', \App\Http\Controllers\Api\Admin\MovieController::class);
        
        // Movie Credits
        Route::get('movies/{movie}/credits', [\App\Http\Controllers\Api\Admin\MovieCreditController::class, 'index']);
        Route::post('movies/{movie}/credits', [\App\Http\Controllers\Api\Admin\MovieCreditController::class, 'store']);
        Route::delete('movies/{movie}/credits/{credit}', [\App\Http\Controllers\Api\Admin\MovieCreditController::class, 'destroy']);
        
        // Cinemas & Rooms
        Route::apiResource('cinemas', \App\Http\Controllers\Api\Admin\CinemaController::class);
        Route::apiResource('cinemas.rooms', \App\Http\Controllers\Api\Admin\RoomController::class)->shallow();
        
        // Showtimes & Schedules
        Route::post('showtimes', [\App\Http\Controllers\Api\Admin\ScheduleController::class, 'store']);
        Route::apiResource('schedules', \App\Http\Controllers\Api\Admin\ScheduleController::class);
        
        // Campaigns, Vouchers & Banners
        Route::get('campaigns', [\App\Http\Controllers\Api\Admin\CampaignController::class, 'index']);
        Route::post('campaigns', [\App\Http\Controllers\Api\Admin\CampaignController::class, 'store']);
        Route::post('campaigns/{id}/vouchers', [\App\Http\Controllers\Api\Admin\CampaignController::class, 'storeVoucher']);
        Route::post('campaigns/{id}/banners', [\App\Http\Controllers\Api\Admin\CampaignController::class, 'storeBanner']);
        Route::get('campaigns/{id}/roi', [\App\Http\Controllers\Api\Admin\CampaignController::class, 'roi']);

        // Users & Roles
        Route::get('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::put('users/{id}/role', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateRole']);
        Route::get('users/{userId}/roles', [\App\Http\Controllers\Api\Admin\UserRoleController::class, 'index']);
        Route::post('users/{userId}/roles', [\App\Http\Controllers\Api\Admin\UserRoleController::class, 'store']);
        Route::delete('users/{userId}/roles/{id}', [\App\Http\Controllers\Api\Admin\UserRoleController::class, 'destroy']);
        
        // Bookings
        Route::get('bookings', [\App\Http\Controllers\Api\Admin\BookingController::class, 'index']);
        Route::get('bookings/{id}', [\App\Http\Controllers\Api\Admin\BookingController::class, 'show']);
        Route::post('bookings/{id}/refund', [\App\Http\Controllers\Api\Admin\BookingController::class, 'refund']);

        // Reports
        Route::get('reports/revenue', [\App\Http\Controllers\Api\Admin\ReportController::class, 'revenue']);

        // Vouchers, Combos, Banners, Provinces, Genres, Persons
        Route::apiResource('vouchers', \App\Http\Controllers\Api\Admin\VoucherController::class);
        Route::apiResource('combos', \App\Http\Controllers\Api\Admin\ComboController::class);
        Route::apiResource('provinces', \App\Http\Controllers\Api\Admin\ProvinceController::class);
        Route::apiResource('genres', \App\Http\Controllers\Api\Admin\GenreController::class);
        Route::apiResource('persons', \App\Http\Controllers\Api\Admin\PersonController::class);
        Route::apiResource('banners', \App\Http\Controllers\Api\Admin\BannerController::class);

        // Pricing Rules
        Route::patch('pricing-rules/{id}/toggle-active', [\App\Http\Controllers\Api\Admin\PricingRuleController::class, 'toggleActive']);
        Route::apiResource('pricing-rules', \App\Http\Controllers\Api\Admin\PricingRuleController::class);
    });

}); // End of v1 prefix

// Email verification
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');
