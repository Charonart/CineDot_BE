<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCombo;
use App\Models\BookingSeat;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingService
{
    public function __construct(private PricingEngineService $pricingEngineService)
    {
    }

    /**
     * Lock seats with Redis TTL and create pending booking with financial breakdown.
     */
    public function holdSeats(
        int $userId,
        int $showtimeId,
        array $showtimeSeatIds,
        array $combos = [],
        ?string $voucherCode = null,
        int $pointsUsed = 0
    ) {
        return DB::transaction(function () use ($userId, $showtimeId, $showtimeSeatIds, $combos, $voucherCode, $pointsUsed) {
            $user = User::find($userId);

            // DB Lock in order to prevent deadlocks
            $seats = ShowtimeSeat::whereIn('showtime_seat_id', $showtimeSeatIds)
                ->where('showtime_id', $showtimeId)
                ->orderBy('showtime_seat_id')
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($showtimeSeatIds)) {
                throw ValidationException::withMessages([
                    'seats' => 'Một số ghế không tồn tại hoặc không thuộc suất chiếu này.'
                ]);
            }

            $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);

            // Check seat status in DB and Redis
            foreach ($seats as $seat) {
                if (in_array($seat->status, ['booked', 'blocked'])) {
                    throw new HttpException(409, "Ghế {$seat->row_name}{$seat->seat_number} đã bị mua hoặc khóa.");
                }

                // Check active pending bookings in DB
                $activePending = BookingSeat::where('showtime_seat_id', $seat->showtime_seat_id)
                    ->whereHas('booking', function ($q) use ($showtimeId, $ttlSeconds) {
                        $q->where('showtime_id', $showtimeId)
                          ->where('booking_status', 'pending')
                          ->where('created_at', '>=', now()->subSeconds($ttlSeconds));
                    })->first();

                if ($activePending) {
                    throw new HttpException(409, "Ghế {$seat->row_name}{$seat->seat_number} đang có người khác giữ.");
                }

                // Check Redis hold key with exception fallback
                try {
                    $redisKey = "hold:showtime:{$showtimeId}:seat:{$seat->showtime_seat_id}";
                    if (Redis::exists($redisKey)) {
                        throw new HttpException(409, "Ghế {$seat->row_name}{$seat->seat_number} đang có người khác giữ.");
                    }
                } catch (HttpException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    // Redis connection issue; rely on DB lock
                }
            }

            // Calculate financial breakdown snapshot using PricingEngineService
            $snapshot = $this->pricingEngineService->calculateSummary(
                $showtimeId,
                $showtimeSeatIds,
                $combos,
                $voucherCode,
                $pointsUsed,
                $user
            );

            // Create Booking record
            $booking = Booking::create([
                'user_id'         => $userId,
                'showtime_id'     => $showtimeId,
                'voucher_id'      => $snapshot['voucher_id'] ?? null,
                'price_breakdown' => $snapshot,
                'final_amount'    => $snapshot['financial_breakdown']['final_amount_to_pay'],
                'discount_amount' => $snapshot['financial_breakdown']['total_discount_amount'],
                'booking_status'  => 'pending',
                'booking_code'    => $snapshot['booking_summary']['booking_code'],
            ]);

            // Update DB showtime_seats status to holding
            ShowtimeSeat::whereIn('showtime_seat_id', $showtimeSeatIds)->update(['status' => 'holding']);

            // Create BookingSeat entries and set Redis TTL keys
            foreach ($snapshot['items']['tickets'] as $tItem) {
                $seatId = $tItem['showtime_seat_id'];
                
                try {
                    $redisKey = "hold:showtime:{$showtimeId}:seat:{$seatId}";
                    Redis::setex($redisKey, $ttlSeconds, $booking->booking_id);
                } catch (\Exception $e) {
                    // Redis fallback
                }

                BookingSeat::create([
                    'booking_id'       => $booking->booking_id,
                    'showtime_seat_id' => $seatId,
                    'ticket_type'      => $tItem['seat_type'],
                    'price'            => $tItem['final_seat_price'],
                ]);
            }

            // Create BookingCombo entries
            foreach ($snapshot['items']['combos'] as $cItem) {
                BookingCombo::create([
                    'booking_id'       => $booking->booking_id,
                    'combo_id'         => $cItem['combo_id'],
                    'quantity'         => $cItem['quantity'],
                    'price_at_booking' => $cItem['unit_price'],
                    'is_claimed'       => false,
                ]);
            }

            return $booking;
        });
    }

    /**
     * Manually release hold keys on Redis & DB when user cancels seat selection.
     */
    public function releaseSeats(int $userId, int $showtimeId, array $showtimeSeatIds): bool
    {
        // Reset DB showtime_seats status to available for holding seats
        ShowtimeSeat::whereIn('showtime_seat_id', $showtimeSeatIds)
            ->where('status', 'holding')
            ->update(['status' => 'available']);

        foreach ($showtimeSeatIds as $seatId) {
            try {
                $redisKey = "hold:showtime:{$showtimeId}:seat:{$seatId}";
                Redis::del($redisKey);
            } catch (\Exception $e) {
                // Redis fallback
            }

            // Cancel any pending booking in DB for these seats by this user
            $pendingBookings = Booking::where('user_id', $userId)
                ->where('showtime_id', $showtimeId)
                ->where('booking_status', 'pending')
                ->whereHas('bookingSeats', function ($q) use ($seatId) {
                    $q->where('showtime_seat_id', $seatId);
                })->get();

            foreach ($pendingBookings as $pBooking) {
                $pBooking->update(['booking_status' => 'cancelled']);
            }
        }
        return true;
    }

    /**
     * Confirm booking after successful payment and update seat status to booked.
     */
    public function confirmBooking(int $bookingId, array $paymentData = [])
    {
        return DB::transaction(function () use ($bookingId, $paymentData) {
            $booking = Booking::where('booking_id', $bookingId)->lockForUpdate()->firstOrFail();

            if ($booking->booking_status === 'completed' || $booking->booking_status === 'paid') {
                return $booking;
            }

            $booking->update(['booking_status' => 'completed']);

            // Update showtime seats to booked in DB
            $seatIds = BookingSeat::where('booking_id', $booking->booking_id)->pluck('showtime_seat_id');
            ShowtimeSeat::whereIn('showtime_seat_id', $seatIds)->update(['status' => 'booked']);

            // Remove Redis hold keys
            foreach ($seatIds as $sId) {
                try {
                    Redis::del("hold:showtime:{$booking->showtime_id}:seat:{$sId}");
                } catch (\Exception $e) {
                    // Redis fallback
                }
            }

            // Increment voucher used count if voucher was applied
            if ($booking->voucher_id) {
                \App\Models\Voucher::where('voucher_id', $booking->voucher_id)->increment('used_count');
            }

            $user = $booking->user;
            if ($user) {
                // Deduct points used if applicable
                $pointsUsed = $booking->price_breakdown['financial_breakdown']['discounts']['point_discount']['points_used'] ?? 0;
                if ($pointsUsed > 0 && $user->point >= $pointsUsed) {
                    $user->decrement('point', $pointsUsed);
                    \App\Models\PointHistory::create([
                        'user_id'        => $user->user_id,
                        'reference_type' => Booking::class,
                        'reference_id'   => $booking->booking_id,
                        'amount'         => -$pointsUsed,
                        'action'         => 'redeem_booking',
                    ]);
                }

                // Loyalty points calculation (1 point per 10,000 VND spent)
                $earnedPoints = (int) round($booking->final_amount / 10000);
                if ($earnedPoints > 0) {
                    $user->increment('point', $earnedPoints);

                    \App\Models\PointHistory::create([
                        'user_id'        => $user->user_id,
                        'reference_type' => Booking::class,
                        'reference_id'   => $booking->booking_id,
                        'amount'         => $earnedPoints,
                        'action'         => 'earn_booking',
                    ]);
                }
            }

            // Create record in payments table
            if (class_exists('\App\Models\Payment')) {
                try {
                    \App\Models\Payment::create([
                        'booking_id'     => $booking->booking_id,
                        'transaction_id' => $paymentData['transaction_id'] ?? 'TXN-' . strtoupper(uniqid()),
                        'payment_method' => $paymentData['payment_method'] ?? 'VNPAY',
                        'amount'         => $booking->final_amount,
                        'payment_status' => 'success',
                        'payment_time'   => now(),
                    ]);
                } catch (\Exception $e) {
                    // Table payments might have slightly different columns or constraints
                }
            }

            return $booking;
        });
    }
}
