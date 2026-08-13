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
        ]);

        $this->fixPgsqlSequences();

        $this->call([
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
            PricingRuleSeeder::class,
        ]);

        $this->fixPgsqlSequences();
    }

    private function fixPgsqlSequences(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            $tables = [
                'users' => 'user_id',
                'roles' => 'role_id',
                'permissions' => 'permission_id',
                'user_tiers' => 'user_tier_id',
                'provinces' => 'province_id',
                'cinemas' => 'cinema_id',
                'rooms' => 'room_id',
                'movies' => 'movie_id',
                'genres' => 'genre_id',
                'persons' => 'person_id',
                'credits' => 'credit_id',
                'videos' => 'video_id',
                'showtimes' => 'showtime_id',
                'showtime_seats' => 'showtime_seat_id',
                'campaigns' => 'campaign_id',
                'banners' => 'banner_id',
                'vouchers' => 'voucher_id',
                'combos' => 'combo_id',
                'bookings' => 'booking_id',
                'booking_seats' => 'booking_seat_id',
                'booking_combos' => 'booking_combo_id',
                'pricing_rules' => 'pricing_rule_id',
            ];

            foreach ($tables as $table => $pk) {
                try {
                    $seq = \Illuminate\Support\Facades\DB::selectOne("SELECT pg_get_serial_sequence('{$table}', '{$pk}') as seq");
                    if ($seq && $seq->seq) {
                        $max = \Illuminate\Support\Facades\DB::table($table)->max($pk) ?: 0;
                        $nextVal = $max + 1;
                        \Illuminate\Support\Facades\DB::statement("SELECT setval('{$seq->seq}', {$nextVal}, false)");
                    }
                } catch (\Exception $e) {
                    // Ignore missing sequences/tables
                }
            }
        }
    }
}
