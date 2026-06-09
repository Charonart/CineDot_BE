<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return ['user' => $user, 'token' => $token];
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();
        
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }
        
        $user->update(['last_login' => now()]);
        
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user)
    {
        $user->currentAccessToken()->delete();
    }
}
