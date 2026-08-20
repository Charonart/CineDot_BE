<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Super Wildcard
            ['name' => '*', 'description' => 'Toàn quyền quản trị hệ thống'],

            // 1. Phim & Đánh giá
            ['name' => 'movies.*', 'description' => 'Toàn quyền quản lý phim'],
            ['name' => 'movies.view', 'description' => 'Xem danh sách phim'],
            ['name' => 'movies.create', 'description' => 'Tạo phim mới'],
            ['name' => 'movies.edit', 'description' => 'Chỉnh sửa thông tin phim'],
            ['name' => 'movies.delete', 'description' => 'Xóa phim'],
            ['name' => 'movies.genres.manage', 'description' => 'Quản lý thể loại phim'],
            ['name' => 'reviews.view', 'description' => 'Xem đánh giá & bình luận'],
            ['name' => 'reviews.delete', 'description' => 'Xóa bình luận vi phạm'],

            // 2. Cụm Rạp & Phòng Chiếu
            ['name' => 'cinemas.*', 'description' => 'Toàn quyền quản lý cụm rạp'],
            ['name' => 'cinemas.view', 'description' => 'Xem danh sách cụm rạp & sơ đồ ghế'],
            ['name' => 'cinemas.create', 'description' => 'Tạo cụm rạp mới'],
            ['name' => 'cinemas.manage_rooms', 'description' => 'Quản lý phòng chiếu & vẽ layout ghế'],
            ['name' => 'seat_types.manage', 'description' => 'Quản lý danh mục loại ghế & giá phụ phí'],

            // 3. Lịch Chiếu & Suất Chiếu
            ['name' => 'showtimes.*', 'description' => 'Toàn quyền xếp lịch chiếu'],
            ['name' => 'showtimes.view', 'description' => 'Xem lịch chiếu & Gantt timeline'],
            ['name' => 'showtimes.create', 'description' => 'Tạo suất chiếu & phân bổ phim'],
            ['name' => 'showtimes.edit', 'description' => 'Chỉnh sửa giờ chiếu & đổi phòng'],
            ['name' => 'showtimes.delete', 'description' => 'Hủy suất chiếu'],

            // 4. Đơn Vé & Soát Vé
            ['name' => 'bookings.*', 'description' => 'Toàn quyền quản lý đơn đặt vé'],
            ['name' => 'bookings.view', 'description' => 'Tra cứu đơn đặt vé & chi tiết'],
            ['name' => 'bookings.refund', 'description' => 'Hoàn tiền vé cho khách'],
            ['name' => 'bookings.cancel', 'description' => 'Hủy đơn đặt vé'],
            ['name' => 'tickets.scan', 'description' => 'Quét mã QR vé tại cửa vào'],
            ['name' => 'tickets.checkin', 'description' => 'Xác nhận check-in vé'],
            ['name' => 'fnb.claim', 'description' => 'Đổi phần bắp nước (F&B Claim)'],

            // 5. Bắp Nước F&B
            ['name' => 'concessions.*', 'description' => 'Toàn quyền quản lý quầy F&B'],
            ['name' => 'concessions.view', 'description' => 'Xem danh mục combo bắp nước'],
            ['name' => 'concessions.manage', 'description' => 'Tạo/Sửa combo & giá bán F&B'],

            // 6. Marketing, Chiến Dịch & Voucher
            ['name' => 'campaigns.manage', 'description' => 'Quản lý chiến dịch tiếp thị & ROI'],
            ['name' => 'vouchers.*', 'description' => 'Toàn quyền kho voucher'],
            ['name' => 'vouchers.view', 'description' => 'Xem danh sách voucher'],
            ['name' => 'vouchers.manage', 'description' => 'Tạo/Sửa/Kích hoạt voucher giảm giá'],
            ['name' => 'banners.manage', 'description' => 'Quản lý banner quảng cáo & slider'],

            // 7. Nhân Sự, Khách Hàng & RBAC
            ['name' => 'staff.*', 'description' => 'Toàn quyền quản lý nhân sự'],
            ['name' => 'staff.view', 'description' => 'Xem danh sách nhân viên'],
            ['name' => 'staff.create', 'description' => 'Tạo tài khoản nhân viên'],
            ['name' => 'staff.edit', 'description' => 'Sửa thông tin & đổi quyền nhân viên'],
            ['name' => 'staff.manage', 'description' => 'Gán rạp quản lý & phân vai trò'],
            ['name' => 'staff.delete', 'description' => 'Khóa/Xóa tài khoản nhân sự'],
            ['name' => 'users.view', 'description' => 'Xem danh sách khách hàng & hội viên'],
            ['name' => 'roles.manage', 'description' => 'Cấu hình ma trận vai trò & quyền hạn RBAC'],

            // 8. Báo Cáo & Thống Kê
            ['name' => 'reports.*', 'description' => 'Toàn quyền xem báo cáo'],
            ['name' => 'reports.dashboard.view', 'description' => 'Xem Dashboard tổng quan doanh thu'],
            ['name' => 'reports.revenue', 'description' => 'Xem chi tiết doanh thu phòng vé & F&B'],
            ['name' => 'reports.tickets', 'description' => 'Báo cáo sản lượng vé bán ra'],
            ['name' => 'reports.occupancy', 'description' => 'Báo cáo tỷ lệ lấp đầy phòng chiếu'],

            // 9. Cấu Hình Hệ Thống
            ['name' => 'settings.*', 'description' => 'Toàn quyền cài đặt hệ thống'],
            ['name' => 'settings.view', 'description' => 'Xem thông số cấu hình'],
            ['name' => 'settings.manage', 'description' => 'Thay đổi tham số vận hành rạp & cổng thanh toán'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['name' => $p['name']], ['description' => $p['description']]);
        }
    }
}
