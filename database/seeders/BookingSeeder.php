<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Payment;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->count() === 0) return;

        // Lấy 20 suất chiếu ngẫu nhiên (bao gồm cả quá khứ và tương lai)
        $schedules = Schedule::inRandomOrder()->limit(20)->get();

        foreach ($schedules as $sch) {
            $user = $users->random();
            
            // Lấy 2 ghế trống của suất này
            $schSeats = ScheduleSeat::where('schedule_id', $sch->schedule_id)
                ->where('status', 'available')
                ->limit(2)
                ->get();

            if ($schSeats->count() > 0) {
                $totalAmt = $schSeats->sum('price');
                
                // Ngày booking phải nằm trước ngày chiếu
                // Lấy phần ngày (10 ký tự đầu) để tránh double time specification
                $dateOnly = substr($sch->schedule_date, 0, 10);
                $scheduleDateTime = Carbon::parse($dateOnly . ' ' . $sch->schedule_start);
                
                // Nếu suất chiếu ở tương lai quá xa, có thể chưa book, nhưng cứ random 1-2 ngày trước
                $bookingDate = (clone $scheduleDateTime)->subDays(rand(1, 2))->subHours(rand(1, 10));
                
                // Tránh ngày booking lớn hơn hiện tại
                if ($bookingDate > Carbon::now()) {
                    $bookingDate = Carbon::now()->subMinutes(rand(10, 60));
                }

                $booking = Booking::create([
                    'user_id'        => $user->user_id,
                    'schedule_id'    => $sch->schedule_id,
                    'total_amount'   => $totalAmt,
                    'booking_code'   => 'CD' . strtoupper(Str::random(8)),
                    'booking_status' => 'completed',
                    'created_at'     => $bookingDate,
                    'updated_at'     => $bookingDate,
                ]);

                foreach ($schSeats as $ss) {
                    $ss->update(['status' => 'booked']);

                    BookingSeat::create([
                        'booking_id'       => $booking->booking_id,
                        'schedule_seat_id' => $ss->schedule_seat_id,
                    ]);
                }

                Payment::forceCreate([
                    'booking_id'     => $booking->booking_id,
                    'amount'         => $totalAmt,
                    'method'         => 'vnpay',
                    'status'         => 'success',
                    'transaction_id' => 'TXN' . strtoupper(Str::random(12)),
                    'paid_at'        => (clone $bookingDate)->addMinutes(rand(1, 5)), // Thanh toán sau vài phút
                    'created_at'     => clone $bookingDate,
                ]);

                // Cộng điểm thưởng
                $user->increment('point', round($totalAmt / 10000));
            }
        }
    }
}
