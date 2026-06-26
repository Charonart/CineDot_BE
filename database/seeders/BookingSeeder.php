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

        // Seed điểm thưởng thành viên và các sự kiện mẫu
        foreach ($users as $u) {
            // 1. Thưởng chào mừng thành viên mới
            \App\Models\PointHistory::create([
                'user_id'    => $u->user_id,
                'booking_id' => null,
                'amount'     => 100,
                'action'     => 'earn_register',
                'created_at' => Carbon::now()->subDays(30),
            ]);
            $u->increment('point', 100);

            // 2. Thưởng sinh nhật (ngẫu nhiên)
            if (rand(0, 1)) {
                \App\Models\PointHistory::create([
                    'user_id'    => $u->user_id,
                    'booking_id' => null,
                    'amount'     => 50,
                    'action'     => 'earn_birthday',
                    'created_at' => Carbon::now()->subDays(15),
                ]);
                $u->increment('point', 50);
            }

            // 3. Quy đổi Voucher (ngẫu nhiên)
            if (rand(0, 1)) {
                \App\Models\PointHistory::create([
                    'user_id'    => $u->user_id,
                    'booking_id' => null,
                    'amount'     => -30,
                    'action'     => 'spend_voucher',
                    'created_at' => Carbon::now()->subDays(10),
                ]);
                $u->decrement('point', 30);
            }

            // 4. Quy đổi Combo (ngẫu nhiên)
            if (rand(0, 1)) {
                \App\Models\PointHistory::create([
                    'user_id'    => $u->user_id,
                    'booking_id' => null,
                    'amount'     => -20,
                    'action'     => 'spend_combo',
                    'created_at' => Carbon::now()->subDays(5),
                ]);
                $u->decrement('point', 20);
            }
        }

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

                // Cộng điểm thưởng & ghi nhận lịch sử
                $points = (int) round($totalAmt / 10000);
                if ($points > 0) {
                    $user->increment('point', $points);

                    \App\Models\PointHistory::create([
                        'user_id'    => $user->user_id,
                        'booking_id' => $booking->booking_id,
                        'amount'     => $points,
                        'action'     => 'earn_booking',
                        'created_at' => $bookingDate,
                    ]);
                }
            }
        }
    }
}
