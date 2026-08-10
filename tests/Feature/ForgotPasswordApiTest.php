<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ForgotPasswordApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'username' => 'resetuser',
            'email' => 'resetuser@cinedot.vn',
            'password' => bcrypt('oldpassword123'),
            'fullname' => 'Reset User',
            'status' => 'active',
        ]);
    }

    public function test_forgot_password_generates_redis_otp_with_15min_ttl()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'resetuser@cinedot.vn',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $redisKey = "password_reset:otp:resetuser@cinedot.vn";
        $this->assertTrue((bool) Redis::exists($redisKey));

        $otp = Redis::get($redisKey);
        $this->assertEquals(6, strlen($otp));
        $this->assertTrue((int) $otp >= 100000);
    }

    public function test_reset_password_with_valid_otp_updates_password_and_clears_redis()
    {
        $otp = '654321';
        Redis::setex("password_reset:otp:resetuser@cinedot.vn", 900, $otp);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'resetuser@cinedot.vn',
            'otp' => $otp,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
        $this->assertFalse((bool) Redis::exists("password_reset:otp:resetuser@cinedot.vn"));
    }

    public function test_reset_password_rejects_invalid_otp()
    {
        Redis::setex("password_reset:otp:resetuser@cinedot.vn", 900, '654321');

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'resetuser@cinedot.vn',
            'otp' => '000000', // Invalid OTP
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }
}
