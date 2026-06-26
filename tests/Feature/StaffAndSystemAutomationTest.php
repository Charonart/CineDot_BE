<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\BookingSeat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Carbon\Carbon;

class StaffAndSystemAutomationTest extends TestCase
{
    use DatabaseTransactions;

    protected $redisData = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->redisData = [];

        // Mock Redis facade to avoid requiring a running Redis instance during tests
        Redis::shouldReceive('set')
            ->andReturnUsing(function ($key, $value, ...$args) {
                if (in_array('NX', $args) && isset($this->redisData[$key])) {
                    return false;
                }
                $this->redisData[$key] = $value;
                return true;
            });

        Redis::shouldReceive('setex')
            ->andReturnUsing(function ($key, $ttl, $value) {
                $this->redisData[$key] = $value;
                return true;
            });

        Redis::shouldReceive('exists')
            ->andReturnUsing(function ($key) {
                return isset($this->redisData[$key]);
            });

        Redis::shouldReceive('del')
            ->andReturnUsing(function ($key) {
                if (isset($this->redisData[$key])) {
                    unset($this->redisData[$key]);
                    return 1;
                }
                return 0;
            });

        Redis::shouldReceive('flushall')
            ->andReturnUsing(function () {
                $this->redisData = [];
                return true;
            });
    }

    /**
     * Test staff check-in validation rules and success paths.
     */
    public function test_staff_check_in_permissions_and_validations()
    {
        $admin = User::where('role', 'admin')->first();
        $staff = User::where('role', 'staff')->first();
        $customer = User::where('role', 'customer')->first();

        // Ensure we have a staff user and they are assigned to cinema ID 1
        $this->assertNotNull($staff);
        $staff->update(['cinema_id' => 1]);

        // Create a completed booking for schedule in cinema ID 1
        $schedule = Schedule::whereHas('room', function ($q) {
            $q->where('cinema_id', 1);
        })->first();

        $this->assertNotNull($schedule);

        // Set showtime start time to 15 minutes in the future (inside the 45-min window)
        $schedule->update([
            'schedule_date' => now()->format('Y-m-d'),
            'schedule_start' => now()->addMinutes(15)->format('H:i:s')
        ]);

        $booking = Booking::create([
            'user_id' => $customer->user_id,
            'schedule_id' => $schedule->schedule_id,
            'total_amount' => 100000,
            'booking_status' => 'completed',
            'booking_code' => 'CHECKIN_TEST_CODE',
        ]);

        // 1. Customer cannot check in (403 Forbidden)
        Sanctum::actingAs($customer);
        $response = $this->postJson("/api/v1/staff/bookings/{$booking->booking_code}/checkin");
        $response->assertStatus(403);

        // 2. Staff from another cinema cannot check in (403 Forbidden)
        $staff->update(['cinema_id' => 2]); // Change staff to cinema 2
        Sanctum::actingAs($staff);
        $response = $this->postJson("/api/v1/staff/bookings/{$booking->booking_code}/checkin");
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => 'Nhân viên không được phép soát vé của cụm rạp khác.'
                ]
            ]);

        // Reassign staff to cinema 1
        $staff->update(['cinema_id' => 1]);

        // 3. Check in outside the time window (e.g. movie starts in 2 hours -> outside window)
        $schedule->update([
            'schedule_start' => now()->addHours(2)->format('H:i:s')
        ]);
        $response = $this->postJson("/api/v1/staff/bookings/{$booking->booking_code}/checkin");
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => 'Thời gian soát vé không hợp lệ. Chỉ cho phép soát vé từ 45 phút trước giờ chiếu đến 30 phút sau khi phim bắt đầu.'
                ]
            ]);

        // Reset showtime to within window
        $schedule->update([
            'schedule_start' => now()->addMinutes(10)->format('H:i:s')
        ]);

        // 4. Booking not completed (e.g. pending status) (400 Bad Request)
        $booking->update(['booking_status' => 'pending']);
        $response = $this->postJson("/api/v1/staff/bookings/{$booking->booking_code}/checkin");
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => 'Chỉ có thể soát vé cho đơn hàng đã hoàn thành thanh toán (completed).'
                ]
            ]);

        $booking->update(['booking_status' => 'completed']);

        // 5. Successful check-in by authorized staff
        $response = $this->postJson("/api/v1/staff/bookings/{$booking->booking_code}/checkin");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'bookingCode' => $booking->booking_code,
                    'checkedInBy' => $staff->fullname,
                ]
            ]);

        $booking->refresh();
        $this->assertNotNull($booking->checked_in_at);
        $this->assertEquals($staff->user_id, $booking->checked_in_by);

        // 6. Double check-in throws 400 Bad Request
        $response = $this->postJson("/api/v1/staff/bookings/{$booking->booking_code}/checkin");
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test database fallback for seat map display when Redis key is lost.
     */
    public function test_seat_map_database_fallback()
    {
        $customer = User::where('role', 'customer')->first();
        $schedule = Schedule::first();
        $seat = ScheduleSeat::where('schedule_id', $schedule->schedule_id)->first();

        // Create a pending booking
        $booking = Booking::create([
            'user_id' => $customer->user_id,
            'schedule_id' => $schedule->schedule_id,
            'total_amount' => 50000,
            'booking_status' => 'pending',
            'booking_code' => 'FALLBACK_PENDING_CODE',
        ]);

        BookingSeat::create([
            'booking_id' => $booking->booking_id,
            'schedule_seat_id' => $seat->schedule_seat_id,
        ]);

        // Redis is completely empty (simulating a Redis crash/flush)
        Redis::flushall();

        // Request seat map
        Sanctum::actingAs($customer);
        $response = $this->getJson("/api/v1/showtimes/{$schedule->schedule_id}/seats");
        $response->assertStatus(200);

        // Check that the seat is displayed as 'held' even though Redis has no record of it
        $seatsData = $response->json('data');
        $seatInResponse = collect($seatsData)->firstWhere('scheduleSeatId', $seat->schedule_seat_id);

        $this->assertNotNull($seatInResponse);
        $this->assertEquals('held', $seatInResponse['status']);
    }

    /**
     * Test automatic cancellation of expired bookings and seat release via event-driven design.
     */
    public function test_expired_bookings_cancellation_and_seat_release()
    {
        $customer = User::where('role', 'customer')->first();
        $schedule = Schedule::first();
        $seat = ScheduleSeat::where('schedule_id', $schedule->schedule_id)->first();

        // Create a pending booking created 11 minutes ago
        $booking = Booking::create([
            'user_id' => $customer->user_id,
            'schedule_id' => $schedule->schedule_id,
            'total_amount' => 50000,
            'booking_status' => 'pending',
            'booking_code' => 'EXPIRED_BOOKING_CODE',
        ]);
        $booking->created_at = now()->subMinutes(11);
        $booking->updated_at = now()->subMinutes(11);
        $booking->save(['timestamps' => false]);

        BookingSeat::create([
            'booking_id' => $booking->booking_id,
            'schedule_seat_id' => $seat->schedule_seat_id,
        ]);

        // Put seat on hold in Redis
        $redisKey = "hold:schedule:{$schedule->schedule_id}:seat:{$seat->schedule_seat_id}";
        Redis::set($redisKey, $booking->booking_id);

        $this->assertTrue(Redis::exists($redisKey));

        // Execute the console command
        $this->artisan('bookings:cancel-expired')
            ->assertExitCode(0);

        // Assert booking is cancelled in DB
        $booking->refresh();
        $this->assertEquals('cancelled', $booking->booking_status);

        // Assert the Redis key is deleted (seats released)
        $this->assertFalse(Redis::exists($redisKey));
    }
}
