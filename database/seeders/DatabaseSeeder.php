<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed toàn bộ dữ liệu CineDot theo kiến trúc mới:
     * - Provinces
     * - Users
     * - Genres
     * - Movies
     * - Person & Credits
     * - Reviews
     * - Cinemas & Rooms & Seats
     * - Combos & Vouchers
     * - Schedules & ScheduleSeats (Random date)
     * - Bookings & Payments
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
            ScheduleSeeder::class,
            BookingSeeder::class,
        ]);
        
        echo "=> Tất cả dữ liệu đã được seed thành công!\n";
    }
}
