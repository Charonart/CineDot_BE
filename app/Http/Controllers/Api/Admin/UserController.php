<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * AD-12: Danh sách người dùng hệ thống (có filter & phân trang)
     * GET /api/v1/admin/users
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        $allowedFilterFields = [
            'username', 'email', 'fullname', 'phone', 'gender', 'province_id', 
            'total_points', 'point', 'is_active', 'role', 'created_at'
        ];
        $allowedSortFields = [
            'user_id', 'created_at', 'username', 'fullname', 'email', 
            'point', 'total_points', 'last_login', 'is_active'
        ];
        $searchableFields = ['username', 'fullname', 'email', 'phone'];

        $query = User::with(['province', 'userRoles.role', 'userRoles.cinema']);

        // Handle role filter backwards compatibility
        if ($request->filled('role')) {
            $roleInput = $request->role;
            if ($roleInput === 'staff_all') {
                $query->whereHas('userRoles.role', function ($q) {
                    $q->where('name', '!=', 'customer');
                });
            } else {
                $query->whereHas('userRoles.role', function ($q) use ($roleInput) {
                    $q->where('name', $roleInput);
                });
            }
        }

        // Handle email verified filter backwards compatibility
        if ($request->filled('verified')) {
            if (filter_var($request->verified, FILTER_VALIDATE_BOOLEAN)) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Apply Universal Dynamic Filter & Sort Pipeline
        $query->applyDataTableQuery($request, $allowedFilterFields, $allowedSortFields, $searchableFields);

        $page = (int) $request->get('page', 1);
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data'    => AdminUserResource::collection($users->items()),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'totalPages'   => $users->lastPage(),
                'totalResults' => $users->total(),
            ],
            'pagination' => [
                'page'       => $users->currentPage(),
                'perPage'    => $users->perPage(),
                'total'      => $users->total(),
                'totalPages' => $users->lastPage(),
            ]
        ]);
    }

    /**
     * Thống kê KPI tổng quan Người dùng & Hội viên
     * GET /api/v1/admin/users/stats
     */
    public function stats()
    {
        $totalUsers = User::count();
        $totalCustomers = User::whereHas('userRoles.role', fn($q) => $q->where('name', 'customer'))->count();
        $totalStaff = User::whereHas('userRoles.role', fn($q) => $q->where('name', '!=', 'customer'))->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $activeUsers = User::where('is_active', true)->count();
        $totalPoints = (int) User::sum('total_points');

        return response()->json([
            'success' => true,
            'data'    => [
                'total_users'        => $totalUsers,
                'total_customers'    => $totalCustomers,
                'total_staff'        => $totalStaff,
                'verified_users'     => $verifiedUsers,
                'active_users'       => $activeUsers,
                'total_points'       => $totalPoints,
            ]
        ]);
    }

    /**
     * Chi tiết người dùng kèm lịch sử đặt vé & các vai trò ngữ cảnh
     * GET /api/v1/admin/users/{id}
     */
    public function show($id)
    {
        $user = User::with([
            'province',
            'userRoles.role',
            'userRoles.cinema',
            'userRoles.province',
            'bookings' => function ($q) {
                $q->with(['showtime.movie', 'showtime.room.cinema'])
                  ->orderByDesc('created_at')
                  ->limit(10);
            }
        ])->findOrFail($id);

        $totalSpent = (float) $user->bookings()->where('status', 'paid')->sum('total_price');
        $paidBookingsCount = $user->bookings()->where('status', 'paid')->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'user'              => new AdminUserResource($user),
                'total_spent'       => $totalSpent,
                'paid_bookings_count' => $paidBookingsCount,
                'recent_bookings'   => $user->bookings->map(function ($b) {
                    return [
                        'booking_id'   => $b->booking_id,
                        'booking_code' => $b->booking_code ?? '#' . $b->booking_id,
                        'movie_title'  => $b->showtime?->movie?->title ?? 'N/A',
                        'cinema_name'  => $b->showtime?->room?->cinema?->cinema_name ?? 'N/A',
                        'room_name'    => $b->showtime?->room?->room_name ?? 'N/A',
                        'show_time'    => $b->showtime?->start_time?->toISOString(),
                        'total_price'  => (float) $b->total_price,
                        'status'       => $b->status,
                        'created_at'   => $b->created_at?->toISOString(),
                    ];
                }),
                'context_roles'     => $user->userRoles->map(function ($ur) {
                    $scopeName = 'Toàn hệ thống';
                    if ($ur->scope_type === 'cinema' && $ur->cinema) {
                        $scopeName = $ur->cinema->cinema_name;
                    } elseif ($ur->scope_type === 'region' && $ur->province) {
                        $scopeName = $ur->province->province_name;
                    }

                    return [
                        'id'         => $ur->id,
                        'role_id'    => $ur->role_id,
                        'role_name'  => $ur->role?->name,
                        'scope_type' => $ur->scope_type,
                        'scope_id'   => $ur->scope_id,
                        'scope_name' => $scopeName,
                    ];
                }),
            ]
        ]);
    }

    /**
     * Tạo tài khoản người dùng / khách hàng mới
     * POST /api/v1/admin/users
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username'    => 'required|string|max:50|unique:users,username',
            'email'       => 'required|email|max:100|unique:users,email',
            'password'    => 'required|string|min:6',
            'fullname'    => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:20',
            'gender'      => 'nullable|in:male,female,other',
            'birthday'    => 'nullable|date',
            'province_id' => 'nullable|exists:provinces,province_id',
            'role_id'     => 'nullable|exists:roles,role_id',
            'role'        => 'nullable|string|exists:roles,name',
            'cinema_id'   => 'nullable|exists:cinemas,cinema_id',
            'is_active'   => 'nullable|boolean',
        ]);

        $roleId = $validated['role_id'] ?? null;
        if (!$roleId && !empty($validated['role'])) {
            $foundRole = Role::where('name', $validated['role'])->first();
            $roleId = $foundRole?->role_id;
        }
        if (!$roleId) {
            $customerRole = Role::where('name', 'customer')->first();
            $roleId = $customerRole?->role_id ?? 3;
        }

        $user = User::create([
            'username'          => $validated['username'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'fullname'          => $validated['fullname'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'gender'            => $validated['gender'] ?? null,
            'birthday'          => $validated['birthday'] ?? null,
            'province_id'       => $validated['province_id'] ?? null,
            'is_active'         => $validated['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        $scopeType = !empty($validated['cinema_id']) ? 'cinema' : 'system';
        $scopeId = $validated['cinema_id'] ?? null;

        \App\Models\UserRole::create([
            'user_id'    => $user->user_id,
            'role_id'    => $roleId,
            'scope_type' => $scopeType,
            'scope_id'   => $scopeId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo tài khoản người dùng thành công.',
            'data'    => new AdminUserResource($user->load('userRoles.role', 'province')),
        ], 201);
    }

    /**
     * Cập nhật thông tin người dùng
     * PUT /api/v1/admin/users/{id}
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'fullname'    => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:20',
            'gender'      => 'nullable|in:male,female,other',
            'birthday'    => 'nullable|date',
            'province_id' => 'nullable|exists:provinces,province_id',
            'password'    => 'nullable|string|min:6',
            'is_active'   => 'nullable|boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin người dùng thành công.',
            'data'    => new AdminUserResource($user->fresh()->load('userRoles.role', 'province')),
        ]);
    }

    /**
     * Bật / Tắt trạng thái khóa tài khoản
     * PATCH /api/v1/admin/users/{id}/toggle-status
     */
    public function toggleStatus(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        if ((int) $user->user_id === (int) $request->user()->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự khóa tài khoản của chính mình.',
            ], 422);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'mở khóa' : 'khóa';

        return response()->json([
            'success' => true,
            'message' => "Đã {$statusStr} tài khoản người dùng thành công.",
            'data'    => new AdminUserResource($user->load('userRoles.role', 'province')),
        ]);
    }

    /**
     * Điều chỉnh điểm thưởng hội viên thủ công
     * POST /api/v1/admin/users/{id}/adjust-points
     */
    public function adjustPoints(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'points' => 'required|integer',
            'reason' => 'nullable|string|max:255',
        ]);

        $pointsDelta = (int) $validated['points'];
        $newPoints = max(0, ((int) $user->total_points) + $pointsDelta);
        $user->total_points = $newPoints;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => "Đã điều chỉnh " . ($pointsDelta >= 0 ? "+{$pointsDelta}" : "{$pointsDelta}") . " điểm thưởng.",
            'data'    => new AdminUserResource($user->fresh()->load('userRoles.role', 'province')),
        ]);
    }

    /**
     * AD-13: Thay đổi role của user
     * PUT /api/v1/admin/users/{id}/role
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
        $roleModel = Role::where('name', $roleName)->firstOrFail();

        $cinemaId = $request->input('cinema_id');
        $scopeType = !empty($cinemaId) ? 'cinema' : 'system';
        $scopeId = $cinemaId ?: null;

        // Cập nhật hoặc tạo vai trò ngữ cảnh trong user_roles
        \App\Models\UserRole::updateOrCreate(
            [
                'user_id'    => $user->user_id,
                'scope_type' => $scopeType,
                'scope_id'   => $scopeId,
            ],
            [
                'role_id' => $roleModel->role_id,
            ]
        );

        app(\App\Services\PermissionService::class)->clearPermissionsCache($user->user_id);

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật role thành \"{$roleName}\" thành công.",
            'data'    => new AdminUserResource($user->load('userRoles.role', 'province')),
        ]);
    }

    /**
     * Xóa tài khoản người dùng
     * DELETE /api/v1/admin/users/{id}
     */
    public function destroy(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        if ((int) $user->user_id === (int) $request->user()->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự xóa tài khoản của chính mình.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tài khoản người dùng thành công.',
        ]);
    }

    /**
     * Chỉnh sửa nhanh 1 ô dữ liệu trực tiếp trong bảng (Notion/Sheet style)
     * PATCH /api/v1/admin/users/{id}/cell
     */
    public function updateCell(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'field' => 'required|string|in:fullname,phone,gender,is_active,province_id,role_id,total_points,birthday',
            'value' => 'nullable',
        ]);

        $field = $validated['field'];
        $value = $validated['value'];

        // Specific field sanitization
        if ($field === 'is_active') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif ($field === 'total_points') {
            $value = max(0, (int) $value);
        } elseif ($field === 'province_id') {
            $value = !empty($value) ? (int) $value : null;
        }

        $user->{$field} = $value;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$field} thành công.",
            'data'    => new AdminUserResource($user->fresh()->load('userRoles.role', 'province')),
        ]);
    }

    /**
     * Thao tác hàng loạt (Bulk Operations)
     * POST /api/v1/admin/users/bulk
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action'  => 'required|string|in:delete,toggle_active,set_active,set_inactive,adjust_points',
            'ids'     => 'required|array|min:1',
            'ids.*'   => 'required|integer',
            'payload' => 'nullable|array',
        ]);

        $action = $validated['action'];
        $ids = array_diff($validated['ids'], [$request->user()->user_id]); // Do not affect self

        switch ($action) {
            case 'delete':
                $count = User::whereIn('user_id', $ids)->delete();
                return response()->json([
                    'success' => true,
                    'message' => "Đã xóa {$count} tài khoản thành công.",
                ]);

            case 'set_active':
                $count = User::whereIn('user_id', $ids)->update(['is_active' => true]);
                return response()->json([
                    'success' => true,
                    'message' => "Đã kích hoạt {$count} tài khoản thành công.",
                ]);

            case 'set_inactive':
                $count = User::whereIn('user_id', $ids)->update(['is_active' => false]);
                return response()->json([
                    'success' => true,
                    'message' => "Đã khóa {$count} tài khoản thành công.",
                ]);

            case 'adjust_points':
                $points = (int) ($validated['payload']['points'] ?? 0);
                if ($points !== 0) {
                    if ($points > 0) {
                        User::whereIn('user_id', $ids)->increment('total_points', $points);
                    } else {
                        User::whereIn('user_id', $ids)->decrement('total_points', abs($points));
                    }
                }
                return response()->json([
                    'success' => true,
                    'message' => "Đã điều chỉnh điểm cho " . count($ids) . " tài khoản.",
                ]);

            default:
                return response()->json(['success' => false, 'message' => 'Hành động không hợp lệ.'], 422);
        }
    }
}
