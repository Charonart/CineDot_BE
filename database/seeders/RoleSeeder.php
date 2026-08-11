<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_id' => 1, 'name' => 'admin', 'description' => 'Quản trị viên hệ thống'],
            ['role_id' => 2, 'name' => 'staff', 'description' => 'Nhân viên rạp phim'],
            ['role_id' => 3, 'name' => 'customer', 'description' => 'Khách hàng'],
        ];

        DB::table('roles')->insertOrIgnore($roles);
    }
}
