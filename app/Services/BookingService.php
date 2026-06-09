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
    public function holdSeats(int $userId, int $scheduleId, array $scheduleSeatIds)
    {
        return DB::transaction(function () use ($userId, $scheduleId, $scheduleSeatIds) {
            // Khoá dòng dữ liệu để xử lý đồng thời an toàn
            $seats = ScheduleSeat::whereIn('schedule_seat_id', $scheduleSeatIds)
                ->where('schedule_id', $scheduleId)
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
                    throw ValidationException::withMessages([
                        'seats' => 'Một số ghế bạn chọn đã bị bán. Vui lòng chọn ghế khác.'
                    ]);
                }
                
                // Kiểm tra Redis xem có đang bị ai đó giữ không
                $redisKey = "hold:schedule:{$scheduleId}:seat:{$seat->schedule_seat_id}";
                if (Redis::exists($redisKey)) {
                    throw ValidationException::withMessages([
                        'seats' => 'Một số ghế bạn chọn đang có người khác giữ. Vui lòng thử lại sau.'
                    ]);
                }
                
                $totalAmount += $seat->price;
            }

            $booking = Booking::create([
                'user_id'      => $userId,
                'schedule_id'  => $scheduleId,
                'total_amount' => $totalAmount,
                'status'       => 'pending',
                'booking_code' => 'CD' . time() . rand(100, 999),
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

            return $booking->load(['schedule.movie', 'bookingSeats.scheduleSeat.seat']);
        });
    }

    public function confirmBooking(int $bookingId)
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = Booking::where('booking_id', $bookingId)->lockForUpdate()->firstOrFail();

            if ($booking->status === 'success') {
                return $booking;
            }

            $booking->update(['status' => 'success']);

            $scheduleSeatIds = BookingSeat::where('booking_id', $booking->booking_id)->pluck('schedule_seat_id');
            ScheduleSeat::whereIn('schedule_seat_id', $scheduleSeatIds)->update(['status' => 'booked']);

            // Xóa thủ công key trên Redis vì ghế đã bán thành công
            foreach ($scheduleSeatIds as $seatId) {
                Redis::del("hold:schedule:{$booking->schedule_id}:seat:{$seatId}");
            }

            return $booking;
        });
    }
}
