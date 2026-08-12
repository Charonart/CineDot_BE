<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * AD-12: Danh sách người dùng hệ thống (có filter & phân trang)
     * GET /api/v1/admin/users
     *
     * Query params:
     *   search       - tìm theo username + fullname
     *   email        - lọc email chứa chuỗi (partial match)
     *   role         - admin | staff | customer
     *   gender       - male | female | other
     *   province_id  - integer
     *   verified     - true | false (trạng thái xác thực email)
     *   created_from - YYYY-MM-DD
     *   created_to   - YYYY-MM-DD
     *   sort_by      - created_at | username | point | last_login (default: created_at)
     *   sort_dir     - asc | desc (default: desc)
     *   per_page     - integer, max 100 (default: 15)
     *   page         - integer (default: 1)
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        $allowedSortFields = ['created_at', 'username', 'point', 'total_points', 'last_login'];
        $rawSortBy = $request->get('sort_by');
        $sortBy = in_array($rawSortBy, $allowedSortFields) ? $rawSortBy : 'created_at';
        if ($sortBy === 'point') {
            $sortBy = 'total_points';
        }
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';

        $query = User::with(['province', 'role']);

        // Tìm kiếm theo username + fullname
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'ilike', "%{$search}%")
                  ->orWhere('fullname', 'ilike', "%{$search}%");
            });
        }

        // Lọc theo email (partial match)
        if ($request->filled('email')) {
            $query->where('email', 'ilike', '%' . $request->email . '%');
        }

        // Lọc theo role
        if ($request->filled('role')) {
            $roleInput = $request->role;
            $query->whereHas('role', function ($q) use ($roleInput) {
                $q->where('name', $roleInput);
            });
        }

        // Lọc theo giới tính
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Lọc theo tỉnh/thành phố
        if ($request->filled('province_id')) {
            $query->where('province_id', (int) $request->province_id);
        }

        // Lọc theo trạng thái xác thực email
        if ($request->filled('verified')) {
            if (filter_var($request->verified, FILTER_VALIDATE_BOOLEAN)) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Lọc theo khoảng ngày đăng ký
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        $users = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => AdminUserResource::collection($users->items()),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * AD-13: Thay đổi role của user
     * PUT /api/v1/admin/users/{id}/role
     *
     * Body: { "role": "admin" | "staff" | "customer" }
     *
     * Guard: Admin không thể tự đổi role của chính mình.
     */
    public function updateRole(UpdateUserRoleRequest $request, string $id)
    {
        $user = User::findOrFail($id);

        // Bảo vệ: Admin không tự đổi role của chính mình
        if ((int) $user->user_id === (int) $request->user()->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể thay đổi role của chính mình.',
            ], 422);
        }

        $roleName = $request->validated()['role'];
        $roleModel = \App\Models\Role::where('name', $roleName)->firstOrFail();
        $user->role_id = $roleModel->role_id;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật role thành \"{$roleName}\" thành công.",
            'data'    => new AdminUserResource($user->load('role', 'province')),
        ]);
    }
}
