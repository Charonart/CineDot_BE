<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;
use App\Models\Cinema;
use App\Models\Showtime;

class CinemaShowtimeSeeder extends Seeder
{
    /**
     * Seed dữ liệu mẫu cho cinemas và showtimes.
     * Chạy riêng: php artisan db:seed --class=CinemaShowtimeSeeder
     */
    public function run(): void
    {
        // Xoá dữ liệu cũ nếu chạy lại
        Showtime::truncate();
        Cinema::truncate();

        // ============================================================
        // CINEMAS (5 rạp: HN + HCM)
        // ============================================================
        $cgvVincom     = Cinema::create(['name' => 'CGV Vincom Center Bà Triệu', 'chain' => 'CGV',    'city' => 'Hà Nội',  'address' => '191 Bà Triệu, Hai Bà Trưng',         'slug' => 'cgv-vincom-ba-trieu',  'screen_count' => 8]);
        $lotteHN       = Cinema::create(['name' => 'Lotte Cinema Hà Nội',        'chain' => 'Lotte',  'city' => 'Hà Nội',  'address' => '54 Liễu Giai, Ba Đình',              'slug' => 'lotte-cinema-ha-noi',  'screen_count' => 6]);
        $cgvAeonHCM    = Cinema::create(['name' => 'CGV Aeon Mall Bình Dương',   'chain' => 'CGV',    'city' => 'TP.HCM',  'address' => 'Aeon Mall Bình Dương',                'slug' => 'cgv-aeon-binh-duong',  'screen_count' => 10]);
        $galaxyNguyenDu= Cinema::create(['name' => 'Galaxy Nguyễn Du',           'chain' => 'Galaxy', 'city' => 'TP.HCM',  'address' => '116 Nguyễn Du, Quận 1',              'slug' => 'galaxy-nguyen-du',     'screen_count' => 5]);
        $bhdPremium    = Cinema::create(['name' => 'BHD Star Phạm Hùng',         'chain' => 'BHD',    'city' => 'TP.HCM',  'address' => 'SC VivoCity, 1058 Nguyễn Văn Linh',  'slug' => 'bhd-star-pham-hung',   'screen_count' => 7]);

        // ============================================================
        // SHOWTIMES (3 ngày tới)
        // ============================================================
        $movies = Movie::all()->keyBy('title');
        $today  = now()->toDateString();
        $day2   = now()->addDay()->toDateString();
        $day3   = now()->addDays(2)->toDateString();

        $schedule = [
            // Hôm nay — CGV Vincom
            [$cgvVincom,     'Se7en',        $today, '09:00', '11:15', 'Phòng 1', '2D',   75000, 120],
            [$cgvVincom,     'Se7en',        $today, '14:30', '16:45', 'Phòng 1', '2D',   75000, 95],
            [$cgvVincom,     'Se7en',        $today, '20:00', '22:15', 'Phòng 2', '3D',   95000, 140],
            [$cgvVincom,     'Parasite',     $today, '10:00', '12:15', 'Phòng 3', '2D',   75000, 110],
            [$cgvVincom,     'Parasite',     $today, '19:30', '21:45', 'Phòng 3', '2D',   75000, 88],
            [$cgvVincom,     'Pulp Fiction', $today, '16:00', '18:40', 'Phòng 4', '2D',   75000, 130],
            // Hôm nay — Lotte HN
            [$lotteHN,       'GoodFellas',   $today, '11:00', '13:30', 'Phòng A', '2D',   80000, 100],
            [$lotteHN,       'GoodFellas',   $today, '17:00', '19:30', 'Phòng A', '2D',   80000, 72],
            [$lotteHN,       'Pulp Fiction', $today, '20:30', '23:10', 'Phòng B', '2D',   80000, 115],
            [$lotteHN,       'Se7en',        $today, '08:30', '10:45', 'Phòng C', '2D',   80000, 90],
            // Hôm nay — CGV Aeon HCM
            [$cgvAeonHCM,    'Se7en',        $today, '10:30', '12:45', 'Phòng 5', 'IMAX', 150000, 200],
            [$cgvAeonHCM,    'Se7en',        $today, '15:00', '17:15', 'Phòng 5', 'IMAX', 150000, 180],
            [$cgvAeonHCM,    'Parasite',     $today, '13:00', '15:15', 'Phòng 6', '4DX',  130000, 90],
            [$cgvAeonHCM,    'GoodFellas',   $today, '18:00', '20:30', 'Phòng 7', '2D',   85000, 160],
            [$cgvAeonHCM,    'Pulp Fiction', $today, '21:00', '23:40', 'Phòng 8', '3D',   100000, 140],
            // Hôm nay — Galaxy + BHD
            [$galaxyNguyenDu,'Pulp Fiction', $today, '14:00', '16:40', 'Phòng 2', '2D',   70000, 80],
            [$galaxyNguyenDu,'Parasite',     $today, '19:00', '21:15', 'Phòng 1', '2D',   70000, 95],
            [$bhdPremium,    'GoodFellas',   $today, '10:00', '12:30', 'Phòng P1','2D',   90000, 110],
            [$bhdPremium,    'Se7en',        $today, '16:30', '18:45', 'Phòng P2','3D',   110000, 120],
            // Ngày 2
            [$cgvVincom,     'Se7en',        $day2, '10:00', '12:15', 'Phòng 1', '2D',   75000, 140],
            [$cgvVincom,     'Pulp Fiction', $day2, '15:30', '18:10', 'Phòng 2', '2D',   75000, 120],
            [$cgvVincom,     'Parasite',     $day2, '20:00', '22:15', 'Phòng 3', '3D',   95000, 100],
            [$lotteHN,       'GoodFellas',   $day2, '14:00', '16:30', 'Phòng A', '2D',   80000, 88],
            [$cgvAeonHCM,    'Se7en',        $day2, '11:00', '13:15', 'Phòng 5', 'IMAX', 150000, 200],
            [$cgvAeonHCM,    'Parasite',     $day2, '17:30', '19:45', 'Phòng 6', '4DX',  130000, 90],
            [$galaxyNguyenDu,'Pulp Fiction', $day2, '19:30', '22:10', 'Phòng 1', '2D',   70000, 95],
            [$bhdPremium,    'GoodFellas',   $day2, '20:30', '23:00', 'Phòng P1','2D',   90000, 115],
            // Ngày 3
            [$cgvVincom,     'Se7en',        $day3, '09:30', '11:45', 'Phòng 1', '2D',   75000, 140],
            [$cgvVincom,     'GoodFellas',   $day3, '14:00', '16:30', 'Phòng 4', '2D',   75000, 110],
            [$cgvAeonHCM,    'Pulp Fiction', $day3, '18:00', '20:40', 'Phòng 8', '3D',   100000, 140],
            [$galaxyNguyenDu,'Parasite',     $day3, '20:00', '22:15', 'Phòng 2', '2D',   70000, 80],
            [$bhdPremium,    'Se7en',        $day3, '15:00', '17:15', 'Phòng P2','IMAX', 150000, 130],
        ];

        foreach ($schedule as [$cinema, $title, $date, $start, $end, $screen, $format, $price, $seats]) {
            if (!isset($movies[$title])) continue;
            Showtime::create([
                'movie_id'        => $movies[$title]->id,
                'cinema_id'       => $cinema->id,
                'show_date'       => $date,
                'start_time'      => $start,
                'end_time'        => $end,
                'screen'          => $screen,
                'format'          => $format,
                'price'           => $price,
                'available_seats' => $seats,
            ]);
        }

        $this->command->info('✅ Seeded ' . Cinema::count() . ' cinemas, ' . Showtime::count() . ' showtimes.');
    }
}
