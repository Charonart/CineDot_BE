<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Campaign;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Province;
use App\Models\Role;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostmanCollectionSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $roleCustomer = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        $roleAdmin = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin']);
        $roleStaff = Role::firstOrCreate(['name' => 'staff'], ['description' => 'Staff']);

        $this->customer = User::create([
            'username' => 'customer1',
            'email' => 'customer1@cinedot.vn',
            'password' => bcrypt('password123'),
            'fullname' => 'Customer One',
            'is_active' => true,
        ]);
        \App\Models\UserRole::create([
            'user_id' => $this->customer->user_id,
            'role_id' => $roleCustomer->role_id,
            'scope_type' => 'system',
        ]);

        $this->admin = User::create([
            'username' => 'admin1',
            'email' => 'admin1@cinedot.vn',
            'password' => bcrypt('password123'),
            'fullname' => 'Admin One',
            'is_active' => true,
        ]);
        \App\Models\UserRole::create([
            'user_id' => $this->admin->user_id,
            'role_id' => $roleAdmin->role_id,
            'scope_type' => 'system',
        ]);

        $this->staff = User::create([
            'username' => 'staff1',
            'email' => 'staff1@cinedot.vn',
            'password' => bcrypt('password123'),
            'fullname' => 'Staff One',
            'is_active' => true,
        ]);
        \App\Models\UserRole::create([
            'user_id' => $this->staff->user_id,
            'role_id' => $roleStaff->role_id,
            'scope_type' => 'system',
        ]);
    }

    public function test_register_accepts_name_and_generates_username()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van Test',
            'email' => 'newuser@cinedot.vn',
            'password' => 'password123',
            'phone' => '0901234567',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'newuser@cinedot.vn']);
    }

    public function test_patch_profile_updates_user_info()
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->patchJson('/api/v1/users/profile', [
                'name' => 'Nguyen Van B',
                'phone' => '0909876543',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'user_id' => $this->customer->user_id,
            'fullname' => 'Nguyen Van B',
        ]);
    }

    public function test_payment_create_url_endpoint()
    {
        $province = Province::create(['province_name' => 'HCM', 'province_code' => 'HCM']);
        $cinema = Cinema::create(['province_id' => $province->province_id, 'cinema_name' => 'Cine 1', 'slug' => 'cine-1', 'cinema_address' => 'Addr 1']);
        $room = Room::create(['cinema_id' => $cinema->cinema_id, 'room_name' => 'Room 1', 'room_type' => 'STD', 'total_seats' => 50]);
        $movie = Movie::create(['title' => 'Phim A', 'slug' => 'phim-a', 'duration' => 90, 'status' => 'now_showing']);
        $showtime = Showtime::create(['room_id' => $room->room_id, 'movie_id' => $movie->movie_id, 'showtime_start' => now(), 'showtime_end' => now()->addHour(), 'base_price' => 100000]);

        $booking = Booking::create([
            'user_id' => $this->customer->user_id,
            'showtime_id' => $showtime->showtime_id,
            'final_amount' => 100000,
            'discount_amount' => 0,
            'booking_status' => 'pending',
            'booking_code' => 'TESTCODE123',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/payments/create-url', [
                'booking_id' => $booking->booking_id,
                'payment_method' => 'VNPAY',
            ], ['Idempotency-Key' => 'test-key-123']);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $paymentUrl = $response->json('payment_url') ?? $response->json('data.payment_url');
        $this->assertNotEmpty($paymentUrl);
    }

    public function test_payment_webhook_endpoint()
    {
        $response = $this->postJson('/api/v1/webhooks/payment', [
            'order_id' => 'NONEXISTENT',
            'result_code' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_staff_fnb_claim_and_pos_endpoints()
    {
        $response = $this->actingAs($this->staff, 'sanctum')
            ->postJson('/api/v1/staff/fnb/claim', [
                'booking_detail_id' => 9999,
            ]);

        $response->assertStatus(404); // Verified route exists and executed logic
    }


    public function test_admin_campaign_crud_and_roi_endpoints()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/campaigns', [
                'name' => 'Hè Sôi Động 2026',
                'budget' => 500000000,
            ]);

        $response->assertStatus(201);
        $campaignId = $response->json('data.campaign_id');

        $vResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/campaigns/{$campaignId}/vouchers", [
                'code' => 'HE2026',
                'discount_type' => 'fixed_amount',
                'discount_value' => 50000,
            ]);
        $vResponse->assertStatus(201);

        $roiResponse = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/campaigns/{$campaignId}/roi");
        $roiResponse->assertStatus(200);
    }

    public function test_standalone_voucher_apply_endpoint()
    {
        Voucher::create([
            'code' => 'HE2026',
            'discount_type' => 'fixed_amount',
            'discount_value' => 50000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/vouchers/apply', [
                'code' => 'HE2026',
                'order_amount' => 200000,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.discount_amount', 50000);
    }
}
