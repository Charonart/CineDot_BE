<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private PermissionService $permissionService)
    {
    }

    public function register(array $data)
    {
        if (!isset($data['role_id'])) {
            $customerRole = \App\Models\Role::where('name', 'customer')->first();
            $data['role_id'] = $customerRole ? $customerRole->role_id : 1;
        }

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        // Send registration event & verification email after HTTP response is flushed (< 50ms response time)
        dispatch(function () use ($user) {
            event(new Registered($user));
        })->afterResponse();

        $token = $user->createToken('auth_token')->plainTextToken;
        $permissions = $this->permissionService->getUserPermissions($user);

        return ['user' => $user, 'token' => $token, 'permissions' => $permissions];
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])
            ->orWhere('username', $data['email'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }

        $user->update(['last_login' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;
        $permissions = $this->permissionService->getUserPermissions($user);

        return ['user' => $user, 'token' => $token, 'permissions' => $permissions];
    }

    public function logout(User $user)
    {
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        $this->permissionService->clearPermissionsCache($user->user_id);
    }

    /**
     * Generate 6-digit OTP code & Token for password reset, cached 100% in Redis with 15-minute TTL.
     */
    public function forgotPassword(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Địa chỉ email không tồn tại trong hệ thống.'],
            ]);
        }

        // Generate 6-digit OTP code and password reset token
        $otp = (string) rand(100000, 999999);
        $token = Str::random(60);

        // Store OTP and Token in Redis with 15-minute TTL (900 seconds)
        Redis::setex("password_reset:otp:{$email}", 900, $otp);
        Redis::setex("password_reset:token:{$email}", 900, $token);

        // Send email after HTTP response is flushed (< 50ms response time)
        dispatch(function () use ($user, $token) {
            $user->notify(new \App\Notifications\CustomResetPassword($token));
        })->afterResponse();

        return [
            'otp' => $otp,
            'token' => $token,
        ];
    }

    /**
     * Verify OTP / Token from Redis, update password, and perform security cleanup.
     */
    public function resetPassword(array $data): bool
    {
        $email = $data['email'];
        $inputCode = $data['otp'] ?? $data['token'] ?? null;
        $password = $data['password'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Địa chỉ email không tồn tại trong hệ thống.'],
            ]);
        }

        $cachedOtp = Redis::get("password_reset:otp:{$email}");
        $cachedToken = Redis::get("password_reset:token:{$email}");

        $isValid = false;

        if (!empty($inputCode)) {
            if ($cachedOtp && (string) $inputCode === (string) $cachedOtp) {
                $isValid = true;
            } elseif ($cachedToken && (string) $inputCode === (string) $cachedToken) {
                $isValid = true;
            }
        }

        if (!$isValid) {
            throw ValidationException::withMessages([
                'otp' => ['Mã OTP hoặc Token khôi phục mật khẩu không hợp lệ hoặc đã hết hạn.'],
            ]);
        }

        // Update password
        $user->password = Hash::make($password);
        $user->save();

        // Security cleanup
        Redis::del("password_reset:otp:{$email}");
        Redis::del("password_reset:token:{$email}");
        $user->tokens()->delete();
        $this->permissionService->clearPermissionsCache($user->user_id);

        return true;
    }
}
