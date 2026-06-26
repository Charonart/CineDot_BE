<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Province;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = Province::pluck('province_id', 'province_name')->toArray();

        $usersData = [
            [
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'email'    => 'admin@cinedot.vn',
                'fullname' => 'Nguyễn Văn Admin',
                'role'     => 'admin',
                'avatar'   => 'https://ui-avatars.com/api/?name=Admin&background=e11d48&color=fff',
                'birthday' => '1990-01-15',
                'gender'   => 'male',
                'province_name' => 'Hà Nội',
                'phone'    => '0901234567',
                'point'    => 1500,
            ],
            [
                'username' => 'lequy_admin',
                'password' => Hash::make('lequy123'),
                'email'    => 'lequy27102006@gmail.com',
                'fullname' => 'Lê Quý (Master Admin)',
                'role'     => 'admin',
                'avatar'   => 'https://ui-avatars.com/api/?name=Le+Quy&background=000000&color=fff',
                'birthday' => '2006-10-27',
                'gender'   => 'male',
                'province_name' => 'TP. Hồ Chí Minh',
                'phone'    => '0999999999',
                'point'    => 9999,
            ],
            [
                'username' => 'minh_tran',
                'password' => Hash::make('password123'),
                'email'    => 'minh.tran@gmail.com',
                'fullname' => 'Trần Minh',
                'role'     => 'customer',
                'avatar'   => 'https://ui-avatars.com/api/?name=Minh+Tran&background=7c3aed&color=fff',
                'birthday' => '1995-03-22',
                'gender'   => 'male',
                'province_name' => 'TP. Hồ Chí Minh',
                'phone'    => '0912345678',
                'point'    => 200,
            ],
            [
                'username' => 'linh_nguyen',
                'password' => Hash::make('password123'),
                'email'    => 'linh.nguyen@yahoo.com',
                'fullname' => 'Nguyễn Thị Linh',
                'role'     => 'customer',
                'avatar'   => 'https://ui-avatars.com/api/?name=Linh+Nguyen&background=0891b2&color=fff',
                'birthday' => '1998-07-10',
                'gender'   => 'female',
                'province_name' => 'Đà Nẵng',
                'phone'    => '0923456789',
                'point'    => 50,
            ],
            [
                'username' => 'hung_le',
                'password' => Hash::make('password123'),
                'email'    => 'hung.le@outlook.com',
                'fullname' => 'Lê Hùng',
                'role'     => 'customer',
                'avatar'   => 'https://ui-avatars.com/api/?name=Hung+Le&background=059669&color=fff',
                'birthday' => '1993-11-05',
                'gender'   => 'male',
                'province_name' => 'Cần Thơ',
                'phone'    => '0934567890',
                'point'    => 120,
            ],
            [
                'username' => 'thu_pham',
                'password' => Hash::make('password123'),
                'email'    => 'thu.pham@gmail.com',
                'fullname' => 'Phạm Thị Thu',
                'role'     => 'customer',
                'avatar'   => 'https://ui-avatars.com/api/?name=Thu+Pham&background=d97706&color=fff',
                'birthday' => '2000-06-18',
                'gender'   => 'female',
                'province_name' => 'Hải Phòng',
                'phone'    => '0945678901',
                'point'    => 350,
            ],
        ];

        foreach ($usersData as $ud) {
            $provinceId = $provinces[$ud['province_name']] ?? null;
            unset($ud['province_name']);
            $ud['province_id'] = $provinceId;
            
            // Random created_at and last_login within the last 30 days
            $createdAt = Carbon::now()->subDays(rand(5, 30))->subHours(rand(1, 24));
            $lastLogin = (clone $createdAt)->addDays(rand(1, 5))->addHours(rand(1, 10));
            if ($lastLogin > Carbon::now()) {
                $lastLogin = Carbon::now();
            }

            $ud['created_at'] = $createdAt;
            $ud['updated_at'] = $createdAt;
            $ud['last_login'] = $lastLogin;
            
            $user = User::create($ud);

            // Seed User Tiers
            if ($user->username === 'admin') {
                \App\Models\UserTier::create([
                    'user_id' => $user->user_id,
                    'tier' => 'vip',
                    'discount_percent' => 20
                ]);
            } elseif ($user->username === 'lequy_admin') {
                \App\Models\UserTier::create([
                    'user_id' => $user->user_id,
                    'tier' => 'super_vip',
                    'discount_percent' => 40
                ]);
            } else {
                $tier = 'member';
                $discount = 0;
                if ($user->username === 'minh_tran') {
                    $tier = 'vip';
                    $discount = 20;
                }
                \App\Models\UserTier::create([
                    'user_id' => $user->user_id,
                    'tier' => $tier,
                    'discount_percent' => $discount
                ]);
            }
        }
    }
}
