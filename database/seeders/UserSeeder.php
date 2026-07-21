<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'user_id' => 1,
                'tier_id' => 4,
                'role_id' => 1,
                'province_id' => 1,
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'email' => 'admin@cinedot.com',
                'fullname' => 'Quản Trị Viên',
                'avatar' => null,
                'birthday' => '1990-01-01',
                'gender' => 'male',
                'phone' => '0901234567',
                'total_points' => 1000,
                'email_verified_at' => now(),
                'last_login' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'tier_id' => 2,
                'role_id' => 2,
                'province_id' => 1,
                'username' => 'staff_hn',
                'password' => Hash::make('password123'),
                'email' => 'staff@cinedot.com',
                'fullname' => 'Nhân Viên Rạp',
                'avatar' => null,
                'birthday' => '1995-05-15',
                'gender' => 'female',
                'phone' => '0902345678',
                'total_points' => 200,
                'email_verified_at' => now(),
                'last_login' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'tier_id' => 1,
                'role_id' => 3,
                'province_id' => 2,
                'username' => 'customer1',
                'password' => Hash::make('password123'),
                'email' => 'customer1@gmail.com',
                'fullname' => 'Nguyễn Văn A',
                'avatar' => null,
                'birthday' => '2000-10-20',
                'gender' => 'male',
                'phone' => '0903456789',
                'total_points' => 50,
                'email_verified_at' => now(),
                'last_login' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}
