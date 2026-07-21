<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $rolePermissions = [
            // Admin has all permissions
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 1, 'permission_id' => 2],
            ['role_id' => 1, 'permission_id' => 3],
            ['role_id' => 1, 'permission_id' => 4],
            ['role_id' => 1, 'permission_id' => 5],
            // Staff
            ['role_id' => 2, 'permission_id' => 3],
            ['role_id' => 2, 'permission_id' => 4],
            // Customer
            ['role_id' => 3, 'permission_id' => 5],
        ];

        DB::table('role_permissions')->insert($rolePermissions);
    }
}
