<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Danh sách tất cả quyền trong hệ thống, nhóm theo module chức năng
     * GET /api/v1/admin/permissions
     */
    public function index()
    {
        $permissions = Permission::orderBy('permission_id', 'asc')->get();

        // Định nghĩa nhóm module
        $modules = [
            'movies'      => 'Phim & Đánh Giá',
            'cinemas'     => 'Cụm Rạp & Phòng Chiếu',
            'seat_types'  => 'Cụm Rạp & Phòng Chiếu',
            'showtimes'   => 'Lịch Chiếu & Suất Chiếu',
            'bookings'    => 'Đơn Đặt Vé & Giao Dịch',
            'tickets'     => 'Soát Vé & Check-in',
            'fnb'         => 'Soát Vé & Check-in',
            'concessions' => 'Bắp Nước F&B',
            'campaigns'   => 'Marketing & Khuyến Mãi',
            'vouchers'    => 'Marketing & Khuyến Mãi',
            'banners'     => 'Marketing & Khuyến Mãi',
            'staff'       => 'Nhân Sự & Phân Quyền',
            'users'       => 'Nhân Sự & Phân Quyền',
            'roles'       => 'Nhân Sự & Phân Quyền',
            'reports'     => 'Báo Cáo & Doanh Thu',
            'settings'    => 'Cài Đặt Hệ Thống',
            '*'           => 'Quyền Quản Trị Tối Cao',
        ];

        $grouped = [];

        foreach ($permissions as $p) {
            $prefix = explode('.', $p->name)[0];
            if ($p->name === '*') {
                $moduleName = 'Quyền Quản Trị Tối Cao';
            } elseif (isset($modules[$prefix])) {
                $moduleName = $modules[$prefix];
            } else {
                $moduleName = 'Khác';
            }

            if (!isset($grouped[$moduleName])) {
                $grouped[$moduleName] = [];
            }

            $grouped[$moduleName][] = [
                'id'            => $p->permission_id,
                'permission_id' => $p->permission_id,
                'name'          => $p->name,
                'description'   => $p->description,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'list'    => $permissions->map(fn($p) => [
                    'id'            => $p->permission_id,
                    'permission_id' => $p->permission_id,
                    'name'          => $p->name,
                    'description'   => $p->description,
                ]),
                'grouped' => $grouped,
            ]
        ]);
    }
}
