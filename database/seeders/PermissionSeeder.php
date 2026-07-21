<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['permission_id' => 1, 'name' => 'manage-users', 'description' => 'Quản lý người dùng'],
            ['permission_id' => 2, 'name' => 'manage-movies', 'description' => 'Quản lý danh sách phim'],
            ['permission_id' => 3, 'name' => 'manage-showtimes', 'description' => 'Quản lý suất chiếu'],
            ['permission_id' => 4, 'name' => 'manage-bookings', 'description' => 'Quản lý đơn hàng đặt vé'],
            ['permission_id' => 5, 'name' => 'book-tickets', 'description' => 'Quyền đặt vé xem phim'],
        ];

        DB::table('permissions')->insert($permissions);
    }
}
