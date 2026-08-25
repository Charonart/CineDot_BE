<?php

namespace Tests\Feature;

use App\Events\RevenueUpdated;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Permission;
use App\Models\Province;
use App\Models\Role;
use App\Models\Room;
use App\Models\SeatType;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Models\UserRole;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RevenueReportAndRealtimeTest extends TestCase
{
    use DatabaseTransactions;

    private User $adminUser;
    private User $customerUser;
    private Cinema $cinema1;
    private Cinema $cinema2;
    private Movie $movie1;
    private Showtime $showtime1;
    private Showtime $showtime2;
    private ShowtimeSeat $stSeat1;
    private ShowtimeSeat $stSeat2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Permissions setup
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);

        Permission::firstOrCreate(['name' => 'view:report'], ['description' => 'View Revenue Report']);

        $this->adminUser = User::create([
            'username'     => 'superadmin_' . uniqid(),
            'email'        => 'admin_' . uniqid() . '@cinedot.vn',
            'password'     => bcrypt('secret123'),
            'fullname'     => 'Super Admin',
            'total_points' => 1000,
        ]);
        UserRole::create([
            'user_id'    => $this->adminUser->user_id,
            'role_id'    => $adminRole->role_id,
            'scope_type' => 'system',
        ]);

        $this->customerUser = User::create([
            'username'     => 'customer_' . uniqid(),
            'email'        => 'user_' . uniqid() . '@cinedot.vn',
            'password'     => bcrypt('secret123'),
            'fullname'     => 'Customer Test',
            'total_points' => 1000,
        ]);
        UserRole::create([
            'user_id'    => $this->customerUser->user_id,
            'role_id'    => $customerRole->role_id,
            'scope_type' => 'system',
        ]);

        // 2. Geography & Cinemas
        $province = Province::firstOrCreate(
            ['province_code' => 'HN'],
            ['province_name' => 'Hà Nội']
        );

        $this->cinema1 = Cinema::create([
            'province_id'    => $province->province_id,
            'cinema_name'    => 'CineDot Royal City ' . uniqid(),
            'slug'           => 'cinedot-royal-city-' . uniqid(),
            'cinema_address' => '72A Nguyễn Trãi',
        ]);

        $this->cinema2 = Cinema::create([
            'province_id'    => $province->province_id,
            'cinema_name'    => 'CineDot Ocean Park ' . uniqid(),
            'slug'           => 'cinedot-ocean-park-' . uniqid(),
            'cinema_address' => 'Gia Lâm',
        ]);

        $room1 = Room::create([
            'cinema_id'   => $this->cinema1->cinema_id,
            'room_name'   => 'Room 01',
            'room_type'   => '2D',
            'total_seats' => 50,
        ]);

        $room2 = Room::create([
            'cinema_id'   => $this->cinema2->cinema_id,
            'room_name'   => 'Room 02',
            'room_type'   => '3D',
            'total_seats' => 50,
        ]);

        $seatType = SeatType::firstOrCreate(
            ['seat_type' => 'standard'],
            [
                'type_name'        => 'Ghế Tiêu Chuẩn',
                'surcharge_amount' => 0,
                'color_code'       => '#64748B',
                'icon_name'        => 'seat',
            ]
        );

        $this->movie1 = Movie::create([
            'title'        => 'Dune: Part Two ' . uniqid(),
            'slug'         => 'dune-part-two-' . uniqid(),
            'duration'     => 166,
            'release_date' => '2026-03-01',
            'rating'       => 'PG-13',
            'is_active'    => true,
        ]);

        $this->showtime1 = Showtime::create([
            'room_id'        => $room1->room_id,
            'movie_id'       => $this->movie1->movie_id,
            'showtime_start' => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'showtime_end'   => Carbon::now()->addDays(2)->addMinutes(166)->format('Y-m-d H:i:s'),
            'status'         => 'active',
        ]);

        $this->showtime2 = Showtime::create([
            'room_id'        => $room2->room_id,
            'movie_id'       => $this->movie1->movie_id,
            'showtime_start' => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'showtime_end'   => Carbon::now()->addDays(2)->addMinutes(166)->format('Y-m-d H:i:s'),
            'status'         => 'active',
        ]);

        $this->stSeat1 = ShowtimeSeat::create([
            'showtime_id' => $this->showtime1->showtime_id,
            'seat_type'   => $seatType->seat_type,
            'row_name'    => 'A',
            'seat_number' => '1',
            'status'      => 'available',
        ]);

        $this->stSeat2 = ShowtimeSeat::create([
            'showtime_id' => $this->showtime1->showtime_id,
            'seat_type'   => $seatType->seat_type,
            'row_name'    => 'A',
            'seat_number' => '2',
            'status'      => 'available',
        ]);
    }

    public function test_revenue_report_default_returns_summary_and_backward_compatible_fields()
    {
        Sanctum::actingAs($this->adminUser);

        // Seed 1 completed booking
        $booking = Booking::create([
            'user_id'        => $this->customerUser->user_id,
            'showtime_id'    => $this->showtime1->showtime_id,
            'booking_code'   => 'BK-' . uniqid(),
            'final_amount'   => 200000,
            'booking_status' => 'completed',
        ]);
        DB::table('bookings')->where('booking_id', $booking->booking_id)->update(['created_at' => '2026-08-18 10:00:00']);

        BookingSeat::create([
            'booking_id'       => $booking->booking_id,
            'showtime_seat_id' => $this->stSeat1->showtime_seat_id,
            'price'            => 100000,
        ]);
        BookingSeat::create([
            'booking_id'       => $booking->booking_id,
            'showtime_seat_id' => $this->stSeat2->showtime_seat_id,
            'price'            => 100000,
        ]);

        $response = $this->getJson("/api/v1/admin/reports/revenue?cinema_id={$this->cinema1->cinema_id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'total_revenue'      => 200000,
                    'total_tickets_sold' => 2,
                    'cinema_name'        => $this->cinema1->cinema_name,
                    'summary'            => [
                        'total_revenue'      => 200000,
                        'total_tickets_sold' => 2,
                    ],
                ]
            ]);
    }

    public function test_revenue_report_with_group_by_day_and_zero_filled_missing_dates()
    {
        Sanctum::actingAs($this->adminUser);

        // Booking on 2026-08-17
        $b1 = Booking::create([
            'user_id'        => $this->customerUser->user_id,
            'showtime_id'    => $this->showtime1->showtime_id,
            'booking_code'   => 'BK-AUG17-' . uniqid(),
            'final_amount'   => 150000,
            'booking_status' => 'completed',
        ]);
        DB::table('bookings')->where('booking_id', $b1->booking_id)->update(['created_at' => '2026-08-17 14:00:00']);

        BookingSeat::create([
            'booking_id'       => $b1->booking_id,
            'showtime_seat_id' => $this->stSeat1->showtime_seat_id,
            'price'            => 150000,
        ]);

        // Booking on 2026-08-19
        $b2 = Booking::create([
            'user_id'        => $this->customerUser->user_id,
            'showtime_id'    => $this->showtime1->showtime_id,
            'booking_code'   => 'BK-AUG19-' . uniqid(),
            'final_amount'   => 300000,
            'booking_status' => 'paid',
        ]);
        DB::table('bookings')->where('booking_id', $b2->booking_id)->update(['created_at' => '2026-08-19 18:00:00']);

        BookingSeat::create([
            'booking_id'       => $b2->booking_id,
            'showtime_seat_id' => $this->stSeat2->showtime_seat_id,
            'price'            => 300000,
        ]);

        $response = $this->getJson("/api/v1/admin/reports/revenue?cinema_id={$this->cinema1->cinema_id}&start_date=2026-08-17&end_date=2026-08-20&group_by=day");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(450000, $data['total_revenue']);
        $this->assertEquals(2, $data['total_tickets_sold']);

        // Must contain exactly 4 days: 17, 18, 19, 20
        $this->assertCount(4, $data['chart']);

        // Check 17th
        $this->assertEquals('2026-08-17', $data['chart'][0]['date']);
        $this->assertEquals(150000, $data['chart'][0]['revenue']);
        $this->assertEquals(1, $data['chart'][0]['tickets_sold']);

        // Check 18th (missing in DB -> filled as 0)
        $this->assertEquals('2026-08-18', $data['chart'][1]['date']);
        $this->assertEquals(0, $data['chart'][1]['revenue']);
        $this->assertEquals(0, $data['chart'][1]['tickets_sold']);

        // Check 19th
        $this->assertEquals('2026-08-19', $data['chart'][2]['date']);
        $this->assertEquals(300000, $data['chart'][2]['revenue']);
        $this->assertEquals(1, $data['chart'][2]['tickets_sold']);

        // Check 20th (missing in DB -> filled as 0)
        $this->assertEquals('2026-08-20', $data['chart'][3]['date']);
        $this->assertEquals(0, $data['chart'][3]['revenue']);
        $this->assertEquals(0, $data['chart'][3]['tickets_sold']);
    }

    public function test_revenue_report_validation_fails_on_invalid_dates_or_group_by()
    {
        Sanctum::actingAs($this->adminUser);

        // start_date > end_date
        $res1 = $this->getJson('/api/v1/admin/reports/revenue?start_date=2026-08-25&end_date=2026-08-20');
        $res1->assertStatus(422);

        // invalid group_by
        $res2 = $this->getJson('/api/v1/admin/reports/revenue?group_by=invalid_group');
        $res2->assertStatus(422);
    }

    public function test_revenue_report_data_scoping_per_cinema()
    {
        // Create Admin user scoped only to Cinema 1
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $scopedAdmin = User::create([
            'username'     => 'admin_c1_' . uniqid(),
            'email'        => 'admin_c1_' . uniqid() . '@cinedot.vn',
            'password'     => bcrypt('secret123'),
            'fullname'     => 'Scoped Admin Cinema 1',
            'total_points' => 1000,
        ]);
        UserRole::create([
            'user_id'    => $scopedAdmin->user_id,
            'role_id'    => $adminRole->role_id,
            'scope_type' => 'cinema',
            'scope_id'   => $this->cinema1->cinema_id,
        ]);

        // Booking on Cinema 1
        $b1 = Booking::create([
            'user_id'        => $this->customerUser->user_id,
            'showtime_id'    => $this->showtime1->showtime_id,
            'booking_code'   => 'BK-C1-' . uniqid(),
            'final_amount'   => 100000,
            'booking_status' => 'completed',
        ]);
        DB::table('bookings')->where('booking_id', $b1->booking_id)->update(['created_at' => '2026-08-18 10:00:00']);

        // Booking on Cinema 2
        $b2 = Booking::create([
            'user_id'        => $this->customerUser->user_id,
            'showtime_id'    => $this->showtime2->showtime_id,
            'booking_code'   => 'BK-C2-' . uniqid(),
            'final_amount'   => 200000,
            'booking_status' => 'completed',
        ]);
        DB::table('bookings')->where('booking_id', $b2->booking_id)->update(['created_at' => '2026-08-18 10:00:00']);

        Sanctum::actingAs($scopedAdmin);

        $response = $this->getJson('/api/v1/admin/reports/revenue?start_date=2026-08-18&end_date=2026-08-18');
        $response->assertStatus(200);

        // Scoped Admin should only see 100,000 VND from Cinema 1
        $this->assertEquals(100000, $response->json('data.total_revenue'));
    }

    public function test_confirm_booking_dispatches_revenue_updated_event()
    {
        Event::fake([RevenueUpdated::class]);

        $booking = Booking::create([
            'user_id'        => $this->customerUser->user_id,
            'showtime_id'    => $this->showtime1->showtime_id,
            'booking_code'   => 'BK-CONFIRM-' . uniqid(),
            'final_amount'   => 250000,
            'booking_status' => 'pending',
            'created_at'     => now(),
        ]);

        $bookingService = app(BookingService::class);
        $bookingService->confirmBooking($booking->booking_id);

        Event::assertDispatched(RevenueUpdated::class, function ($event) use ($booking) {
            return $event->bookingId === $booking->booking_id
                && $event->reason === 'payment_completed'
                && $event->cinemaId === $this->cinema1->cinema_id
                && $event->movieId === $this->movie1->movie_id;
        });
    }

    public function test_user_cancel_booking_dispatches_revenue_updated_event()
    {
        Event::fake([RevenueUpdated::class]);

        $booking = Booking::create([
            'user_id'        => $this->customerUser->user_id,
            'showtime_id'    => $this->showtime1->showtime_id,
            'booking_code'   => 'BK-CANCEL-' . uniqid(),
            'final_amount'   => 200000,
            'booking_status' => 'completed',
            'created_at'     => now(),
        ]);

        Sanctum::actingAs($this->customerUser);

        $response = $this->postJson("/api/v1/bookings/{$booking->booking_id}/cancel");
        $response->assertStatus(200);

        Event::assertDispatched(RevenueUpdated::class, function ($event) use ($booking) {
            return $event->bookingId === $booking->booking_id
                && $event->reason === 'booking_cancelled';
        });
    }

    public function test_admin_refund_booking_dispatches_revenue_updated_event()
    {
        Event::fake([RevenueUpdated::class]);

        $booking = Booking::create([
            'user_id'        => $this->customerUser->user_id,
            'showtime_id'    => $this->showtime1->showtime_id,
            'booking_code'   => 'BK-REFUND-' . uniqid(),
            'final_amount'   => 200000,
            'booking_status' => 'completed',
            'created_at'     => now(),
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/v1/admin/bookings/{$booking->booking_id}/refund", [
            'reason' => 'Sự cố phòng chiếu phim',
        ]);
        $response->assertStatus(200);

        Event::assertDispatched(RevenueUpdated::class, function ($event) use ($booking) {
            return $event->bookingId === $booking->booking_id
                && $event->reason === 'booking_refunded';
        });
    }
}
