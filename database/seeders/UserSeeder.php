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
            [
                'user_id' => 4,
                'province_id' => null,
                'username' => 'lequy27102006',
                'password' => '$2y$12$1XTHwGnMvaAlO16I29m1e.RoOxov1WoD4t/8CWGsXE3s9/qVLIvPe',
                'email' => 'lequy27102006@gmail.com',
                'fullname' => 'Nguyen Van B',
                'avatar' => 'https://cdn.cinedot.vn/avatars/user1.jpg',
                'birthday' => null,
                'gender' => null,
                'phone' => '0909876543',
                'total_points' => 0,
                'email_verified_at' => null,
                'last_login' => '2026-08-10 16:50:51',
                'created_at' => '2026-08-04 19:38:19',
                'updated_at' => '2026-08-10 16:50:51',
            ],
        ];

        foreach ($users as $user) {
            \App\Models\User::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
