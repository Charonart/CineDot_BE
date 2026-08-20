<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SeatTypeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure table schema has all enriched columns
        if (Schema::hasTable('seat_types')) {
            Schema::table('seat_types', function (Blueprint $table) {
                if (!Schema::hasColumn('seat_types', 'type_name')) {
                    $table->string('type_name')->default('Loại ghế')->after('seat_type');
                }
                if (!Schema::hasColumn('seat_types', 'color_code')) {
                    $table->string('color_code', 20)->default('#64748B')->after('surcharge_amount');
                }
                if (!Schema::hasColumn('seat_types', 'icon_name')) {
                    $table->string('icon_name', 30)->default('seat')->after('color_code');
                }
                if (!Schema::hasColumn('seat_types', 'description')) {
                    $table->text('description')->nullable()->after('icon_name');
                }
                if (!Schema::hasColumn('seat_types', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }
                if (!Schema::hasColumn('seat_types', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('is_active');
                }
                if (!Schema::hasColumn('seat_types', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        // 2. Standard Rich Seat Types
        $seatTypes = [
            [
                'seat_type'        => 'standard',
                'type_name'        => 'Ghế Tiêu Chuẩn',
                'surcharge_amount' => 0.00,
                'color_code'       => '#64748B', // Slate
                'icon_name'        => 'seat',
                'description'      => 'Ghế tiêu chuẩn êm ái, góc nhìn tốt khắp phòng chiếu.',
                'is_active'        => true,
                'sort_order'       => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'seat_type'        => 'vip',
                'type_name'        => 'Ghế VIP Prime',
                'surcharge_amount' => 20000.00,
                'color_code'       => '#7C6FE8', // CineDot Primary Purple
                'icon_name'        => 'star',
                'description'      => 'Khu vực trung tâm với góc nhìn và chất lượng âm thanh hoàn hảo nhất.',
                'is_active'        => true,
                'sort_order'       => 2,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'seat_type'        => 'couple',
                'type_name'        => 'Ghế Đôi Sweetbox',
                'surcharge_amount' => 40000.00,
                'color_code'       => '#EC4899', // Pink
                'icon_name'        => 'heart',
                'description'      => 'Ghế đôi rộng rãi, vách ngăn riêng tư dành cho 2 người ở hàng cuối.',
                'is_active'        => true,
                'sort_order'       => 3,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'seat_type'        => 'sweetbox',
                'type_name'        => 'Ghế Sweetbox Premium',
                'surcharge_amount' => 40000.00,
                'color_code'       => '#F43F5E', // Rose
                'icon_name'        => 'heart',
                'description'      => 'Ghế sofa đôi cao cấp vách ngăn riêng tư phong cách rạp chiếu hiện đại.',
                'is_active'        => true,
                'sort_order'       => 4,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'seat_type'        => 'deluxe',
                'type_name'        => 'Ghế Deluxe Recliner',
                'surcharge_amount' => 60000.00,
                'color_code'       => '#8B5CF6', // Violet
                'icon_name'        => 'crown',
                'description'      => 'Ghế da tự động ngả lưng, cổng sạc điện thoại và khay đồ ăn cá nhân.',
                'is_active'        => true,
                'sort_order'       => 5,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'seat_type'        => 'bed',
                'type_name'        => 'Ghế Giường Nằm (L\'amour)',
                'surcharge_amount' => 80000.00,
                'color_code'       => '#F59E0B', // Amber Gold
                'icon_name'        => 'bed',
                'description'      => 'Giường nằm êm ái kèm chăn gối thơm tho mang lại trải nghiệm thư giãn tối thượng.',
                'is_active'        => true,
                'sort_order'       => 6,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ];

        foreach ($seatTypes as $st) {
            DB::table('seat_types')->updateOrInsert(
                ['seat_type' => $st['seat_type']],
                $st
            );
        }
    }
}
