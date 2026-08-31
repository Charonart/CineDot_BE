<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@cinedot.com')->orWhere('username', 'admin')->first();
        $staffUser = User::where('email', 'staff@cinedot.com')->orWhere('username', 'staff_hn')->first();
        $customer1 = User::where('email', 'customer1@gmail.com')->orWhere('username', 'customer1')->first();
        $customer2 = User::where('email', 'lequy27102006@gmail.com')->orWhere('username', 'lequy27102006')->first();

        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'cinema_manager')->first();
        $staffRole = Role::where('name', 'staff')->first();
        $customerRole = Role::where('name', 'customer')->first();

        $assignments = [];

        // 1. Super Admin: System scope
        if ($adminUser && $adminRole) {
            $assignments[] = [
                'user_id'    => $adminUser->user_id,
                'role_id'    => $adminRole->role_id,
                'scope_type' => 'system',
                'scope_id'   => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 2. Staff: Cinema manager scope for Cinema 1 (Hanoi Centre)
        if ($staffUser) {
            if ($managerRole) {
                $assignments[] = [
                    'user_id'    => $staffUser->user_id,
                    'role_id'    => $managerRole->role_id,
                    'scope_type' => 'cinema',
                    'scope_id'   => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($staffRole) {
                $assignments[] = [
                    'user_id'    => $staffUser->user_id,
                    'role_id'    => $staffRole->role_id,
                    'scope_type' => 'cinema',
                    'scope_id'   => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 3. Customers: System scope
        if ($customer1 && $customerRole) {
            $assignments[] = [
                'user_id'    => $customer1->user_id,
                'role_id'    => $customerRole->role_id,
                'scope_type' => 'system',
                'scope_id'   => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($customer2 && $adminRole) {
            $assignments[] = [
                'user_id'    => $customer2->user_id,
                'role_id'    => $adminRole->role_id,
                'scope_type' => 'system',
                'scope_id'   => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Assign any other user without role to customer
        $allUsers = User::all();
        foreach ($allUsers as $u) {
            $hasRole = UserRole::where('user_id', $u->user_id)->exists();
            if (!$hasRole && $customerRole) {
                $assignments[] = [
                    'user_id'    => $u->user_id,
                    'role_id'    => $customerRole->role_id,
                    'scope_type' => 'system',
                    'scope_id'   => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach ($assignments as $a) {
            UserRole::updateOrCreate(
                [
                    'user_id'    => $a['user_id'],
                    'role_id'    => $a['role_id'],
                    'scope_type' => $a['scope_type'],
                    'scope_id'   => $a['scope_id'],
                ],
                $a
            );
        }
    }
}
