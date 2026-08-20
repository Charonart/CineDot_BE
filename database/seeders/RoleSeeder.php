<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_id' => 1, 'name' => 'admin', 'description' => 'Tổng Quản Trị Hệ Thống (Super Admin) - Toàn quyền cấu hình, quản lý nhân sự và rạp'],
            ['role_id' => 2, 'name' => 'staff', 'description' => 'Nhân Viên Rạp Phim - Vận hành ca trực, bán vé và soát vé'],
            ['role_id' => 3, 'name' => 'customer', 'description' => 'Khách Hàng Hội Viên - Đặt vé, tích điểm và nhận ưu đãi'],
            ['role_id' => 4, 'name' => 'cinema_manager', 'description' => 'Quản Lý Cụm Rạp - Quản lý phòng chiếu, xếp lịch chiếu và duyệt báo cáo'],
            ['role_id' => 5, 'name' => 'ticket_staff', 'description' => 'Nhân Viên Soát Vé - Quét mã QR vé tại cửa và check-in'],
            ['role_id' => 6, 'name' => 'fnb_staff', 'description' => 'Nhân Viên Quầy F&B - Bán và trả phần bắp nước theo đơn'],
            ['role_id' => 7, 'name' => 'marketing', 'description' => 'Chuyên Viên Marketing - Quản lý chiến dịch, tạo mã voucher và banner'],
            ['role_id' => 8, 'name' => 'accountant', 'description' => 'Kế Toán & Tài Chính - Theo dõi doanh thu phòng vé và đối soát hoàn tiền'],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(['role_id' => $r['role_id']], $r);
        }
    }
}
