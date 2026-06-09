<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\ScheduleSeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function holdSeats(int $userId, int $scheduleId, array $scheduleSeatIds)
    {
        return DB::transaction(function () use ($userId, $scheduleId, $scheduleSeatIds) {
            // Bước 1: Query các ghế đang chọn VÀ KHOÁ DÒNG (Pessimistic Locking)
            $seats = ScheduleSeat::whereIn('schedule_seat_id', $scheduleSeatIds)
                ->where('schedule_id', $scheduleId)
                ->lockForUpdate() // Khoá dòng dữ liệu
                ->get();

            if ($seats->count() !== count($scheduleSeatIds)) {
                throw ValidationException::withMessages([
                    'seats' => 'Ghế không hợp lệ cho suất chiếu này.'
                ]);
            }

            $totalAmount = 0;
            // Bước 2: Kiểm tra xem tất cả ghế có đang trống không
            foreach ($seats as $seat) {
                if ($seat->status !== 'available') {
                    throw ValidationException::withMessages([
                        'seats' => 'Một số ghế bạn chọn đã có người nhanh tay hơn đặt mất. Vui lòng chọn ghế khác.'
                    ]);
                }
                $totalAmount += $seat->price;
            }

            // Bước 3: Đổi trạng thái sang Đang Giữ (held) và tạo đơn hàng
            $booking = Booking::create([
                'user_id'      => $userId,
                'schedule_id'  => $scheduleId,
                'total_amount' => $totalAmount,
                'status'       => 'pending',
            ]);

            foreach ($seats as $seat) {
                $seat->update(['status' => 'held']);

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

            // Đổi trạng thái ghế sang Đã Bán (booked)
            $scheduleSeatIds = BookingSeat::where('booking_id', $booking->booking_id)->pluck('schedule_seat_id');
            ScheduleSeat::whereIn('schedule_seat_id', $scheduleSeatIds)->update(['status' => 'booked']);

            return $booking;
        });
    }
}
