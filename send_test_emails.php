<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Mail\TierUpgradedMail;
use App\Mail\WelcomeUserMail;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserTier;
use App\Notifications\CustomResetPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

$targetEmail = 'lequy27102006@gmail.com';
$targetName = 'Lê Quý';

echo "=== ĐANG GỬI THỬ NGHIỆM 5 MẪU EMAIL ĐẾN: {$targetEmail} ===\n\n";

// 1. Setup Test User
$testUser = User::where('email', $targetEmail)->first();
if (!$testUser) {
    $testUser = User::first() ?? new User();
}
$testUser->email = $targetEmail;
$testUser->name = $targetName;
$testUser->fullname = $targetName;
$testUser->total_points = 1250;

// 2. Setup Test Booking
$booking = Booking::with([
    'showtime.movie',
    'showtime.room.cinema',
    'bookingSeats.showtimeSeat',
    'bookingCombos.combo',
    'user'
])->latest()->first();

if (!$booking) {
    $booking = new Booking();
    $booking->booking_id = 9999;
    $booking->booking_code = 'CINEDOT-78A9B2';
    $booking->final_amount = 210000;
    $booking->discount_amount = 30000;
    $booking->payment_method = 'VNPAY';
}

$booking->user = $testUser;

// 3. Setup Test Tier
$tier = UserTier::first();
if (!$tier) {
    $tier = new UserTier();
    $tier->tier = 'Gold VIP';
    $tier->discount_percent = 10;
}

$results = [];

// TEST 1: Welcome User Email
try {
    echo "1. Đang gửi: Welcome User Email... ";
    Mail::to($targetEmail)->send(new WelcomeUserMail($testUser));
    echo "✅ THÀNH CÔNG\n";
    $results['welcome'] = true;
} catch (\Throwable $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    $results['welcome'] = false;
}

// TEST 2: Booking Confirmed Email (E-Ticket)
try {
    echo "2. Đang gửi: Booking Confirmed E-Ticket Email... ";
    Mail::to($targetEmail)->send(new BookingConfirmedMail($booking));
    echo "✅ THÀNH CÔNG\n";
    $results['booking_confirmed'] = true;
} catch (\Throwable $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    $results['booking_confirmed'] = false;
}

// TEST 3: Booking Cancelled Email
try {
    echo "3. Đang gửi: Booking Cancelled / Refund Email... ";
    Mail::to($targetEmail)->send(new BookingCancelledMail($booking, 100));
    echo "✅ THÀNH CÔNG\n";
    $results['booking_cancelled'] = true;
} catch (\Throwable $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    $results['booking_cancelled'] = false;
}

// TEST 4: Tier Upgraded Email
try {
    echo "4. Đang gửi: Tier Upgraded Email... ";
    Mail::to($targetEmail)->send(new TierUpgradedMail($testUser, $tier));
    echo "✅ THÀNH CÔNG\n";
    $results['tier_upgraded'] = true;
} catch (\Throwable $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    $results['tier_upgraded'] = false;
}

// TEST 5: Custom Reset Password / OTP Email
try {
    echo "5. Đang gửi: Custom Reset Password OTP Email... ";
    $testToken = 'test_reset_token_' . bin2hex(random_bytes(16));
    try {
        Redis::setex("password_reset:otp:{$targetEmail}", 900, '868999');
    } catch (\Throwable $e) {}
    
    $notification = new CustomResetPassword($testToken);
    $mailData = $notification->toResend($testUser);
    
    Mail::html($mailData['html'], function ($message) use ($targetEmail, $mailData) {
        $message->to($targetEmail)
                ->subject($mailData['subject']);
    });
    echo "✅ THÀNH CÔNG\n";
    $results['otp_reset'] = true;
} catch (\Throwable $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    $results['otp_reset'] = false;
}

echo "\n=== KẾT QUẢ GỬI EMAIL ===\n";
print_r($results);
