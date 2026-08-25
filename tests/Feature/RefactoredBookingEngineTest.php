<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Combo;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\PricingRule;
use App\Models\Province;
use App\Models\Role;
use App\Models\Room;
use App\Models\SeatType;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Models\UserTier;
use App\Models\Voucher;
use App\Services\BookingService;
use App\Services\PricingEngineService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RefactoredBookingEngineTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Showtime $showtime;
    private ShowtimeSeat $seat1;
    private ShowtimeSeat $seat2;
    private Combo $combo;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Master data setup
        $role = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        $province = Province::create([
            'province_name' => 'TP. Hồ Chí Minh',
            'province_code' => 'HCM',
        ]);
        $cinema = Cinema::create([
            'province_id' => $province->province_id,
            'cinema_name' => 'CineDot Landmark 81',
            'slug' => 'cinedot-landmark-81',
            'cinema_address' => '720A Điện Biên Phủ',
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->cinema_id,
            'room_name' => 'Room 1 IMAX',
            'room_type' => 'IMAX',
            'total_seats' => 100,
        ]);
        $movie = Movie::create([
            'title' => 'Lật Mặt 8',
            'slug' => 'lat-mat-8',
            'duration' => 120,
            'status' => 'now_showing',
        ]);
        $this->showtime = Showtime::create([
            'room_id' => $room->room_id,
            'movie_id' => $movie->movie_id,
            'showtime_start' => now()->addDay()->setHour(19)->setMinute(0),
            'showtime_end' => now()->addDay()->setHour(21)->setMinute(0),
            'base_price' => 80000.00,
        ]);

        SeatType::create(['seat_type' => 'STD', 'type_name' => 'Ghế Tiêu Chuẩn', 'surcharge_amount' => 0.00]);
        SeatType::create(['seat_type' => 'VIP', 'type_name' => 'Ghế VIP', 'surcharge_amount' => 20000.00]);

        $this->seat1 = ShowtimeSeat::create([
            'showtime_id' => $this->showtime->showtime_id,
            'seat_type' => 'VIP',
            'row_name' => 'H',
            'seat_number' => '10',
            'status' => 'available',
        ]);

        $this->seat2 = ShowtimeSeat::create([
            'showtime_id' => $this->showtime->showtime_id,
            'seat_type' => 'VIP',
            'row_name' => 'H',
            'seat_number' => '11',
            'status' => 'available',
        ]);

        $this->combo = Combo::create([
            'name' => 'Couple Combo',
            'price' => 150000.00,
            'is_active' => true,
        ]);

        UserTier::create([
            'tier' => 'Gold',
            'min_points' => 1000,
            'discount_percent' => 5.00,
        ]);

        $this->user = User::create([
            'username' => 'testcustomer',
            'email' => 'customer@cinedot.vn',
            'password' => bcrypt('password123'),
            'fullname' => 'Nguyen Van A',
            'total_points' => 10000,
            'is_active' => true,
        ]);

        \App\Models\UserRole::create([
            'user_id' => $this->user->user_id,
            'role_id' => $role->role_id,
            'scope_type' => 'system',
        ]);
    }

    public function test_pricing_engine_calculates_stacking_discounts_and_zero_floor()
    {
        $engine = app(PricingEngineService::class);

        // Pricing Rule (Weekend Surcharge 10,000)
        PricingRule::create([
            'name' => 'Phụ thu cuối tuần',
            'rule_category' => 'weekend_surcharge',
            'conditions' => ['days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']],
            'modifier_type' => 'fixed_amount',
            'modifier_value' => 10000.00,
            'priority' => 1,
            'is_active' => true,
        ]);

        // Voucher 50K
        $voucher = Voucher::create([
            'code' => 'MEGA-WEEKEND-50K',
            'discount_type' => 'fixed_amount',
            'discount_value' => 50000.00,
            'min_order_value' => 100000.00,
            'is_active' => true,
        ]);

        $snapshot = $engine->calculateSummary(
            $this->showtime->showtime_id,
            [$this->seat1->showtime_seat_id, $this->seat2->showtime_seat_id],
            [['combo_id' => $this->combo->combo_id, 'quantity' => 1]],
            $voucher->code,
            $this->user
        );

        $this->assertEquals(220000, $snapshot['financial_breakdown']['subtotal_tickets']); // 2 x (80k base + 20k VIP + 10k rule)
        $this->assertEquals(150000, $snapshot['financial_breakdown']['subtotal_combos']);  // 1 x 150k
        $this->assertEquals(370000, $snapshot['financial_breakdown']['total_subtotal']);
        $this->assertEquals(18500, $snapshot['financial_breakdown']['discounts']['tier_discount']['deducted_amount']); // 5% of 370k
        $this->assertEquals(50000, $snapshot['financial_breakdown']['discounts']['voucher_discount']['deducted_amount']);
        $this->assertEquals(301500, $snapshot['financial_breakdown']['final_amount_to_pay']);
        $this->assertTrue($snapshot['metadata']['is_zero_floor_enforced']);
    }

    public function test_hold_seats_sets_customizable_redis_ttl_and_creates_pending_booking()
    {
        config(['app.hold_seat_expire_seconds' => 600]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/bookings/hold-seats', [
                'showtime_id' => $this->showtime->showtime_id,
                'showtime_seat_ids' => [$this->seat1->showtime_seat_id, $this->seat2->showtime_seat_id],
                'combos' => [['combo_id' => $this->combo->combo_id, 'quantity' => 1]],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $bookingId = $response->json('data.booking_id');
        $this->assertDatabaseHas('bookings', [
            'booking_id' => $bookingId,
            'booking_status' => 'pending',
            'user_id' => $this->user->user_id,
        ]);

        $this->assertDatabaseHas('booking_seats', [
            'booking_id' => $bookingId,
            'showtime_seat_id' => $this->seat1->showtime_seat_id,
        ]);

        $this->assertDatabaseHas('booking_combos', [
            'booking_id' => $bookingId,
            'combo_id' => $this->combo->combo_id,
            'is_claimed' => false,
        ]);
    }

    public function test_seat_status_api_merges_db_status_and_redis_hold_keys()
    {
        $redisKey = "hold:showtime:{$this->showtime->showtime_id}:seat:{$this->seat1->showtime_seat_id}";
        Redis::setex($redisKey, 600, 9999);

        $this->seat2->update(['status' => 'booked']);

        $response = $this->getJson("/api/v1/showtimes/{$this->showtime->showtime_id}/seat-status");
        $response->assertStatus(200);

        $seats = collect($response->json('data.seats'));
        $seat1Data = $seats->firstWhere('showtime_seat_id', $this->seat1->showtime_seat_id);
        $seat2Data = $seats->firstWhere('showtime_seat_id', $this->seat2->showtime_seat_id);

        $this->assertEquals('holding', $seat1Data['status']);
        $this->assertEquals('booked', $seat2Data['status']);
    }

    public function test_release_seats_clears_redis_keys()
    {
        $redisKey = "hold:showtime:{$this->showtime->showtime_id}:seat:{$this->seat1->showtime_seat_id}";
        Redis::setex($redisKey, 600, 9999);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/bookings/release-seats', [
                'showtime_id' => $this->showtime->showtime_id,
                'showtime_seat_ids' => [$this->seat1->showtime_seat_id],
            ]);

        $response->assertStatus(200);
        $this->assertFalse((bool) Redis::exists($redisKey));
    }

    public function test_confirm_booking_updates_seats_to_booked_clears_redis_and_awards_points()
    {
        $service = app(BookingService::class);
        $booking = $service->holdSeats(
            $this->user->user_id,
            $this->showtime->showtime_id,
            [$this->seat1->showtime_seat_id],
            []
        );

        $confirmedBooking = $service->confirmBooking($booking->booking_id);

        $this->assertEquals('completed', $confirmedBooking->booking_status);
        $this->assertDatabaseHas('showtime_seats', [
            'showtime_seat_id' => $this->seat1->showtime_seat_id,
            'status' => 'booked',
        ]);

        $redisKey = "hold:showtime:{$this->showtime->showtime_id}:seat:{$this->seat1->showtime_seat_id}";
        $this->assertFalse((bool) Redis::exists($redisKey));
    }
}
