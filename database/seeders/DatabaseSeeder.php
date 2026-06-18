<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\User;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Person;
use App\Models\Credit;
use App\Models\Review;
use App\Models\Video;
use App\Models\Cinema;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed toàn bộ dữ liệu CineDot theo ERD mới:
     * - Provinces (9 tỉnh thành)
     * - Users (10 test users)
     * - Genres (8 thể loại)
     * - Movies (6 phim với đầy đủ TMDB fields)
     * - Movie Genres (Many-to-many)
     * - Persons & Credits (Cast/Crew chất lượng cao)
     * - Videos (Trailer cho từng phim)
     * - Cinemas (7 rạp phân bố ở các tỉnh thành)
     * - Rooms (2 phòng mỗi rạp với room_type: 2D, 3D, IMAX, 4DX)
     * - Seats (Grid 6x8 = 48 ghế/phòng, tự động tạo)
     * - Schedules (Lịch chiếu cho hôm nay và 2 ngày tiếp theo)
     * - ScheduleSeats (Trạng thái ghế của từng suất chiếu)
     * - Reviews (20 reviews mẫu để tính điểm rating & vote_count chính xác)
     * - Bookings, BookingSeats & Payments (Đặt vé và thanh toán mẫu)
     */
    public function run(): void
    {
        // Tránh log queries khi seed số lượng lớn
        DB::connection()->unsetEventDispatcher();

        echo "=> Seeding Provinces...\n";
        $provinceData = [
            ['name' => 'Hà Nội', 'code' => 'HN'],
            ['name' => 'TP. Hồ Chí Minh', 'code' => 'HCM'],
            ['name' => 'Đà Nẵng', 'code' => 'DN'],
            ['name' => 'Cần Thơ', 'code' => 'CT'],
            ['name' => 'Hải Phòng', 'code' => 'HP'],
            ['name' => 'Huế', 'code' => 'HUE'],
            ['name' => 'Biên Hoà', 'code' => 'BH'],
            ['name' => 'Vũng Tàu', 'code' => 'VT'],
            ['name' => 'Nha Trang', 'code' => 'NT'],
        ];
        $provinces = [];
        foreach ($provinceData as $p) {
            $provinces[$p['name']] = Province::create([
                'province_name' => $p['name'],
                'province_code' => $p['code'],
            ]);
        }

        echo "=> Seeding Users...\n";
        $usersData = [
            [
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'email'    => 'admin@cinedot.vn',
                'fullname' => 'Nguyễn Văn Admin',
                'avatar'   => 'https://ui-avatars.com/api/?name=Admin&background=e11d48&color=fff',
                'birthday' => '1990-01-15',
                'gender'   => 'male',
                'province_name' => 'Hà Nội',
                'phone'    => '0901234567',
                'point'    => 1500,
                'last_login' => now(),
            ],
            [
                'username' => 'minh_tran',
                'password' => Hash::make('password123'),
                'email'    => 'minh.tran@gmail.com',
                'fullname' => 'Trần Minh',
                'avatar'   => 'https://ui-avatars.com/api/?name=Minh+Tran&background=7c3aed&color=fff',
                'birthday' => '1995-03-22',
                'gender'   => 'male',
                'province_name' => 'TP. Hồ Chí Minh',
                'phone'    => '0912345678',
                'point'    => 200,
                'last_login' => now()->subDays(1),
            ],
            [
                'username' => 'linh_nguyen',
                'password' => Hash::make('password123'),
                'email'    => 'linh.nguyen@yahoo.com',
                'fullname' => 'Nguyễn Thị Linh',
                'avatar'   => 'https://ui-avatars.com/api/?name=Linh+Nguyen&background=0891b2&color=fff',
                'birthday' => '1998-07-10',
                'gender'   => 'female',
                'province_name' => 'Đà Nẵng',
                'phone'    => '0923456789',
                'point'    => 50,
                'last_login' => now()->subDays(2),
            ],
            [
                'username' => 'hung_le',
                'password' => Hash::make('password123'),
                'email'    => 'hung.le@outlook.com',
                'fullname' => 'Lê Hùng',
                'avatar'   => 'https://ui-avatars.com/api/?name=Hung+Le&background=059669&color=fff',
                'birthday' => '1993-11-05',
                'gender'   => 'male',
                'province_name' => 'Cần Thơ',
                'phone'    => '0934567890',
                'point'    => 120,
                'last_login' => now()->subDays(3),
            ],
            [
                'username' => 'thu_pham',
                'password' => Hash::make('password123'),
                'email'    => 'thu.pham@gmail.com',
                'fullname' => 'Phạm Thị Thu',
                'avatar'   => 'https://ui-avatars.com/api/?name=Thu+Pham&background=d97706&color=fff',
                'birthday' => '2000-06-18',
                'gender'   => 'female',
                'province_name' => 'Hải Phòng',
                'phone'    => '0945678901',
                'point'    => 350,
                'last_login' => now()->subDays(4),
            ],
            [
                'username' => 'duc_hoang',
                'password' => Hash::make('password123'),
                'email'    => 'duc.hoang@gmail.com',
                'fullname' => 'Hoàng Đức',
                'avatar'   => 'https://ui-avatars.com/api/?name=Duc+Hoang&background=dc2626&color=fff',
                'birthday' => '1997-02-28',
                'gender'   => 'male',
                'province_name' => 'Huế',
                'phone'    => '0956789012',
                'point'    => 0,
                'last_login' => now()->subDays(5),
            ],
            [
                'username' => 'mai_vo',
                'password' => Hash::make('password123'),
                'email'    => 'mai.vo@cinedot.vn',
                'fullname' => 'Võ Thị Mai',
                'avatar'   => 'https://ui-avatars.com/api/?name=Mai+Vo&background=be185d&color=fff',
                'birthday' => '1996-09-14',
                'gender'   => 'female',
                'province_name' => 'Biên Hoà',
                'phone'    => '0967890123',
                'point'    => 80,
                'last_login' => now()->subDays(10),
            ],
            [
                'username' => 'tuan_bui',
                'password' => Hash::make('password123'),
                'email'    => 'tuan.bui@gmail.com',
                'fullname' => 'Bùi Tuấn',
                'avatar'   => 'https://ui-avatars.com/api/?name=Tuan+Bui&background=1d4ed8&color=fff',
                'birthday' => '1992-04-30',
                'gender'   => 'male',
                'province_name' => 'Vũng Tàu',
                'phone'    => '0978901234',
                'point'    => 450,
                'last_login' => now()->subHours(3),
            ],
            [
                'username' => 'hoa_dang',
                'password' => Hash::make('password123'),
                'email'    => 'hoa.dang@gmail.com',
                'fullname' => 'Đặng Thị Hoa',
                'avatar'   => 'https://ui-avatars.com/api/?name=Hoa+Dang&background=7e22ce&color=fff',
                'birthday' => '2001-12-25',
                'gender'   => 'female',
                'province_name' => 'Nha Trang',
                'phone'    => '0989012345',
                'point'    => 10,
                'last_login' => now()->subDays(7),
            ],
            [
                'username' => 'khoa_phan',
                'password' => Hash::make('password123'),
                'email'    => 'khoa.phan@gmail.com',
                'fullname' => 'Phan Khoa',
                'avatar'   => 'https://ui-avatars.com/api/?name=Khoa+Phan&background=0f766e&color=fff',
                'birthday' => '1999-08-08',
                'gender'   => 'male',
                'province_name' => 'Cần Thơ',
                'phone'    => '0990123456',
                'point'    => 90,
                'last_login' => now()->subDays(2),
            ],
        ];

        $users = [];
        foreach ($usersData as $ud) {
            $prov = $provinces[$ud['province_name']] ?? null;
            unset($ud['province_name']);
            $ud['province_id'] = $prov ? $prov->province_id : null;
            
            $users[] = User::create($ud);
        }

        echo "=> Seeding Genres...\n";
        $genreData = [
            ['id' => 12, 'name' => 'Adventure', 'slug' => 'adventure'],
            ['id' => 14, 'name' => 'Fantasy', 'slug' => 'fantasy'],
            ['id' => 16, 'name' => 'Animation', 'slug' => 'animation'],
            ['id' => 18, 'name' => 'Drama', 'slug' => 'drama'],
            ['id' => 28, 'name' => 'Action', 'slug' => 'action'],
            ['id' => 35, 'name' => 'Comedy', 'slug' => 'comedy'],
            ['id' => 53, 'name' => 'Thriller', 'slug' => 'thriller'],
            ['id' => 80, 'name' => 'Crime', 'slug' => 'crime'],
            ['id' => 878, 'name' => 'Science Fiction', 'slug' => 'sci-fi'],
            ['id' => 9648, 'name' => 'Mystery', 'slug' => 'mystery'],
            ['id' => 10749, 'name' => 'Romance', 'slug' => 'romance'],
            ['id' => 10751, 'name' => 'Family', 'slug' => 'family'],
            ['id' => 10752, 'name' => 'War', 'slug' => 'war'],
            ['id' => 99, 'name' => 'Documentary', 'slug' => 'documentary'],
            ['id' => 10001, 'name' => 'Tech', 'slug' => 'tech'],
        ];
        $genres = [];
        foreach ($genreData as $g) {
            $genreModel = Genre::create([
                'genre_id'   => $g['id'],
                'genre_name' => $g['name'],
            ]);
            $genres[$g['slug']] = $genreModel;
            $genres[$g['id']]   = $genreModel;
        }

        echo "=> Seeding Movies...\n";
        $moviesData = [
            [
                'id' => 1,
                'title' => 'CineDot: The Beginning',
                'original_title' => 'CineDot: The Beginning',
                'overview' => "A senior architect's journey to build the perfect movie app. This documentary explores the challenges of clean architecture and the beauty of modular design.",
                'poster_path' => 'https://images.tmdb.org/t/p/w500/poster1.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/backdrop1.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 120,
                'release_date' => '2024-05-14',
                'original_language' => 'en',
                'popularity' => 99.500,
                'genres' => [99, 10001],
            ],
            [
                'id' => 2,
                'title' => 'Tanstack & Beyond',
                'original_title' => 'Tanstack & Beyond',
                'overview' => 'The epic story of state management and the developers who dared to dream of a server-state revolution.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/poster2.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/backdrop2.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 95,
                'release_date' => '2024-06-20',
                'original_language' => 'en',
                'popularity' => 85.000,
                'genres' => [28, 10001],
            ],
            [
                'id' => 3,
                'title' => 'Se7en',
                'original_title' => 'Seven',
                'overview' => 'Two homicide detectives are on a desperate hunt for a serial killer whose crimes are based on the seven deadly sins.',
                'poster_path' => 'https://images.tmdb.org/t/p/w185/6yoghtyTpznpBik8EngEmJskVUO.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/w342/yw9LkPVBXQCNBHMoVuWHcuYe6RM.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 127,
                'release_date' => '1995-09-22',
                'original_language' => 'en',
                'popularity' => 150.000,
                'genres' => [80, 9648, 53],
            ],
            [
                'id' => 4,
                'title' => 'Parasite',
                'original_title' => 'Gisaengchung',
                'overview' => "All unemployed, Ki-taek's family takes peculiar interest in the wealthy and glamorous Park family.",
                'poster_path' => 'https://images.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/TU9NIjwzjoKPwQHoHshkFcQUCG.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 132,
                'release_date' => '2019-05-30',
                'original_language' => 'ko',
                'popularity' => 180.000,
                'genres' => [35, 53, 18],
            ],
            [
                'id' => 5,
                'title' => 'GoodFellas',
                'original_title' => 'GoodFellas',
                'overview' => 'The true story of Henry Hill, a half-Irish, half-Sicilian Brooklyn kid who is adopted by the Mob.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/aKuFiU82s5ISJpGZp7YkIr3kCUd.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/sw7mordbZxgITU877yTpZCud90M.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 146,
                'release_date' => '1990-09-12',
                'original_language' => 'en',
                'popularity' => 110.000,
                'genres' => [18, 80],
            ],
            [
                'id' => 6,
                'title' => 'Pulp Fiction',
                'original_title' => 'Pulp Fiction',
                'overview' => 'The lives of two mob hitmen, a boxer, a gangster and his wife intertwine in four tales of violence and redemption.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/4cDFJr4HnXN5AdPw4AKrmLlMWdO.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 154,
                'release_date' => '1994-09-10',
                'original_language' => 'en',
                'popularity' => 220.000,
                'genres' => [53, 80, 18],
            ],
            [
                'id' => 120,
                'title' => 'The Lord of the Rings: The Fellowship of the Ring',
                'original_title' => 'The Lord of the Rings: The Fellowship of the Ring',
                'overview' => 'Young hobbit Frodo Baggins, after inheriting a mysterious ring from his uncle Bilbo, must leave his home in order to keep it from falling into the hands of its evil creator. Along the way, a fellowship is formed to protect the ringbearer and make sure that the ring arrives at its final destination: Mt. Doom, the only place where it can be destroyed.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/6oom5QYQ2yQTMJIbnvbkBL9cHo6.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/dUVbWINfRMGojGZRcO6GF1Z2nV8.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 178,
                'release_date' => '2001-12-18',
                'original_language' => 'en',
                'popularity' => 84.737,
                'genres' => [12, 14, 28],
            ],
            [
                'id' => 106,
                'title' => 'Predator',
                'original_title' => 'Predator',
                'overview' => 'A team of elite commandos on a secret mission in a Central American jungle come to find themselves hunted by an extraterrestrial warrior.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/k3mW4qfJo6SKqe6laRyNGnbB9n5.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/YL3GPOiDcNraIJOVDCZsoOBoDy.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 107,
                'release_date' => '1987-06-12',
                'original_language' => 'en',
                'popularity' => 62.969,
                'genres' => [878, 28, 12, 53],
            ],
            [
                'id' => 155,
                'title' => 'The Dark Knight',
                'original_title' => 'The Dark Knight',
                'overview' => 'Batman raises the stakes in his war on crime. With the help of Lt. Jim Gordon and District Attorney Harvey Dent, Batman sets out to dismantle the remaining criminal organizations that plague the streets. The partnership proves to be effective, but they soon find themselves prey to a reign of chaos unleashed by a rising criminal mastermind known to the terrified citizens of Gotham as the Joker.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/qJ2tW6WMUDux911r6m7haRef0WH.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/dqK9Hag1054tghRQSqLSfrkvQnA.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 152,
                'release_date' => '2008-07-14',
                'original_language' => 'en',
                'popularity' => 83.151,
                'genres' => [18, 28, 80, 53],
            ],
            [
                'id' => 12,
                'title' => 'Finding Nemo',
                'original_title' => 'Finding Nemo',
                'overview' => "Nemo, an adventurous young clownfish, is unexpectedly taken from his Great Barrier Reef home to a dentist's office aquarium. It's up to his worrisome father Marlin and a friendly but forgetful fish Dory to bring Nemo home -- meeting vegetarian sharks, surfer dude turtles, hypnotic jellyfish, hungry seagulls, and more along the way.",
                'poster_path' => 'https://images.tmdb.org/t/p/w500/5lc6nQc0VhWFYFbNv016xze8Jvy.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/yzqaKAhglTrkeOfuIXYYArf0WnA.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 100,
                'release_date' => '2003-05-30',
                'original_language' => 'en',
                'popularity' => 94.545,
                'genres' => [16, 10751],
            ],
            [
                'id' => 107,
                'title' => 'Snatch',
                'original_title' => 'Snatch',
                'overview' => 'Unscrupulous boxing promoters, violent bookmakers, a Russian gangster, incompetent amateur robbers and supposedly Jewish jewelers fight to track down a priceless stolen diamond.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/56mOJth6DJ6JhgoE2jtpilVqJO.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/ysKahAEPP8h6MInuLjr0xuZOTjh.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 104,
                'release_date' => '2000-09-01',
                'original_language' => 'en',
                'popularity' => 32.430,
                'genres' => [80, 35],
            ],
            [
                'id' => 38,
                'title' => 'Eternal Sunshine of the Spotless Mind',
                'original_title' => 'Eternal Sunshine of the Spotless Mind',
                'overview' => 'Joel Barish, heartbroken that his girlfriend underwent a procedure to erase him from her memory, decides to do the same. However, as he watches his memories of her fade away, he realises that he still loves her, and may be too late to correct his mistake.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/5MwkWH9tYHv3mV9OdYTMR5qreIz.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/8AE7M2lMsyvowfT36porzGtmOtI.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 108,
                'release_date' => '2004-03-19',
                'original_language' => 'en',
                'popularity' => 36.729,
                'genres' => [878, 18, 10749],
            ],
            [
                'id' => 13,
                'title' => 'Forrest Gump',
                'original_title' => 'Forrest Gump',
                'overview' => 'A man with a low IQ has accomplished great things in his life and been present during significant historic events—in each case, far exceeding what anyone imagined he could do. But despite all he has achieved, his one true love eludes him.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/arw2vcBveWOVZr6pxd9XTd1TdQa.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/3h1JZGDhZ8nzxdgvkxha0qBqi05.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 142,
                'release_date' => '1994-06-23',
                'original_language' => 'en',
                'popularity' => 62.225,
                'genres' => [35, 18, 10749],
            ],
            [
                'id' => 280,
                'title' => 'Terminator 2: Judgment Day',
                'original_title' => 'Terminator 2: Judgment Day',
                'overview' => 'Nearly 10 years have passed since Sarah Connor was targeted for termination by a cyborg from the future. Now her son, John, the future leader of the resistance, is the target for a newer, more deadly terminator. Once again, the resistance has managed to send a protector back to attempt to save John and his mother Sarah.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/5M0j0B18abtBI5gi2RhfjjurTqb.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/xKb6mtdfI5Qsggc44Hr9CCUDvaj.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 137,
                'release_date' => '1991-07-03',
                'original_language' => 'en',
                'popularity' => 66.674,
                'genres' => [28, 53, 878],
            ],
            [
                'id' => 310,
                'title' => 'Bruce Almighty',
                'original_title' => 'Bruce Almighty',
                'overview' => 'Bruce Nolan toils as a \'human interest\' television reporter in Buffalo, N.Y., but despite his high ratings and the love of his beautiful girlfriend, Bruce remains unfulfilled. At the end of the worst day in his life, he angrily ridicules God—and the Almighty responds, endowing Bruce with all of His divine powers.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/f0QqG14SZYYZcV4VWykVc5w13dz.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/8bQbejOvAKe78RXxpkfM04n0K18.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 101,
                'release_date' => '2003-05-23',
                'original_language' => 'en',
                'popularity' => 35.483,
                'genres' => [14, 35],
            ],
            [
                'id' => 187,
                'title' => 'Sin City',
                'original_title' => 'Sin City',
                'overview' => 'Welcome to Sin City. This town beckons to the tough, the corrupt, the brokenhearted. Some call it dark… Hard-boiled. Then there are those who call it home — Crooked cops, sexy dames, desperate vigilantes. Some are seeking revenge, others lust after redemption, and then there are those hoping for a little of both. A universe of unlikely and reluctant heroes still trying to do the right thing in a city that refuses to care.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/i66G50wATMmPrvpP95f0XP6ZdVS.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/lt1rGQSqhk2T2T4lHo6CTQYvoWP.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 124,
                'release_date' => '2005-04-01',
                'original_language' => 'en',
                'popularity' => 23.715,
                'genres' => [53, 28, 80],
            ],
            [
                'id' => 238,
                'title' => 'The Godfather',
                'original_title' => 'The Godfather',
                'overview' => 'Spanning the years 1945 to 1955, a chronicle of the fictional Italian-American Corleone crime family. When organized crime family patriarch, Vito Corleone barely survives an attempt on his life, his youngest son, Michael steps in to take care of the would-be killers, launching a campaign of bloody revenge.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/3bhkrj58Vtu7enYsRolD1fZdja1.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/tmU7GeKVybMWFButWEGl2M4GeiP.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 175,
                'release_date' => '1972-03-14',
                'original_language' => 'en',
                'popularity' => 100.932,
                'genres' => [18, 80],
            ],
            [
                'id' => 289,
                'title' => 'Casablanca',
                'original_title' => 'Casablanca',
                'overview' => 'In Casablanca, Morocco in December 1941, a cynical American expatriate meets a former lover, with unforeseen complications.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/5K7cOHoay2mZusSLezBOY0Qxh8a.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/wOfzdzC0QZyhUIlyjeMuUYSb8Ax.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 102,
                'release_date' => '1942-11-26',
                'original_language' => 'en',
                'popularity' => 17.927,
                'genres' => [18, 10749],
            ],
            [
                'id' => 275,
                'title' => 'Fargo',
                'original_title' => 'Fargo',
                'overview' => 'Jerry, a small-town Minnesota car salesman is bursting at the seams with debt... but he\'s got a plan. He\'s going to hire two thugs to kidnap his wife in a scheme to collect a hefty ransom from his wealthy father-in-law. It\'s going to be a snap and nobody\'s going to get hurt... until people start dying. Enter Police Chief Marge, a coffee-drinking, parka-wearing - and extremely pregnant - investigator who\'ll stop at nothing to get her man. And if you think her small-time investigative skills will give the crooks a run for their ransom... you betcha!',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/rt7cpEr1uP6RTZykBFhBTcRaKvG.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/747dgDfL5d8esobk7h4odaOFhUq.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 98,
                'release_date' => '1996-03-08',
                'original_language' => 'en',
                'popularity' => 20.791,
                'genres' => [80, 18, 53],
            ],
            [
                'id' => 675,
                'title' => 'Harry Potter and the Order of the Phoenix',
                'original_title' => 'Harry Potter and the Order of the Phoenix',
                'overview' => 'Returning for his fifth year of study at Hogwarts, Harry is stunned to find that his warnings about the return of Lord Voldemort have been ignored. Left with no choice, Harry takes matters into his own hands, training a small group of students to defend themselves against the dark arts.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/5aOyriWkPec0zUDxmHFP9qMmBaj.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/bsaA9qtIR6VTDQykXdBzJc8V5rS.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 138,
                'release_date' => '2007-06-28',
                'original_language' => 'en',
                'popularity' => 118.896,
                'genres' => [12, 14, 9648],
            ],
            [
                'id' => 503,
                'title' => 'Poseidon',
                'original_title' => 'Poseidon',
                'overview' => 'A packed cruise ship traveling the Atlantic is hit and overturned by a massive wave, compelling the passengers to begin a dramatic fight for their lives.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/cCx2Ead8KoZhHofsAAr7tyrjfDo.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/r1gEZd7BoXtLNEQSNMqfsMlMtIz.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 98,
                'release_date' => '2006-05-10',
                'original_language' => 'en',
                'popularity' => 22.847,
                'genres' => [12, 28, 18, 53],
            ],
            [
                'id' => 1271,
                'title' => '300',
                'original_title' => '300',
                'overview' => 'Based on Frank Miller\'s graphic novel, "300" is very loosely based the 480 B.C. Battle of Thermopylae, where the King of Sparta led his army against the advancing Persians; the battle is said to have inspired all of Greece to band together against the Persians, and helped usher in the world\'s first democracy.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/9W49fy5G7v9Ed3CXtvMi41YqZtt.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/eGhjeUbzttA3E4flxdAm8gHz4h4.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 117,
                'release_date' => '2007-03-07',
                'original_language' => 'en',
                'popularity' => 49.667,
                'genres' => [28, 12, 10752],
            ],
        ];

        $movies = [];
        foreach ($moviesData as $md) {
            $genreSlugs = $md['genres'];
            unset($md['genres']);
            $md['slug'] = Str::slug($md['title']);
            $movie = Movie::create($md);
            $movies[$movie->title] = $movie;

            // Link genres
            $genreIds = [];
            foreach ($genreSlugs as $slug) {
                if (isset($genres[$slug])) {
                    $genreIds[] = $genres[$slug]->genre_id;
                }
            }
            $movie->genres()->attach($genreIds);

            // Seed YouTube Trailer
            Video::create([
                'movie_id' => $movie->id,
                'name' => 'Official Trailer',
                'key_value' => 'dQw4w9WgXcQ', // Demo Youtube video key
                'site' => 'YouTube',
                'size' => 1080,
                'type' => 'Trailer',
                'official' => true,
                'published_at' => now(),
            ]);
        }

        echo "=> Seeding Persons & Credits...\n";
        // M1
        $this->seedCredits($movies['CineDot: The Beginning']->id, [
            ['name' => 'Edward Norton',        'profile_url' => 'https://images.tmdb.org/t/p/w185/5XBzD5WuTyVQZeS4VI25z2moMeY.jpg', 'character' => 'The Narrator',         'order' => 0],
            ['name' => 'Brad Pitt',            'profile_url' => 'https://images.tmdb.org/t/p/w185/cckcYc2v0yh1tc9QjRelptcOBko.jpg',  'character' => 'Tyler Durden',          'order' => 1],
            ['name' => 'Helena Bonham Carter', 'profile_url' => 'https://images.tmdb.org/t/p/w185/58oJPFG1wefMC0BnCsAoSEQBPko.jpg',  'character' => 'Marla Singer',          'order' => 2],
            ['name' => 'Meat Loaf',            'profile_url' => 'https://images.tmdb.org/t/p/w185/7FMHDdRHpUCPfJGBaLQU1MgF6Q2.jpg',  'character' => "Robert 'Bob' Paulsen", 'order' => 3],
            ['name' => 'Zach Grenier',         'profile_url' => 'https://images.tmdb.org/t/p/w185/rcMHs7uyFdSfGsM4hEjqYxEeOB2.jpg',  'character' => 'Richard Chesler',       'order' => 4],
            ['name' => 'Richmond Arquette',    'profile_url' => null,                                                                   'character' => 'Intern',                'order' => 5],
        ], [
            ['name' => 'David Fincher', 'profile_url' => 'https://images.tmdb.org/t/p/w185/tpEczFclQZuk81ATaw63meKLRxM.jpg', 'job' => 'Director',   'department' => 'Directing'],
            ['name' => 'Jim Uhls',      'profile_url' => null,                                                                 'job' => 'Screenplay', 'department' => 'Writing'],
            ['name' => 'Art Linson',    'profile_url' => null,                                                                 'job' => 'Producer',   'department' => 'Production'],
        ]);

        // M2
        $this->seedCredits($movies['Tanstack & Beyond']->id, [
            ['name' => 'Tanner Linsley',     'profile_url' => null, 'character' => 'Himself (Creator)',    'order' => 0],
            ['name' => 'Dominik Dorfmeister', 'profile_url' => null, 'character' => 'Himself (Co-creator)', 'order' => 1],
            ['name' => 'Kent C. Dodds',      'profile_url' => null, 'character' => 'Himself (Mentor)',     'order' => 2],
            ['name' => 'Josh Goldberg',      'profile_url' => null, 'character' => 'Senior Dev',           'order' => 3],
        ], [
            ['name' => 'Adam Wathan',    'profile_url' => null, 'job' => 'Director',  'department' => 'Directing'],
            ['name' => 'Steve Schoger',  'profile_url' => null, 'job' => 'Producer',  'department' => 'Production'],
            ['name' => 'Dan Abramov',    'profile_url' => null, 'job' => 'Consultant','department' => 'Writing'],
        ]);

        // M3
        $this->seedCredits($movies['Se7en']->id, [
            ['name' => 'Brad Pitt',       'profile_url' => 'https://images.tmdb.org/t/p/w185/cckcYc2v0yh1tc9QjRelptcOBko.jpg',  'character' => 'Detective Mills',    'order' => 0],
            ['name' => 'Morgan Freeman',  'profile_url' => 'https://images.tmdb.org/t/p/w185/oIciMBZgqcFkKGMKsGZYNM3BL9z.jpg',  'character' => 'Detective Somerset', 'order' => 1],
            ['name' => 'Kevin Spacey',    'profile_url' => 'https://images.tmdb.org/t/p/w185/lKhhk1sV9rBilEz41BkrFRlMGAQ.jpg',  'character' => 'John Doe',           'order' => 2],
            ['name' => 'Gwyneth Paltrow', 'profile_url' => 'https://images.tmdb.org/t/p/w185/uOhHKQtPbEMJhFCGMq0GDe6X5bD.jpg',  'character' => 'Tracy Mills',        'order' => 3],
        ], [
            ['name' => 'David Fincher',   'profile_url' => 'https://images.tmdb.org/t/p/w185/tpEczFclQZuk81ATaw63meKLRxM.jpg', 'job' => 'Director',      'department' => 'Directing'],
            ['name' => 'Andrew Kevin Walker','profile_url'=> null,                                                               'job' => 'Screenplay',    'department' => 'Writing'],
        ]);

        // M4
        $this->seedCredits($movies['Parasite']->id, [
            ['name' => 'Choi Woo-shik',   'profile_url' => 'https://images.tmdb.org/t/p/w185/dN7hSHNGlbWJVtR0Pu5iIRrfWJk.jpg', 'character' => 'Ki-woo',      'order' => 0],
            ['name' => 'Park So-dam',     'profile_url' => 'https://images.tmdb.org/t/p/w185/n87GSMNPJc2MBmfKHjxjjGV6U3p.jpg', 'character' => 'Ki-jung',     'order' => 1],
            ['name' => 'Song Kang-ho',    'profile_url' => 'https://images.tmdb.org/t/p/w185/2OuFtrgI2fEkUbJqWB4VFt3Kdwf.jpg', 'character' => 'Ki-taek',     'order' => 2],
        ], [
            ['name' => 'Bong Joon-ho',  'profile_url' => 'https://images.tmdb.org/t/p/w185/XzNTgOIKMV0lm0VVZB4aBcRqnk.jpg', 'job' => 'Director',   'department' => 'Directing'],
            ['name' => 'Han Jin-won',   'profile_url' => null,                                                                 'job' => 'Screenplay', 'department' => 'Writing'],
        ]);

        // M5
        $this->seedCredits($movies['GoodFellas']->id, [
            ['name' => 'Ray Liotta',      'profile_url' => 'https://images.tmdb.org/t/p/w185/rUOiU2OcARwfLpK5mHq3yZjmJfb.jpg', 'character' => 'Henry Hill',       'order' => 0],
            ['name' => 'Robert De Niro',  'profile_url' => 'https://images.tmdb.org/t/p/w185/cT8htcckIuyI1Bqx7KjpltQjLeA.jpg', 'character' => 'James Conway',     'order' => 1],
            ['name' => 'Joe Pesci',       'profile_url' => 'https://images.tmdb.org/t/p/w185/qCPSywnMoNLePVxphrAB5ew8Noc.jpg', 'character' => 'Tommy DeVito',     'order' => 2],
        ], [
            ['name' => 'Martin Scorsese', 'profile_url' => 'https://images.tmdb.org/t/p/w185/9U9Y5GQuWX3EZy39B8nkk4NY01S.jpg', 'job' => 'Director',   'department' => 'Directing'],
            ['name' => 'Nicholas Pileggi','profile_url' => null,                                                                  'job' => 'Screenplay', 'department' => 'Writing'],
        ]);

        // M6
        $this->seedCredits($movies['Pulp Fiction']->id, [
            ['name' => 'John Travolta',    'profile_url' => 'https://images.tmdb.org/t/p/w185/qOveRopRKKDZRXMsAqeakE1uSCE.jpg', 'character' => 'Vincent Vega',      'order' => 0],
            ['name' => 'Samuel L. Jackson', 'profile_url' => 'https://images.tmdb.org/t/p/w185/nCJJ3NiWkSUGBs9QdXfOmFEDiTS.jpg', 'character' => 'Jules Winnfield',   'order' => 1],
            ['name' => 'Uma Thurman',      'profile_url' => 'https://images.tmdb.org/t/p/w185/uTH5jXBGrXSEWDRQINUOAJk7q7U.jpg', 'character' => 'Mia Wallace',       'order' => 2],
        ], [
            ['name' => 'Quentin Tarantino','profile_url'=> 'https://images.tmdb.org/t/p/w185/1gjcpAa99FAOWGnrUvHEXXsRs7o.jpg', 'job' => 'Director',   'department' => 'Directing'],
            ['name' => 'Roger Avary',      'profile_url'=> null,                                                                  'job' => 'Screenplay', 'department' => 'Writing'],
        ]);

        echo "=> Seeding Reviews (for dynamic ratings calculation)...\n";
        // review averages targeting exact ratings:
        // M1 (CineDot): Target 9.5
        $this->seedReviews($movies['CineDot: The Beginning']->id, $users, [
            [10.0, 'Tuyệt tác về phần mềm! Đáng xem từng giây.'],
            [9.0, 'Phim rất truyền cảm hứng cho lập trình viên trẻ.'],
            [10.0, 'Đỉnh cao của clean architecture.'],
            [9.0, 'Excellent documentary. A must watch!'],
        ]);

        // M2 (Tanstack): Target 8.8
        $this->seedReviews($movies['Tanstack & Beyond']->id, $users, [
            [9.0, 'Tôi cực kỳ thích câu chuyện quản lý state trong này.'],
            [8.0, 'Nhịp phim nhanh, lôi cuốn.'],
            [9.0, 'Rất hữu ích để hiểu sâu về server state.'],
            [9.0, 'Fantastic storyline.'],
        ]);

        // M3 (Se7en): Target 8.6
        $this->seedReviews($movies['Se7en']->id, $users, [
            [9.0, 'Cực kỳ ám ảnh và nghẹt thở.'],
            [8.0, 'Diễn xuất của Brad Pitt quá đỉnh.'],
            [9.0, 'Cái kết hay nhất mọi thời đại.'],
            [8.0, 'Bầu không khí u tối xuất sắc.'],
            [9.0, 'One of the best psychological thrillers ever.'],
        ]);

        // M4 (Parasite): Target 8.5
        $this->seedReviews($movies['Parasite']->id, $users, [
            [9.0, 'Phản ánh xã hội sâu sắc, vừa hài hước vừa kinh dị.'],
            [8.0, 'Xứng đáng giải Oscars danh giá.'],
            [8.0, 'Kịch bản xuất sắc không kẽ hở.'],
            [9.0, 'Bong Joon-ho thật thiên tài!'],
        ]);

        // M5 (GoodFellas): Target 8.5
        $this->seedReviews($movies['GoodFellas']->id, $users, [
            [9.0, 'Phim gangster xuất sắc nhất mà tôi từng xem.'],
            [8.0, 'Ray Liotta đóng vai chính quá hay.'],
            [8.0, 'Martin Scorsese chỉ đạo tuyệt vời.'],
            [9.0, 'Classic mafia movie!'],
        ]);

        // M6 (Pulp Fiction): Target 8.9
        $this->seedReviews($movies['Pulp Fiction']->id, $users, [
            [9.0, 'Lời thoại cực kỳ độc đáo và chất.'],
            [9.0, 'Cấu trúc phi tuyến tính đỉnh cao.'],
            [9.0, 'Samuel L. Jackson diễn quá ngầu.'],
            [8.0, 'Một trong những phim hay nhất của Tarantino.'],
            [9.0, 'Iconic masterpiece!'],
        ]);

        echo "=> Seeding Reviews for 16 new movies...\n";
        $newMoviesRatings = [
            120 => 8.0,
            106 => 6.0,
            155 => 9.0,
            12  => 8.0,
            107 => 8.0,
            38  => 9.0,
            13  => 9.0,
            280 => 8.0,
            310 => 8.0,
            187 => 8.0,
            238 => 9.0,
            289 => 8.0,
            275 => 8.0,
            675 => 8.0,
            503 => 6.0,
            1271 => 8.0,
        ];

        foreach ($movies as $title => $movie) {
            if (isset($newMoviesRatings[$movie->id])) {
                $targetRating = $newMoviesRatings[$movie->id];
                $this->seedReviews($movie->id, $users, [
                    [$targetRating, 'Phim xem rất hay, cực kỳ thích kỹ xảo và âm thanh.'],
                    [$targetRating, 'Kịch bản cuốn hút từ đầu đến cuối.'],
                    [$targetRating, 'Diễn xuất của các diễn viên vô cùng tuyệt vời.'],
                ]);
            }
        }

        echo "=> Seeding Cinemas & Rooms...\n";
        $cinemasData = [
            ['name' => 'CGV Vincom Center Bà Triệu', 'province' => 'Hà Nội',           'address' => '191 Bà Triệu, Hai Bà Trưng',         'phone' => '19006017'],
            ['name' => 'Lotte Cinema Hà Nội',        'province' => 'Hà Nội',           'address' => '54 Liễu Giai, Ba Đình',              'phone' => '0243333000'],
            ['name' => 'CGV Aeon Mall Bình Dương',   'province' => 'TP. Hồ Chí Minh',  'address' => 'Aeon Mall Bình Dương',                'phone' => '19006018'],
            ['name' => 'Galaxy Nguyễn Du',          'province' => 'TP. Hồ Chí Minh',  'address' => '116 Nguyễn Du, Quận 1',              'phone' => '028393506'],
            ['name' => 'BHD Star Phạm Hùng',        'province' => 'TP. Hồ Chí Minh',  'address' => 'SC VivoCity, 1058 Nguyễn Văn Linh',  'phone' => '19002099'],
            ['name' => 'CGV Vĩnh Trung Plaza',       'province' => 'Đà Nẵng',          'address' => '255-257 Hùng Vương, Thanh Khê',     'phone' => '19006019'],
            ['name' => 'Lotte Cinema Cần Thơ',       'province' => 'Cần Thơ',          'address' => 'Mậu Thân, Xuân Khánh, Ninh Kiều',    'phone' => '029237688'],
        ];

        $rooms = [];
        foreach ($cinemasData as $cd) {
            $prov = $provinces[$cd['province']] ?? null;
            $cinema = Cinema::create([
                'cinema_name'    => $cd['name'],
                'slug'           => Str::slug($cd['name']),
                'cinema_address' => $cd['address'],
                'province_id'    => $prov ? $prov->province_id : null,
                'phone'          => $cd['phone'],
                'email'          => strtolower(Str::slug($cd['name'])) . '@cinedot.vn',
                'description'    => 'Rạp chiếu phim hiện đại tiêu chuẩn quốc tế tại ' . $cd['province'],
                'is_active'      => true,
            ]);

            // Mỗi rạp có 2 phòng chiếu
            $roomTypes = ['2D', '3D', 'IMAX', '4DX'];
            $roomNameSuffixes = ['Phòng 1', 'Phòng 2'];

            foreach ($roomNameSuffixes as $index => $suffix) {
                $type = $roomTypes[($cinema->cinema_id + $index) % 4];
                $room = Room::create([
                    'cinema_id'   => $cinema->cinema_id,
                    'room_name'   => $suffix . ' (' . $type . ')',
                    'room_type'   => $type,
                    'total_seats' => 48, // Grid 6x8
                    'is_active'   => true,
                ]);
                $rooms[] = $room;

                // Tạo seats cho phòng này (bulk insert để tăng tốc độ)
                $seatsInsert = [];
                $rows = ['A', 'B', 'C', 'D', 'E', 'F'];
                foreach ($rows as $rIndex => $row) {
                    for ($num = 1; $num <= 8; $num++) {
                        $seatType = ($row === 'E' || $row === 'F') ? 'vip' : 'standard';
                        $seatsInsert[] = [
                            'room_id'     => $room->room_id,
                            'seat_row'    => $row,
                            'seat_number' => $num,
                            'seat_type'   => $seatType,
                            'position_x'  => $num * 40,
                            'position_y'  => ($rIndex + 1) * 50,
                            'is_active'   => true,
                            'created_at'  => now(),
                        ];
                    }
                }
                DB::table('seats')->insert($seatsInsert);
            }
        }

        echo "=> Seeding Schedules & ScheduleSeats...\n";
        $today = now()->toDateString();
        $day2  = now()->addDay()->toDateString();
        $day3  = now()->addDays(2)->toDateString();

        $scheduleTemplates = [
            // [MovieTitle, RoomIndex, Date, StartTime, EndTime, BasePrice]
            ['Se7en', 0, $today, '09:00', '11:15', 75000],
            ['Se7en', 0, $today, '14:30', '16:45', 75000],
            ['Se7en', 1, $today, '20:00', '22:15', 95000],
            ['Parasite', 2, $today, '10:00', '12:15', 75000],
            ['Parasite', 2, $today, '19:30', '21:45', 75000],
            ['Pulp Fiction', 3, $today, '16:00', '18:40', 80000],
            ['GoodFellas', 4, $today, '11:00', '13:30', 85000],
            ['GoodFellas', 4, $today, '17:00', '19:30', 85000],
            ['Pulp Fiction', 5, $today, '20:30', '23:10', 90000],
            ['Se7en', 6, $today, '08:30', '10:45', 85000],
            
            // Ngày mai
            ['Se7en', 0, $day2, '10:00', '12:15', 75000],
            ['Pulp Fiction', 1, $day2, '15:30', '18:10', 85000],
            ['Parasite', 2, $day2, '20:00', '22:15', 90000],
            ['GoodFellas', 4, $day2, '14:00', '16:30', 80000],

            // Ngày kia
            ['Se7en', 0, $day3, '09:30', '11:45', 75000],
            ['GoodFellas', 3, $day3, '14:00', '16:30', 85000],
            ['CineDot: The Beginning', 8, $today, '09:00', '11:00', 70000],
            ['CineDot: The Beginning', 8, $today, '13:30', '15:30', 70000],
            ['Tanstack & Beyond', 9, $today, '15:00', '16:35', 75000],
            ['Tanstack & Beyond', 9, $today, '19:00', '20:35', 80000],

            // Lịch chiếu cho các phim mới
            ['The Lord of the Rings: The Fellowship of the Ring', 0, $today, '12:00', '15:00', 85000],
            ['The Dark Knight', 1, $today, '16:00', '18:30', 95000],
            ['Finding Nemo', 2, $today, '08:30', '10:10', 70000],
            ['The Godfather', 3, $today, '13:00', '16:00', 90000],
            ['Harry Potter and the Order of the Phoenix', 4, $today, '14:00', '16:20', 80000],
        ];

        foreach ($scheduleTemplates as $st) {
            [$title, $roomIdx, $date, $start, $end, $price] = $st;
            if (!isset($movies[$title]) || !isset($rooms[$roomIdx])) {
                continue;
            }

            $movie = $movies[$title];
            $room  = $rooms[$roomIdx];

            $schedule = Schedule::create([
                'movie_id'       => $movie->id,
                'room_id'        => $room->room_id,
                'schedule_date'  => $date,
                'schedule_start' => $start . ':00',
                'schedule_end'   => $end . ':00',
                'base_price'     => $price,
            ]);

            // Query seats thuộc room của schedule
            $seats = Seat::where('room_id', $room->room_id)->get();
            $scheduleSeatsInsert = [];
            foreach ($seats as $seat) {
                $seatPrice = ($seat->seat_type === 'vip') ? $price + 20000 : $price;
                $scheduleSeatsInsert[] = [
                    'schedule_id' => $schedule->schedule_id,
                    'seat_id'     => $seat->seat_id,
                    'status'      => 'available',
                    'price'       => $seatPrice,
                ];
            }
            DB::table('schedule_seats')->insert($scheduleSeatsInsert);
        }

        echo "=> Seeding Bookings & Payments (Demo records)...\n";
        // Lấy 3 schedules ngẫu nhiên để book
        $targetSchedules = Schedule::limit(3)->get();
        $targetUsers = collect($users)->slice(1, 3)->values();

        foreach ($targetSchedules as $index => $sch) {
            $user = $targetUsers[$index] ?? $users[0];
            
            // Lấy 2 ghế trống của suất này
            $schSeats = ScheduleSeat::where('schedule_id', $sch->schedule_id)
                ->where('status', 'available')
                ->limit(2)
                ->get();

            if ($schSeats->count() > 0) {
                $totalAmt = $schSeats->sum('price');
                
                $booking = Booking::create([
                    'user_id'        => $user->user_id,
                    'schedule_id'    => $sch->schedule_id,
                    'total_amount'   => $totalAmt,
                    'booking_code'   => 'CD' . strtoupper(Str::random(8)),
                    'booking_status' => 'completed',
                ]);

                foreach ($schSeats as $ss) {
                    // Cập nhật trạng thái ghế trong suất chiếu thành 'booked'
                    $ss->update(['status' => 'booked']);

                    BookingSeat::create([
                        'booking_id'       => $booking->booking_id,
                        'schedule_seat_id' => $ss->schedule_seat_id,
                    ]);
                }

                Payment::create([
                    'booking_id'     => $booking->booking_id,
                    'amount'         => $totalAmt,
                    'method'         => 'vnpay',
                    'status'         => 'success',
                    'transaction_id' => 'TXN' . strtoupper(Str::random(12)),
                    'paid_at'        => now(),
                ]);

                // Cộng điểm thưởng tích luỹ cho user
                $user->increment('point', round($totalAmt / 10000));
            }
        }

        echo "=> DB Seeding Completed Successfully!\n";
    }

    /**
     * Helper: Tạo Person (hoặc lấy lại) & thêm Credits
     */
    private function seedCredits(int $movieId, array $castData, array $crewData): void
    {
        foreach ($castData as $c) {
            $person = Person::firstOrCreate(
                ['name' => $c['name']],
                [
                    'profile_path'         => $c['profile_url'] ?? null,
                    'gender'               => isset($c['gender']) ? $c['gender'] : 2,
                    'known_for_department' => 'Acting',
                ]
            );

            Credit::create([
                'movie_id'       => $movieId,
                'person_id'      => $person->person_id,
                'credit_type'    => 'cast',
                'character_name' => $c['character'],
                'order'          => $c['order'],
            ]);
        }

        foreach ($crewData as $c) {
            $person = Person::firstOrCreate(
                ['name' => $c['name']],
                [
                    'profile_path'         => $c['profile_url'] ?? null,
                    'gender'               => 2,
                    'known_for_department' => $c['department'] ?? 'Directing',
                ]
            );

            Credit::create([
                'movie_id'    => $movieId,
                'person_id'   => $person->person_id,
                'credit_type' => 'crew',
                'job'         => $c['job'],
                'department'  => $c['department'],
            ]);
        }
    }

    /**
     * Helper: Tạo Reviews mẫu
     */
    private function seedReviews(int $movieId, array $usersList, array $reviewsList): void
    {
        foreach ($reviewsList as $idx => $rev) {
            $user = $usersList[$idx % count($usersList)];
            [$rating, $comment] = $rev;

            Review::create([
                'user_id'    => $user->user_id,
                'movie_id'   => $movieId,
                'rating'     => $rating,
                'comment'    => $comment,
                'created_at' => now()->subDays(rand(1, 10)),
            ]);
        }
    }
}
