<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\PointHistory;
use App\Models\BookingSeat;
use App\Models\ScheduleSeat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoyaltyAndPaymentAdminTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test GET /api/v1/users/points-history returns the correct points list.
     */
    public function test_user_can_view_points_history()
    {
        $user = User::where('email', 'minh.tran@gmail.com')->first();
        $this->assertNotNull($user);

        // Access unauthorized returns 401
        $response = $this->getJson('/api/v1/users/points-history');
        $response->assertStatus(401);

        // Access authorized
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/users/points-history');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'currentPoints',
                    'page',
                    'results',
                    'totalPages',
                    'totalResults'
                ]
            ]);
    }

    /**
     * Test GET /api/v1/admin/payments.
     */
    public function test_admin_can_list_payments()
    {
        $admin = User::where('email', 'admin@cinedot.vn')->first();
        $user = User::where('email', 'minh.tran@gmail.com')->first();

        // Guest returns 401
        $response = $this->getJson('/api/v1/admin/payments');
        $response->assertStatus(401);

        // Customer returns 403 (unauthorized admin role check)
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/admin/payments');
        $response->assertStatus(403);

        // Admin returns 200
        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/v1/admin/payments');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'page',
                    'results',
                    'totalPages',
                    'totalResults'
                ]
            ]);
    }

    /**
     * Test refund logic.
     */
    public function test_admin_can_refund_payment_and_revert_points()
    {
        $admin = User::where('email', 'admin@cinedot.vn')->first();
        $user = User::where('email', 'minh.tran@gmail.com')->first();

        // Find or create a successful payment
        $payment = Payment::where('status', 'success')->first();
        if (!$payment) {
            // Seed a payment if none exists in testing DB state
            $booking = Booking::create([
                'user_id' => $user->user_id,
                'schedule_id' => 1,
                'total_amount' => 120000,
                'booking_status' => 'completed',
                'booking_code' => 'TEST_REFUND_BOOKING',
            ]);
            $payment = Payment::create([
                'booking_id' => $booking->booking_id,
                'amount' => 120000,
                'status' => 'success',
                'method' => 'vnpay',
                'transaction_id' => 'TRANS_TEST_123',
            ]);
        } else {
            $booking = $payment->booking;
        }

        // Setup user point & booking_seats for refund testing
        $initialUserPoints = $user->point;
        $booking->update([
            'user_id' => $user->user_id,
            'total_amount' => 100000, // 10 points
            'booking_status' => 'completed'
        ]);
        $user->update(['point' => $initialUserPoints + 10]);

        // Create seat relationships to verify status release
        $scheduleSeat = ScheduleSeat::first();
        $scheduleSeat->update(['status' => 'booked']);
        BookingSeat::create([
            'booking_id' => $booking->booking_id,
            'schedule_seat_id' => $scheduleSeat->schedule_seat_id,
            'price_at_booking' => 100000,
        ]);

        // Admin invokes refund
        Sanctum::actingAs($admin);
        $response = $this->postJson("/api/v1/admin/payments/{$payment->payment_id}/refund");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Assert payment is refunded
        $payment->refresh();
        $this->assertEquals('refunded', $payment->status);

        // Assert booking is cancelled
        $booking->refresh();
        $this->assertEquals('cancelled', $booking->booking_status);

        // Assert user point is decremented
        $user->refresh();
        $this->assertEquals($initialUserPoints, $user->point);

        // Assert seat is available
        $scheduleSeat->refresh();
        $this->assertEquals('available', $scheduleSeat->status);

        // Assert point history created
        $history = PointHistory::where('booking_id', $booking->booking_id)
            ->where('action', 'deduct_refund')
            ->first();
        $this->assertNotNull($history);
        $this->assertEquals(-10, $history->amount);
    }
}
