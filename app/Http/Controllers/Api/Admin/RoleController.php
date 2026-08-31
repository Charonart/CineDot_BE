<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Danh sách tất cả vai trò trong hệ thống kèm số lượng user và permission_ids
     * GET /api/v1/admin/roles
     */
    public function index()
    {
        $roles = Role::with(['permissions'])
            ->withCount(['users'])
            ->orderBy('role_id', 'asc')
            ->get()
            ->map(function ($role) {
                return [
                    'id'               => $role->role_id,
                    'role_id'          => $role->role_id,
                    'name'             => $role->name,
                    'description'      => $role->description,
                    'users_count'      => (int) $role->users_count,
                    'permissions_count'=> $role->permissions->count(),
                    'permission_ids'   => $role->permissions->pluck('permission_id')->toArray(),
                    'permission_names' => $role->permissions->pluck('name')->toArray(),
                    'is_system'        => in_array(strtolower($role->name), ['admin', 'staff', 'customer', 'cinema_manager', 'ticket_staff', 'fnb_staff', 'marketing', 'accountant']),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $roles,
            'meta'    => [
                'current_page' => 1,
                'last_page'    => 1,
                'per_page'     => $roles->count(),
                'total'        => $roles->count(),
            ]
        ]);
    }

    /**
     * Tạo vai trò mới
     * POST /api/v1/admin/roles
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:50|unique:roles,name',
            'description'    => 'nullable|string|max:255',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,permission_id',
        ]);

        $role = Role::create([
            'name'        => strtolower(trim($validated['name'])),
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tạo vai trò mới thành công.',
            'data'    => [
                'id'               => $role->role_id,
                'role_id'          => $role->role_id,
                'name'             => $role->name,
                'description'      => $role->description,
                'users_count'      => 0,
                'permissions_count'=> $role->permissions()->count(),
                'permission_ids'   => $role->permissions()->pluck('permissions.permission_id')->toArray(),
                'is_system'        => false,
            ],
        ], 201);
    }

    /**
     * Chi tiết vai trò kèm danh sách quyền
     * GET /api/v1/admin/roles/{id}
     */
    public function show($id)
    {
        $role = Role::with('permissions')->withCount('users')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $role->role_id,
                'role_id'          => $role->role_id,
                'name'             => $role->name,
                'description'      => $role->description,
                'users_count'      => (int) $role->users_count,
                'permissions_count'=> $role->permissions->count(),
                'permission_ids'   => $role->permissions->pluck('permission_id')->toArray(),
                'permissions'      => $role->permissions,
                'is_system'        => in_array(strtolower($role->name), ['admin', 'staff', 'customer', 'cinema_manager', 'ticket_staff', 'fnb_staff', 'marketing', 'accountant']),
            ],
        ]);
    }

    /**
     * Cập nhật thông tin vai trò
     * PUT /api/v1/admin/roles/{id}
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'sometimes|required|string|max:50|unique:roles,name,' . $role->role_id . ',role_id',
            'description'    => 'nullable|string|max:255',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,permission_id',
        ]);

        if (isset($validated['name'])) {
            $role->name = strtolower(trim($validated['name']));
        }
        if (isset($validated['description'])) {
            $role->description = $validated['description'];
        }
        $role->save();

        if (isset($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
            app(\App\Services\PermissionService::class)->clearPermissionsCache();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật vai trò thành công.',
            'data'    => [
                'id'               => $role->role_id,
                'role_id'          => $role->role_id,
                'name'             => $role->name,
                'description'      => $role->description,
                'users_count'      => (int) $role->users()->count(),
                'permissions_count'=> $role->permissions()->count(),
                'permission_ids'   => $role->permissions()->pluck('permissions.permission_id')->toArray(),
                'is_system'        => in_array(strtolower($role->name), ['admin', 'staff', 'customer', 'cinema_manager', 'ticket_staff', 'fnb_staff', 'marketing', 'accountant']),
            ],
        ]);
    }

    /**
     * Đồng bộ ma trận quyền của vai trò
     * PUT /api/v1/admin/roles/{id}/permissions
     */
    public function syncPermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permission_ids'   => 'required|array',
            'permission_ids.*' => 'exists:permissions,permission_id',
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        // Xóa cache quyền
        app(\App\Services\PermissionService::class)->clearPermissionsCache();

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$role->permissions()->count()} quyền cho vai trò \"{$role->name}\".",
            'data'    => [
                'role_id'          => $role->role_id,
                'permissions_count'=> $role->permissions()->count(),
                'permission_ids'   => $role->permissions()->pluck('permissions.permission_id')->toArray(),
            ]
        ]);
    }

    /**
     * Xóa vai trò tùy biến
     * DELETE /api/v1/admin/roles/{id}
     */
    public function destroy($id)
    {
        $role = Role::withCount('users')->findOrFail($id);

        if (in_array(strtolower($role->name), ['admin', 'staff', 'customer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa các vai trò hệ thống mặc định (Admin, Staff, Customer).',
            ], 422);
        }

        if ($role->users_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xóa vai trò này vì đang có {$role->users_count} người dùng được gán vai trò này.",
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        app(\App\Services\PermissionService::class)->clearPermissionsCache();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vai trò thành công.',
        ]);
    }
}
