<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed toàn bộ dữ liệu CineDot theo kiến trúc mới
     */
    public function run(): void
    {
        // Tránh log queries khi seed số lượng lớn
        DB::connection()->unsetEventDispatcher();

        $this->call([
            ProvinceSeeder::class,
            UserSeeder::class,
            GenreSeeder::class,
            MovieSeeder::class,
            PersonCreditSeeder::class,
            ReviewSeeder::class,
            CinemaSeeder::class,
            RoomSeatSeeder::class,
            ComboSeeder::class,
            VoucherSeeder::class,
            BannerSeeder::class,
            ScheduleSeeder::class,
            BookingSeeder::class,
        ]);

        // Gán cinema_id = 1 cho staff_user
        $staffUser = \App\Models\User::where('username', 'staff_user')->first();
        $cinema = \App\Models\Cinema::first();
        if ($staffUser && $cinema) {
            $staffUser->update(['cinema_id' => $cinema->cinema_id]);
        }
        
        echo "=> Tất cả dữ liệu đã được seed thành công!\n";
    }
}
