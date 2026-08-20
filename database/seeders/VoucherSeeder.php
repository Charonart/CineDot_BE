<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        // Safe schema sync: ensure title & description columns exist
        if (!Schema::hasColumn('vouchers', 'title')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->string('title')->nullable()->after('code');
            });
        }
        if (!Schema::hasColumn('vouchers', 'description')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }

        $vouchers = [
            [
                'voucher_id' => 1,
                'campaign_id' => 1,
                'code' => 'CINEDOT20',
                'title' => 'Giảm 20% Cho Toàn Bộ Đơn Đặt Vé',
                'description' => 'Áp dụng cho mọi suất chiếu 2D/3D khi thanh toán qua ví điện tử.',
                'voucher_type' => 'order',
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'min_order_value' => 100000.00,
                'max_discount_value' => 50000.00,
                'valid_from' => '2026-06-01 00:00:00',
                'valid_until' => '2026-12-31 23:59:59',
                'system_limit' => 1000,
                'limit_per_user' => 1,
                'used_count' => 142,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'voucher_id' => 2,
                'campaign_id' => 1,
                'code' => 'HE2026',
                'title' => 'Ưu Đãi Hè 2026 - Giảm Ngay 50.000đ',
                'description' => 'Dành riêng cho các suất chiếu bom tấn mùa hè, áp dụng đơn từ 150.000đ.',
                'voucher_type' => 'ticket',
                'discount_type' => 'fixed_amount',
                'discount_value' => 50000.00,
                'min_order_value' => 150000.00,
                'max_discount_value' => null,
                'valid_from' => '2026-06-01 00:00:00',
                'valid_until' => '2026-08-31 23:59:59',
                'system_limit' => 500,
                'limit_per_user' => 1,
                'used_count' => 389,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'voucher_id' => 3,
                'campaign_id' => 2,
                'code' => 'WELCOME50',
                'title' => 'Quà Tặng Chào Mừng Tân Hội Viên 50%',
                'description' => 'Giảm 50% tối đa 30.000đ cho đơn hàng đầu tiên của thành viên mới.',
                'voucher_type' => 'order',
                'discount_type' => 'percentage',
                'discount_value' => 50.00,
                'min_order_value' => 50000.00,
                'max_discount_value' => 30000.00,
                'valid_from' => '2026-01-01 00:00:00',
                'valid_until' => '2026-12-31 23:59:59',
                'system_limit' => 2000,
                'limit_per_user' => 1,
                'used_count' => 845,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'voucher_id' => 4,
                'campaign_id' => 3,
                'code' => 'VIPMEMBER',
                'title' => 'Tri Ân Khách Hàng VIP Gold & Platinum',
                'description' => 'Giảm 15% tối đa 100.000đ cho các phòng chiếu đặc biệt IMAX và Gold Class.',
                'voucher_type' => 'order',
                'discount_type' => 'percentage',
                'discount_value' => 15.00,
                'min_order_value' => 200000.00,
                'max_discount_value' => 100000.00,
                'valid_from' => '2026-01-01 00:00:00',
                'valid_until' => '2026-12-31 23:59:59',
                'system_limit' => 1000,
                'limit_per_user' => 2,
                'used_count' => 210,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'voucher_id' => 5,
                'campaign_id' => 1,
                'code' => 'COMBO30',
                'title' => 'Giảm 30.000đ Combo Bắp Nước CineCouple',
                'description' => 'Áp dụng cho các combo bắp rang bơ caramel và nước ngọt size L.',
                'voucher_type' => 'combo',
                'discount_type' => 'fixed_amount',
                'discount_value' => 30000.00,
                'min_order_value' => 80000.00,
                'max_discount_value' => null,
                'valid_from' => '2026-06-01 00:00:00',
                'valid_until' => '2026-12-31 23:59:59',
                'system_limit' => 800,
                'limit_per_user' => 1,
                'used_count' => 530,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'voucher_id' => 6,
                'campaign_id' => 4,
                'code' => 'NIGHTOWL',
                'title' => 'Cú Đêm Xem Phim - Suất Chiếu Sau 22h00',
                'description' => 'Giảm 25% tối đa 40.000đ cho các suất chiếu khuya từ thứ 2 đến chủ nhật.',
                'voucher_type' => 'ticket',
                'discount_type' => 'percentage',
                'discount_value' => 25.00,
                'min_order_value' => 90000.00,
                'max_discount_value' => 40000.00,
                'valid_from' => '2026-05-01 00:00:00',
                'valid_until' => '2026-11-30 23:59:59',
                'system_limit' => 600,
                'limit_per_user' => 1,
                'used_count' => 195,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($vouchers as $v) {
            DB::table('vouchers')->updateOrInsert(
                ['code' => $v['code']],
                $v
            );
        }
    }
}
