<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user()->load('province');
        
        return response()->json([
            'success' => true,
            'data'    => new UserResource($user)
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công',
            'data'    => new UserResource($user->load('province'))
        ]);
    }
}
