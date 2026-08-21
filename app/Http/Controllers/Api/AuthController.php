<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private PermissionService $permissionService
    ) {
    }

    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công',
            'data'    => [
                'user'        => new UserResource($result['user']),
                'token'       => $result['token'],
                'permissions' => $result['permissions'],
            ]
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data'    => [
                'user'        => new UserResource($result['user']),
                'token'       => $result['token'],
                'permissions' => $result['permissions'],
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?: $request->user();
        if ($user) {
            $this->authService->logout($user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công'
        ]);
    }

    public function me(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?: $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $permissions = $this->permissionService->getUserPermissions($user);

        return response()->json([
            'success' => true,
            'data'    => [
                'user'        => new UserResource($user->load('role', 'province')),
                'permissions' => $permissions,
            ]
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $this->authService->forgotPassword($request->email);

        return response()->json([
            'success' => true,
            'message' => 'Mã OTP / Link khôi phục mật khẩu đã được gửi vào email của bạn.'
        ]);
    }



    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'password'              => 'required|min:6|confirmed',
            'otp'                   => 'required_without:token|nullable|string',
            'token'                 => 'required_without:otp|nullable|string',
        ]);

        $this->authService->resetPassword($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Mật khẩu đã được đặt lại thành công.'
        ]);
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = \App\Models\User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        return redirect()->away($frontendUrl . '/login?verified=1');
    }

    public function verifyEmailResend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['success' => false, 'message' => 'Email đã được xác thực.'], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['success' => true, 'message' => 'Email xác thực đã được gửi lại.']);
    }
}
