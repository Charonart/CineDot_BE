<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin'], ['description' => 'Quản trị toàn hệ thống (Wildcard *)']);
        $contentManager = Role::firstOrCreate(['name' => 'content_manager'], ['description' => 'Quản lý thông tin phim & suất chiếu']);
        $cskhStaff = Role::firstOrCreate(['name' => 'cskh_staff'], ['description' => 'Chăm sóc khách hàng & xử lý hoàn vé']);
        $merchant = Role::firstOrCreate(['name' => 'merchant'], ['description' => 'Trưởng rạp chiếu phim']);
        $customer = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Khách hàng']);

        // 2. Create Permissions
        $permissions = [
            '*' => 'Quản trị toàn quyền',
            'view:movie' => 'Xem danh sách phim',
            'create:movie' => 'Thêm mới phim',
            'edit:movie' => 'Cập nhật phim',
            'delete:movie' => 'Xóa phim',
            'create:showtime' => 'Tạo suất chiếu mới',
            'view:showtime' => 'Xem lịch chiếu',
            'view:booking' => 'Xem danh sách hóa đơn',
            'search:booking' => 'Tra cứu hóa đơn',
            'refund:ticket' => 'Xử lý hoàn tiền vé',
            'resend:ticket' => 'Gửi lại mã vé',
            'view:cinema_report' => 'Xem báo cáo doanh thu rạp',
        ];

        $permissionModels = [];
        foreach ($permissions as $name => $description) {
            $permissionModels[$name] = Permission::firstOrCreate(['name' => $name], ['description' => $description]);
        }

        // 3. Role-Permission Mappings
        // Super Admin gets wildcard *
        $superAdmin->permissions()->sync([$permissionModels['*']->permission_id]);

        // Content Manager
        $contentManager->permissions()->sync([
            $permissionModels['view:movie']->permission_id,
            $permissionModels['create:movie']->permission_id,
            $permissionModels['edit:movie']->permission_id,
            $permissionModels['delete:movie']->permission_id,
            $permissionModels['create:showtime']->permission_id,
            $permissionModels['view:showtime']->permission_id,
        ]);

        // CSKH Staff
        $cskhStaff->permissions()->sync([
            $permissionModels['view:booking']->permission_id,
            $permissionModels['search:booking']->permission_id,
            $permissionModels['refund:ticket']->permission_id,
            $permissionModels['resend:ticket']->permission_id,
        ]);

        // Merchant
        $merchant->permissions()->sync([
            $permissionModels['view:showtime']->permission_id,
            $permissionModels['create:showtime']->permission_id,
            $permissionModels['view:cinema_report']->permission_id,
        ]);
    }
}
