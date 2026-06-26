<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\ScheduleSeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function holdSeats(int $userId, int $scheduleId, array $scheduleSeatIds, array $combos = [])
    {
        return DB::transaction(function () use ($userId, $scheduleId, $scheduleSeatIds, $combos) {
            // Khoá dòng dữ liệu để xử lý đồng thời an toàn (thêm orderBy để chống deadlock)
            $seats = ScheduleSeat::whereIn('schedule_seat_id', $scheduleSeatIds)
                ->where('schedule_id', $scheduleId)
                ->orderBy('schedule_seat_id')
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($scheduleSeatIds)) {
                throw ValidationException::withMessages([
                    'seats' => 'Ghế không hợp lệ cho suất chiếu này.'
                ]);
            }

            $totalAmount = 0;
            foreach ($seats as $seat) {
                // Kiểm tra DB xem có bị bán chưa
                if ($seat->status === 'booked') {
                    throw new \Symfony\Component\HttpKernel\Exception\HttpException(409, 'Một số ghế bạn chọn đã bị bán. Vui lòng chọn ghế khác.');
                }
                
                // Kiểm tra Redis xem có đang bị ai đó giữ không
                $redisKey = "hold:schedule:{$scheduleId}:seat:{$seat->schedule_seat_id}";
                if (Redis::exists($redisKey)) {
                    throw new \Symfony\Component\HttpKernel\Exception\HttpException(409, 'Một số ghế bạn chọn đang có người khác giữ. Vui lòng thử lại sau.');
                }
                
                $totalAmount += $seat->price;
            }

            $comboData = [];
            if (!empty($combos)) {
                $comboIds = array_column($combos, 'combo_id');
                $dbCombos = \App\Models\Combo::whereIn('combo_id', $comboIds)
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('combo_id');

                foreach ($combos as $comboInput) {
                    $cId = $comboInput['combo_id'];
                    $qty = $comboInput['quantity'];
                    if (!isset($dbCombos[$cId])) {
                        throw ValidationException::withMessages(['combos' => 'Combo không hợp lệ hoặc đã ngừng bán.']);
                    }
                    $price = $dbCombos[$cId]->price;
                    $totalAmount += ($price * $qty);
                    $comboData[] = [
                        'combo_id' => $cId,
                        'quantity' => $qty,
                        'price_at_booking' => $price,
                    ];
                }
            }

            $booking = Booking::create([
                'user_id'      => $userId,
                'schedule_id'  => $scheduleId,
                'total_amount'   => $totalAmount,
                'booking_status' => 'pending',
                'booking_code'   => 'CD' . time() . rand(100, 999),
            ]);

            foreach ($seats as $seat) {
                // Đẩy trạng thái HELD lên Redis với TTL 10 phút (600 giây)
                $redisKey = "hold:schedule:{$scheduleId}:seat:{$seat->schedule_seat_id}";
                Redis::setex($redisKey, 600, $booking->booking_id);

                BookingSeat::create([
                    'booking_id'       => $booking->booking_id,
                    'schedule_seat_id' => $seat->schedule_seat_id,
                    'price_at_booking' => $seat->price,
                ]);
            }

            foreach ($comboData as $cData) {
                $cData['booking_id'] = $booking->booking_id;
                \App\Models\BookingCombo::create($cData);
            }

            // Đẩy Job hủy đơn tự động sau 10 phút nếu không thanh toán
            \App\Jobs\CancelExpiredBookingJob::dispatch($booking->booking_id)->delay(now()->addMinutes(10));

            return $booking->load(['schedule.movie', 'bookingSeats.scheduleSeat.seat', 'bookingCombos.combo']);
        });
    }

    public function confirmBooking(int $bookingId)
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = Booking::where('booking_id', $bookingId)->lockForUpdate()->firstOrFail();

            if ($booking->booking_status === 'completed') {
                return $booking;
            }

            $booking->update(['booking_status' => 'completed']);

            // Cộng điểm tích lũy & ghi nhận lịch sử
            $user = $booking->user;
            $earnedPoints = (int) round($booking->total_amount / 10000);
            if ($earnedPoints > 0) {
                $user->increment('point', $earnedPoints);

                \App\Models\PointHistory::create([
                    'user_id'    => $user->user_id,
                    'booking_id' => $booking->booking_id,
                    'amount'     => $earnedPoints,
                    'action'     => 'earn_booking',
                ]);
            }

            // Mark point-exchanged vouchers as used
            $appliedVoucherIds = \App\Models\BookingVoucher::where('booking_id', $booking->booking_id)->pluck('voucher_id');
            if ($appliedVoucherIds->isNotEmpty()) {
                \App\Models\UserVoucher::where('user_id', $booking->user_id)
                    ->whereIn('voucher_id', $appliedVoucherIds)
                    ->where('is_used', false)
                    ->update([
                        'is_used' => true,
                        'used_at' => now()
                    ]);
            }

            $scheduleSeatIds = BookingSeat::where('booking_id', $booking->booking_id)->pluck('schedule_seat_id');
            ScheduleSeat::whereIn('schedule_seat_id', $scheduleSeatIds)->update(['status' => 'booked']);

            // Xóa thủ công key trên Redis vì ghế đã bán thành công
            foreach ($scheduleSeatIds as $seatId) {
                Redis::del("hold:schedule:{$booking->schedule_id}:seat:{$seatId}");
            }

            // Gửi email xác nhận
            \App\Jobs\SendBookingEmailJob::dispatch($booking);

            return $booking;
        });
    }
}
