<?php

namespace App\Services;

use App\Events\SeatStatusUpdated;
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
        ?string $voucherCode = null
    ) {
        return DB::transaction(function () use ($userId, $showtimeId, $showtimeSeatIds, $combos, $voucherCode) {
            $user = User::find($userId);

            // DB Lock in order to prevent deadlocks
            $seats = ShowtimeSeat::with('seat')
                ->whereIn('showtime_seat_id', $showtimeSeatIds)
                ->where('showtime_id', $showtimeId)
                ->orderBy('showtime_seat_id')
                ->lockForUpdate()
                ->get();

            $foundIds = $seats->pluck('showtime_seat_id')->toArray();
            $missingIds = array_diff($showtimeSeatIds, $foundIds);
            if (!empty($missingIds)) {
                throw new HttpException(404, "Không tìm thấy các ghế có ID: [" . implode(', ', $missingIds) . "] trong suất chiếu #{$showtimeId}.");
            }

            $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);

            // Fetch active pending bookings for all requested seats in 1 query
            $activePendingSeats = BookingSeat::whereIn('showtime_seat_id', $showtimeSeatIds)
                ->whereHas('booking', function ($q) use ($showtimeId, $ttlSeconds) {
                    $q->where('showtime_id', $showtimeId)
                      ->where('booking_status', 'pending')
                      ->where('created_at', '>=', now()->subSeconds($ttlSeconds));
                })
                ->with('booking')
                ->get()
                ->keyBy('showtime_seat_id');

            // Batch Redis pipeline lookup for lock values
            $redisLocks = [];
            try {
                $pipeResults = Redis::pipeline(function ($pipe) use ($showtimeId, $showtimeSeatIds) {
                    foreach ($showtimeSeatIds as $sId) {
                        $pipe->get("hold:showtime:{$showtimeId}:seat:{$sId}");
                    }
                });
                foreach ($showtimeSeatIds as $idx => $sId) {
                    $redisLocks[$sId] = $pipeResults[$idx] ?? null;
                }
            } catch (\Throwable $e) {}

            // Validate seats status
            foreach ($seats as $seat) {
                if (in_array($seat->status, ['booked', 'blocked'])) {
                    throw new HttpException(409, "Ghế {$seat->row_name}{$seat->seat_number} (ID {$seat->showtime_seat_id}) đã bị mua hoặc khóa.");
                }

                $activePending = $activePendingSeats->get($seat->showtime_seat_id);
                if ($activePending && $activePending->booking && $activePending->booking->user_id != $userId) {
                    throw new HttpException(409, "Ghế {$seat->row_name}{$seat->seat_number} (ID {$seat->showtime_seat_id}) đang bị giữ bởi đơn hàng #{$activePending->booking_id}.");
                }

                $lockVal = $redisLocks[$seat->showtime_seat_id] ?? null;
                if ($lockVal) {
                    $heldBooking = is_numeric($lockVal) ? Booking::find((int) $lockVal) : null;
                    $isGhostLock = !$heldBooking 
                        || $heldBooking->booking_status !== 'pending' 
                        || $heldBooking->showtime_id != $showtimeId 
                        || $heldBooking->created_at < now()->subSeconds($ttlSeconds);

                    if ($isGhostLock) {
                        try { Redis::del("hold:showtime:{$showtimeId}:seat:{$seat->showtime_seat_id}"); } catch (\Throwable $e) {}
                    } elseif ($heldBooking && $heldBooking->user_id != $userId) {
                        throw new HttpException(409, "Ghế {$seat->row_name}{$seat->seat_number} (ID {$seat->showtime_seat_id}) đang được giữ bởi đơn hàng #{$heldBooking->booking_id}.");
                    }
                }
            }

            // Phase 2.5: Auto-cancel previous pending bookings for this user & showtime, releasing their unselected seats
            $previousUserPending = Booking::where('user_id', $userId)
                ->where('showtime_id', $showtimeId)
                ->where('booking_status', 'pending')
                ->get();

            if ($previousUserPending->isNotEmpty()) {
                $prevBookingIds = $previousUserPending->pluck('booking_id');
                $oldSeatIds = BookingSeat::whereIn('booking_id', $prevBookingIds)->pluck('showtime_seat_id')->toArray();
                
                if (!empty($oldSeatIds)) {
                    ShowtimeSeat::whereIn('showtime_seat_id', $oldSeatIds)->update(['status' => 'available']);
                    try {
                        Redis::pipeline(function ($pipe) use ($showtimeId, $oldSeatIds) {
                            foreach ($oldSeatIds as $sId) {
                                $pipe->del("hold:showtime:{$showtimeId}:seat:{$sId}");
                            }
                        });
                    } catch (\Throwable $e) {}
                }
                Booking::whereIn('booking_id', $prevBookingIds)->update(['booking_status' => 'cancelled']);
            }

            // Calculate financial breakdown snapshot using PricingEngineService
            $snapshot = $this->pricingEngineService->calculateSummary(
                $showtimeId,
                $showtimeSeatIds,
                $combos,
                $voucherCode,
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

            // Batch create BookingSeat entries and set Redis TTL keys via pipeline
            $nowTime = now();
            $batchSeats = [];
            foreach ($snapshot['items']['tickets'] as $tItem) {
                $batchSeats[] = [
                    'booking_id'       => $booking->booking_id,
                    'showtime_seat_id' => $tItem['showtime_seat_id'],
                    'ticket_type'      => $tItem['seat_type'],
                    'price'            => $tItem['final_seat_price'],
                    'created_at'       => $nowTime,
                ];
            }
            if (!empty($batchSeats)) {
                BookingSeat::insert($batchSeats);
            }

            try {
                Redis::pipeline(function ($pipe) use ($showtimeId, $showtimeSeatIds, $ttlSeconds, $booking) {
                    foreach ($showtimeSeatIds as $seatId) {
                        $pipe->setex("hold:showtime:{$showtimeId}:seat:{$seatId}", $ttlSeconds, $booking->booking_id);
                    }
                });
            } catch (\Throwable $e) {}

            // Batch create BookingCombo entries
            $batchCombos = [];
            foreach ($snapshot['items']['combos'] as $cItem) {
                $batchCombos[] = [
                    'booking_id'       => $booking->booking_id,
                    'combo_id'         => $cItem['combo_id'],
                    'quantity'         => $cItem['quantity'],
                    'price_at_booking' => $cItem['unit_price'],
                    'is_claimed'       => false,
                    'created_at'       => $nowTime,
                    'updated_at'       => $nowTime,
                ];
            }
            if (!empty($batchCombos)) {
                BookingCombo::insert($batchCombos);
            }

            return $booking;
        });

        // Broadcast holding state to other users on the seat selection page
        try {
            event(new SeatStatusUpdated($showtimeId, $showtimeSeatIds, 'holding', $userId));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to broadcast SeatStatusUpdated (holding) for showtime {$showtimeId}: " . $e->getMessage());
        }

        // Dispatch auto-cancel job AFTER DB transaction commits to prevent PostgreSQL transaction aborts
        try {
            $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);
            \App\Jobs\CancelExpiredBookingJob::dispatch($booking->booking_id)->delay(now()->addSeconds($ttlSeconds));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to dispatch CancelExpiredBookingJob for booking {$booking->booking_id}: " . $e->getMessage());
        }

        return $booking;
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

        // Batch delete Redis keys via pipeline
        try {
            Redis::pipeline(function ($pipe) use ($showtimeId, $showtimeSeatIds) {
                foreach ($showtimeSeatIds as $seatId) {
                    $pipe->del("hold:showtime:{$showtimeId}:seat:{$seatId}");
                }
            });
        } catch (\Throwable $e) {
            // Redis fallback
        }

        // Cancel any pending booking in DB for these seats by this user in 1 query
        Booking::where('user_id', $userId)
            ->where('showtime_id', $showtimeId)
            ->where('booking_status', 'pending')
            ->whereHas('bookingSeats', function ($q) use ($showtimeSeatIds) {
                $q->whereIn('showtime_seat_id', $showtimeSeatIds);
            })
            ->update(['booking_status' => 'cancelled']);

        // Broadcast available state so others see seats are unlocked
        try {
            event(new SeatStatusUpdated($showtimeId, $showtimeSeatIds, 'available', $userId));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to broadcast SeatStatusUpdated (available) for showtime {$showtimeId}: " . $e->getMessage());
        }

        return true;
    }

    /**
     * Confirm booking after successful payment and update seat status to booked.
     */
    public function confirmBooking(int $bookingId, array $paymentData = [])
    {
        $confirmedResult = DB::transaction(function () use ($bookingId, $paymentData) {
            $booking = Booking::where('booking_id', $bookingId)->lockForUpdate()->firstOrFail();

            if ($booking->booking_status === 'completed' || $booking->booking_status === 'paid') {
                return ['booking' => $booking, 'seatIds' => []];
            }

            $booking->update(['booking_status' => 'completed']);

            // Update showtime seats to booked in DB
            $seatIds = BookingSeat::where('booking_id', $booking->booking_id)->pluck('showtime_seat_id')->toArray();
            ShowtimeSeat::whereIn('showtime_seat_id', $seatIds)->update(['status' => 'booked']);

            // Remove Redis hold keys via pipeline
            try {
                Redis::pipeline(function ($pipe) use ($booking, $seatIds) {
                    foreach ($seatIds as $sId) {
                        $pipe->del("hold:showtime:{$booking->showtime_id}:seat:{$sId}");
                    }
                });
            } catch (\Throwable $e) {
                // Redis fallback
            }

            // Increment voucher used count if voucher was applied
            if ($booking->voucher_id) {
                \App\Models\Voucher::where('voucher_id', $booking->voucher_id)->increment('used_count');
            }

            $user = $booking->user;
            $tierUpgraded = false;
            $newTier = null;
            if ($user) {
                $oldTier = $user->userTier();
                // Loyalty points calculation (1 point per 10,000 VND spent) -> updates users.total_points
                $earnedPoints = (int) round($booking->final_amount / 10000);
                if ($earnedPoints > 0) {
                    $user->increment('total_points', $earnedPoints);
                    $user->refresh();
                    $newTier = $user->userTier();
                    if ($newTier && (!$oldTier || $newTier->user_tier_id !== $oldTier->user_tier_id)) {
                        $tierUpgraded = true;
                    }
                }
            }

            // Real-time Revenue Updated Broadcast (Triggered safely after DB Commit)
            $booking->loadMissing('showtime.room');
            \App\Events\RevenueUpdated::dispatchSafely(
                $booking->booking_id,
                'payment_completed',
                $booking->showtime?->room?->cinema_id,
                $booking->showtime?->movie_id
            );

            return [
                'booking'      => $booking, 
                'seatIds'      => (array) $seatIds,
                'tierUpgraded' => $tierUpgraded,
                'newTier'      => $newTier
            ];
        });

        $booking = $confirmedResult['booking'];
        $seatIds = $confirmedResult['seatIds'];

        // Dispatch Confirmation Email Job asynchronously
        try {
            \App\Jobs\SendBookingEmailJob::dispatch($booking->booking_id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to dispatch SendBookingEmailJob for booking #{$booking->booking_id}: " . $e->getMessage());
        }

        // Dispatch Tier Upgrade Email if loyalty points unlocked new tier
        if (!empty($confirmedResult['tierUpgraded']) && !empty($confirmedResult['newTier']) && $booking->user && !empty($booking->user->email)) {
            try {
                \Illuminate\Support\Facades\Mail::to($booking->user->email)->queue(new \App\Mail\TierUpgradedMail($booking->user, $confirmedResult['newTier']));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to queue TierUpgradedMail for user #{$booking->user_id}: " . $e->getMessage());
            }
        }

        // Broadcast booked status in real-time
        if (!empty($seatIds)) {
            try {
                event(new SeatStatusUpdated($booking->showtime_id, $seatIds, 'booked'));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to broadcast SeatStatusUpdated (booked) for showtime {$booking->showtime_id}: " . $e->getMessage());
            }
        }

        return $booking;
    }
}
