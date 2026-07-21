<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProvinceSeeder::class,
            UserTierSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            CinemaSeeder::class,
            RoomSeeder::class,
            SeatTypeSeeder::class,
            GenreSeeder::class,
            MovieSeeder::class,
            MovieGenreSeeder::class,
            VideoSeeder::class,
            PersonSeeder::class,
            CreditSeeder::class,
            ShowtimeSeeder::class,
            ShowtimeSeatSeeder::class,
            CampaignSeeder::class,
            BannerSeeder::class,
            VoucherSeeder::class,
            ComboSeeder::class,
            BookingSeeder::class,
            BookingSeatSeeder::class,
            BookingComboSeeder::class,
            ReviewSeeder::class,
            PointHistorySeeder::class,
            PricingRuleSeeder::class,
        ]);
    }
}
