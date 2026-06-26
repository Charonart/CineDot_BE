<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voucher;
use App\Models\UserVoucher;
use App\Models\BookingVoucher;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\ScheduleSeat;
use App\Models\Payment;
use App\Models\Banner;
use App\Jobs\ProcessRefundJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvancedLoyaltyAndRefundTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test point exchange dynamic pricing based on membership tier.
     */
    public function test_point_exchange_dynamic_pricing()
    {
        $vipUser = User::where('username', 'minh_tran')->first(); // VIP (20% discount)
        $memberUser = User::where('username', 'linh_nguyen')->first(); // Member (0% discount)
        $voucher = Voucher::where('code', 'CINEDOT50')->first(); // 500 points cost

        $this->assertNotNull($vipUser);
        $this->assertNotNull($memberUser);
        $this->assertNotNull($voucher);

        // Set initial points
        $vipUser->update(['point' => 1000]);
        $memberUser->update(['point' => 1000]);

        // 1. VIP user exchange (expects 500 * 0.8 = 400 points cost)
        Sanctum::actingAs($vipUser);
        $response = $this->postJson('/api/v1/users/exchange-points', [
            'voucher_id' => $voucher->voucher_id
        ]);
        $response->assertStatus(200);
        $vipUser->refresh();
        $this->assertEquals(600, $vipUser->point); // 1000 - 400 = 600

        // Check user voucher ownership created
        $userV = UserVoucher::where('user_id', $vipUser->user_id)
            ->where('voucher_id', $voucher->voucher_id)
            ->first();
        $this->assertNotNull($userV);
        $this->assertFalse($userV->is_used);

        // 2. Member user exchange (expects 500 points cost)
        Sanctum::actingAs($memberUser);
        $response = $this->postJson('/api/v1/users/exchange-points', [
            'voucher_id' => $voucher->voucher_id
        ]);
        $response->assertStatus(200);
        $memberUser->refresh();
        $this->assertEquals(500, $memberUser->point); // 1000 - 500 = 500
    }

    /**
     * Test voucher stacking rules.
     */
    public function test_voucher_stacking_rules()
    {
        $user = User::where('username', 'minh_tran')->first();
        $voucher1 = Voucher::where('code', 'CINEDOT50')->first(); // ticket_discount
        $voucher2 = Voucher::where('code', 'FREEPOPOCORN')->first(); // gift (excludes CINEDOT50 code)
        
        // Grant vouchers via user_vouchers so they own them
        UserVoucher::create([
            'user_id' => $user->user_id,
            'voucher_id' => $voucher1->voucher_id,
            'is_used' => false
        ]);
        UserVoucher::create([
            'user_id' => $user->user_id,
            'voucher_id' => $voucher2->voucher_id,
            'is_used' => false
        ]);

        // Create a pending booking
        $booking = Booking::create([
            'user_id' => $user->user_id,
            'schedule_id' => 1,
            'total_amount' => 150000,
            'booking_status' => 'pending',
            'booking_code' => 'TEST_STACK_BOOKING',
        ]);

        Sanctum::actingAs($user);

        // Apply voucher 1 (Success)
        $response = $this->postJson("/api/v1/bookings/{$booking->booking_id}/apply-voucher", [
            'voucher_code' => $voucher1->code
        ]);
        $response->assertStatus(200);

        // Apply voucher 2 (Should fail because FREEPOPOCORN excludes CINEDOT50)
        $response = $this->postJson("/api/v1/bookings/{$booking->booking_id}/apply-voucher", [
            'voucher_code' => $voucher2->code
        ]);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => "Mã giảm giá này xung đột với mã 'CINEDOT50' đã áp dụng."
                ]
            ]);
    }

    /**
     * Test home banners public access and admin CRUD.
     */
    public function test_banner_management()
    {
        $admin = User::where('role', 'admin')->first();
        $customer = User::where('role', 'customer')->first();

        // 1. Public home banners (should return only active banners ordered by order)
        $response = $this->getJson('/api/v1/banners');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data'
            ]);
        
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
        // Active banners should not contain inactive ones
        foreach ($data as $banner) {
            $this->assertTrue($banner['isActive']);
        }

        // 2. Admin CRUD access control
        Sanctum::actingAs($customer);
        $this->postJson('/api/v1/admin/banners', [
            'title' => 'Test Banner',
            'image_url' => 'https://example.com/test.jpg'
        ])->assertStatus(403);

        Sanctum::actingAs($admin);
        $response = $this->postJson('/api/v1/admin/banners', [
            'title' => 'Test Banner Admin',
            'image_url' => 'https://example.com/test.jpg',
            'order' => 10,
            'is_active' => true
        ]);
        $response->assertStatus(201);
        $bannerId = $response->json('data.id');

        // Update banner
        $this->putJson("/api/v1/admin/banners/{$bannerId}", [
            'title' => 'Updated Banner Admin'
        ])->assertStatus(200);

        // Delete banner
        $this->deleteJson("/api/v1/admin/banners/{$bannerId}")
            ->assertStatus(200);
    }

    /**
     * Test booking cancellation: timing constraints, points check, and queue job.
     */
    public function test_booking_cancellation_workflows()
    {
        $user = User::where('username', 'minh_tran')->first();
        $user->update(['point' => 500]); // Ensure points balance

        // Create booking & payment
        $booking = Booking::create([
            'user_id' => $user->user_id,
            'schedule_id' => 1,
            'total_amount' => 100000, // generates 10 points
            'booking_status' => 'completed',
            'booking_code' => 'TEST_CANCEL_B1',
        ]);
        Payment::create([
            'booking_id' => $booking->booking_id,
            'amount' => 100000,
            'status' => 'success',
            'method' => 'vnpay',
            'transaction_id' => 'TRANS_CANCEL_1',
            'created_at' => now(),
        ]);

        // Mock schedule showtime: > 24 hours in the future
        $booking->schedule->update([
            'schedule_date' => now()->addDays(2)->format('Y-m-d'),
            'schedule_start' => '19:00:00'
        ]);

        // Seat relationship
        $scheduleSeat = ScheduleSeat::first();
        $scheduleSeat->update(['status' => 'booked']);
        BookingSeat::create([
            'booking_id' => $booking->booking_id,
            'schedule_seat_id' => $scheduleSeat->schedule_seat_id,
            'price_at_booking' => 100000
        ]);

        // Fake the Queue to assert dispatch
        Queue::fake();

        Sanctum::actingAs($user);

        // 1. Cancel > 24h (expects 100% refund, returns voucher, updates status to cancelling)
        $response = $this->postJson("/api/v1/bookings/{$booking->booking_id}/cancel");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'bookingStatus' => 'cancelling',
                    'refundPercentage' => 100,
                    'returnVoucher' => true
                ]
            ]);

        $booking->refresh();
        $this->assertEquals('cancelling', $booking->booking_status);

        // Verify ProcessRefundJob was dispatched
        Queue::assertPushed(ProcessRefundJob::class);

        // 2. Test Negative Point Dilemma:
        // Set booking back to completed, but set user points to 0 (so they spent the points earned)
        $booking->update(['booking_status' => 'completed']);
        $user->update(['point' => 5]); // Cần 10 điểm để thu hồi, chỉ có 5 điểm
        
        $response = $this->postJson("/api/v1/bookings/{$booking->booking_id}/cancel");
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => 'Không thể hủy vé vì bạn đã sử dụng số điểm thưởng tích lũy được từ giao dịch này.'
                ]
            ]);
    }

    /**
     * Test job processing.
     */
    public function test_process_refund_job_logic()
    {
        $user = User::where('username', 'minh_tran')->first();
        $user->update(['point' => 100]); // Ensure points balance

        $booking = Booking::create([
            'user_id' => $user->user_id,
            'schedule_id' => 1,
            'total_amount' => 100000, // generates 10 points
            'booking_status' => 'completed',
            'booking_code' => 'TEST_CANCEL_JOB_B2',
        ]);
        Payment::create([
            'booking_id' => $booking->booking_id,
            'amount' => 100000,
            'status' => 'success',
            'method' => 'vnpay',
            'transaction_id' => 'TRANS_CANCEL_2',
            'created_at' => now(),
        ]);

        $scheduleSeat = ScheduleSeat::first();
        $scheduleSeat->update(['status' => 'booked']);
        BookingSeat::create([
            'booking_id' => $booking->booking_id,
            'schedule_seat_id' => $scheduleSeat->schedule_seat_id,
            'price_at_booking' => 100000
        ]);

        // Run the refund job synchronously (100% refund, return voucher)
        $job = new ProcessRefundJob($booking->booking_id, 100, true);
        $job->handle();

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->booking_status);

        $scheduleSeat->refresh();
        $this->assertEquals('available', $scheduleSeat->status);

        $user->refresh();
        $this->assertEquals(90, $user->point); // 100 - 10 = 90
    }
}
