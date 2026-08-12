<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function __construct(private PermissionService $permissionService)
    {
    }

    /**
     * Danh sách vai trò theo phạm vi của người dùng
     */
    public function index(string $userId)
    {
        $user = User::findOrFail($userId);

        $roles = UserRole::with(['role', 'cinema', 'province'])
            ->where('user_id', $user->user_id)
            ->get()
            ->map(function ($ur) {
                $scopeName = 'Toàn hệ thống';
                if ($ur->scope_type === 'cinema' && $ur->cinema) {
                    $scopeName = $ur->cinema->cinema_name;
                } elseif ($ur->scope_type === 'region' && $ur->province) {
                    $scopeName = $ur->province->province_name;
                }

                return [
                    'id'          => $ur->id,
                    'user_id'     => $ur->user_id,
                    'role_id'     => $ur->role_id,
                    'role_name'   => $ur->role?->name,
                    'scope_type'  => $ur->scope_type,
                    'scope_id'    => $ur->scope_id,
                    'scope_name'  => $scopeName,
                    'created_at'  => $ur->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $roles
        ]);
    }

    /**
     * Gán vai trò theo ngữ cảnh cho người dùng
     */
    public function store(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'role_id'    => 'required|exists:roles,role_id',
            'scope_type' => 'required|in:system,region,cinema',
            'scope_id'   => 'nullable|integer',
        ]);

        if ($validated['scope_type'] === 'cinema' && !empty($validated['scope_id'])) {
            $request->validate(['scope_id' => 'exists:cinemas,cinema_id']);
        } elseif ($validated['scope_type'] === 'region' && !empty($validated['scope_id'])) {
            $request->validate(['scope_id' => 'exists:provinces,province_id']);
        } elseif ($validated['scope_type'] === 'system') {
            $validated['scope_id'] = null;
        }

        $userRole = UserRole::updateOrCreate(
            [
                'user_id'    => $user->user_id,
                'role_id'    => $validated['role_id'],
                'scope_type' => $validated['scope_type'],
                'scope_id'   => $validated['scope_id'] ?? null,
            ]
        );

        $this->permissionService->clearPermissionsCache($user->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Gán vai trò ngữ cảnh thành công.',
            'data'    => $userRole->load(['role', 'cinema', 'province'])
        ], 201);
    }

    /**
     * Hủy vai trò ngữ cảnh của người dùng
     */
    public function destroy(string $userId, string $id)
    {
        $user = User::findOrFail($userId);
        $userRole = UserRole::where('user_id', $user->user_id)->where('id', $id)->firstOrFail();
        $userRole->delete();

        $this->permissionService->clearPermissionsCache($user->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Hủy vai trò ngữ cảnh thành công.'
        ]);
    }
}
