<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;
use App\Models\Genre;
use App\Models\Person;
use App\Models\User;
use App\Models\Cinema;
use App\Models\Showtime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed toàn bộ dữ liệu CineDot:
     *  - 8 genres
     *  - 6 movies (CineDot, Tanstack, Se7en, Parasite, GoodFellas, Pulp Fiction)
     *  - People + Cast + Crew cho TẤT CẢ 6 phim (~10-15 người mỗi phim)
     *  - 3 users test (admin, editor, viewer)
     */
    public function run(): void
    {
        // ============================================================
        // GENRES
        // ============================================================
        $documentary = Genre::firstOrCreate(['slug' => 'documentary'], ['name' => 'Documentary']);
        $tech        = Genre::firstOrCreate(['slug' => 'tech'],        ['name' => 'Tech']);
        $action      = Genre::firstOrCreate(['slug' => 'action'],      ['name' => 'Action']);
        $crime       = Genre::firstOrCreate(['slug' => 'crime'],       ['name' => 'Crime']);
        $mystery     = Genre::firstOrCreate(['slug' => 'mystery'],     ['name' => 'Mystery']);
        $comedy      = Genre::firstOrCreate(['slug' => 'comedy'],      ['name' => 'Comedy']);
        $thriller    = Genre::firstOrCreate(['slug' => 'thriller'],    ['name' => 'Thriller']);
        $drama       = Genre::firstOrCreate(['slug' => 'drama'],       ['name' => 'Drama']);

        // ============================================================
        // MOVIES
        // ============================================================

        // ID 1 – CineDot: The Beginning
        $m1 = Movie::create([
            'title'        => 'CineDot: The Beginning',
            'overview'     => "A senior architect's journey to build the perfect movie app. This documentary explores the challenges of clean architecture and the beauty of modular design.",
            'poster_url'   => 'https://images.tmdb.org/t/p/w500/poster1.jpg',
            'backdrop_url' => 'https://images.tmdb.org/t/p/original/backdrop1.jpg',
            'trailer_url'  => null,
            'status'       => 'now_showing',
            'runtime'      => 120,
            'release_date' => '2024-05-14',
            'rating'       => 9.5,
            'vote_count'   => 1200,
        ]);
        $m1->genres()->attach([$documentary->id, $tech->id]);

        // ID 2 – Tanstack & Beyond
        $m2 = Movie::create([
            'title'        => 'Tanstack & Beyond',
            'overview'     => 'The epic story of state management and the developers who dared to dream of a server-state revolution.',
            'poster_url'   => 'https://images.tmdb.org/t/p/w500/poster2.jpg',
            'backdrop_url' => 'https://images.tmdb.org/t/p/original/backdrop2.jpg',
            'trailer_url'  => null,
            'status'       => 'now_showing',
            'runtime'      => 95,
            'release_date' => '2024-06-20',
            'rating'       => 8.8,
            'vote_count'   => 850,
        ]);
        $m2->genres()->attach([$action->id, $tech->id]);

        // ID 3 – Se7en
        $m3 = Movie::create([
            'title'        => 'Se7en',
            'overview'     => 'Two homicide detectives are on a desperate hunt for a serial killer whose crimes are based on the seven deadly sins.',
            'poster_url'   => 'https://images.tmdb.org/t/p/w185/6yoghtyTpznpBik8EngEmJskVUO.jpg',
            'backdrop_url' => 'https://images.tmdb.org/t/p/w342/yw9LkPVBXQCNBHMoVuWHcuYe6RM.jpg',
            'trailer_url'  => null,
            'status'       => 'now_showing',
            'runtime'      => 127,
            'release_date' => '1995-09-22',
            'rating'       => 8.6,
            'vote_count'   => 21000,
        ]);
        $m3->genres()->attach([$crime->id, $mystery->id, $thriller->id]);

        // ID 4 – Parasite
        $m4 = Movie::create([
            'title'        => 'Parasite',
            'overview'     => "All unemployed, Ki-taek's family takes peculiar interest in the wealthy and glamorous Park family.",
            'poster_url'   => 'https://images.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg',
            'backdrop_url' => 'https://images.tmdb.org/t/p/original/TU9NIjwzjoKPwQHoHshkFcQUCG.jpg',
            'trailer_url'  => null,
            'status'       => 'now_showing',
            'runtime'      => 132,
            'release_date' => '2019-05-30',
            'rating'       => 8.5,
            'vote_count'   => 17000,
        ]);
        $m4->genres()->attach([$comedy->id, $thriller->id, $drama->id]);

        // ID 5 – GoodFellas
        $m5 = Movie::create([
            'title'        => 'GoodFellas',
            'overview'     => 'The true story of Henry Hill, a half-Irish, half-Sicilian Brooklyn kid who is adopted by the Mob.',
            'poster_url'   => 'https://images.tmdb.org/t/p/w500/aKuFiU82s5ISJpGZp7YkIr3kCUd.jpg',
            'backdrop_url' => 'https://images.tmdb.org/t/p/original/sw7mordbZxgITU877yTpZCud90M.jpg',
            'trailer_url'  => null,
            'status'       => 'now_showing',
            'runtime'      => 146,
            'release_date' => '1990-09-12',
            'rating'       => 8.5,
            'vote_count'   => 11000,
        ]);
        $m5->genres()->attach([$drama->id, $crime->id]);

        // ID 6 – Pulp Fiction
        $m6 = Movie::create([
            'title'        => 'Pulp Fiction',
            'overview'     => 'The lives of two mob hitmen, a boxer, a gangster and his wife intertwine in four tales of violence and redemption.',
            'poster_url'   => 'https://images.tmdb.org/t/p/w500/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg',
            'backdrop_url' => 'https://images.tmdb.org/t/p/original/4cDFJr4HnXN5AdPw4AKrmLlMWdO.jpg',
            'trailer_url'  => null,
            'status'       => 'now_showing',
            'runtime'      => 154,
            'release_date' => '1994-09-10',
            'rating'       => 8.9,
            'vote_count'   => 27000,
        ]);
        $m6->genres()->attach([$thriller->id, $crime->id, $drama->id]);

        // ============================================================
        // PEOPLE + CREDITS
        // Mỗi phim có Cast (7-8 người) + Crew (4-5 người) = ~11-13 records
        // ============================================================

        // ── M1: CineDot: The Beginning (Fight Club cast làm demo) ────────
        $this->seedCredits($m1->id, [
            // cast
            ['name' => 'Edward Norton',        'profile_url' => 'https://images.tmdb.org/t/p/w185/5XBzD5WuTyVQZeS4VI25z2moMeY.jpg', 'character' => 'The Narrator',         'order' => 0],
            ['name' => 'Brad Pitt',            'profile_url' => 'https://images.tmdb.org/t/p/w185/cckcYc2v0yh1tc9QjRelptcOBko.jpg',  'character' => 'Tyler Durden',          'order' => 1],
            ['name' => 'Helena Bonham Carter', 'profile_url' => 'https://images.tmdb.org/t/p/w185/58oJPFG1wefMC0BnCsAoSEQBPko.jpg',  'character' => 'Marla Singer',          'order' => 2],
            ['name' => 'Meat Loaf',            'profile_url' => 'https://images.tmdb.org/t/p/w185/7FMHDdRHpUCPfJGBaLQU1MgF6Q2.jpg',  'character' => "Robert 'Bob' Paulsen", 'order' => 3],
            ['name' => 'Zach Grenier',         'profile_url' => 'https://images.tmdb.org/t/p/w185/rcMHs7uyFdSfGsM4hEjqYxEeOB2.jpg',  'character' => 'Richard Chesler',       'order' => 4],
            ['name' => 'Richmond Arquette',    'profile_url' => null,                                                                   'character' => 'Intern',                'order' => 5],
            ['name' => 'David Andrews',        'profile_url' => null,                                                                   'character' => 'Thomas',                'order' => 6],
            ['name' => 'Holt McCallany',       'profile_url' => null,                                                                   'character' => 'The Mechanic',          'order' => 7],
        ], [
            // crew
            ['name' => 'David Fincher', 'profile_url' => 'https://images.tmdb.org/t/p/w185/tpEczFclQZuk81ATaw63meKLRxM.jpg', 'job' => 'Director',   'department' => 'Directing'],
            ['name' => 'Jim Uhls',      'profile_url' => null,                                                                 'job' => 'Screenplay', 'department' => 'Writing'],
            ['name' => 'Art Linson',    'profile_url' => null,                                                                 'job' => 'Producer',   'department' => 'Production'],
            ['name' => 'Ceán Chaffin',  'profile_url' => null,                                                                 'job' => 'Producer',   'department' => 'Production'],
            ['name' => 'Jeff Cronenweth','profile_url'=> null,                                                                 'job' => 'Director of Photography', 'department' => 'Camera'],
        ]);

        // ── M2: Tanstack & Beyond ─────────────────────────────────────────
        $this->seedCredits($m2->id, [
            ['name' => 'Tanner Linsley',  'profile_url' => null, 'character' => 'Himself (Creator)',    'order' => 0],
            ['name' => 'Dominik Dorfmeister','profile_url'=> null,'character' => 'Himself (Co-creator)', 'order' => 1],
            ['name' => 'Kent C. Dodds',   'profile_url' => null, 'character' => 'Himself (Mentor)',     'order' => 2],
            ['name' => 'Josh Goldberg',   'profile_url' => null, 'character' => 'Senior Dev',           'order' => 3],
            ['name' => 'Ryan Florence',   'profile_url' => null, 'character' => 'Remix Evangelist',     'order' => 4],
            ['name' => 'Theo Browne',     'profile_url' => null, 'character' => 'The Streamer',         'order' => 5],
            ['name' => 'Lee Robinson',    'profile_url' => null, 'character' => 'Junior Dev',           'order' => 6],
        ], [
            ['name' => 'Adam Wathan',    'profile_url' => null, 'job' => 'Director',  'department' => 'Directing'],
            ['name' => 'Steve Schoger',  'profile_url' => null, 'job' => 'Producer',  'department' => 'Production'],
            ['name' => 'Dan Abramov',    'profile_url' => null, 'job' => 'Consultant','department' => 'Writing'],
            ['name' => 'Evan You',       'profile_url' => null, 'job' => 'Composer',  'department' => 'Sound'],
        ]);

        // ── M3: Se7en ────────────────────────────────────────────────────
        $this->seedCredits($m3->id, [
            ['name' => 'Brad Pitt',       'profile_url' => 'https://images.tmdb.org/t/p/w185/cckcYc2v0yh1tc9QjRelptcOBko.jpg',  'character' => 'Detective Mills',    'order' => 0],
            ['name' => 'Morgan Freeman',  'profile_url' => 'https://images.tmdb.org/t/p/w185/oIciMBZgqcFkKGMKsGZYNM3BL9z.jpg',  'character' => 'Detective Somerset', 'order' => 1],
            ['name' => 'Kevin Spacey',    'profile_url' => 'https://images.tmdb.org/t/p/w185/lKhhk1sV9rBilEz41BkrFRlMGAQ.jpg',  'character' => 'John Doe',           'order' => 2],
            ['name' => 'Gwyneth Paltrow', 'profile_url' => 'https://images.tmdb.org/t/p/w185/uOhHKQtPbEMJhFCGMq0GDe6X5bD.jpg',  'character' => 'Tracy Mills',        'order' => 3],
            ['name' => 'R. Lee Ermey',    'profile_url' => null,                                                                   'character' => 'Police Captain',     'order' => 4],
            ['name' => 'John C. McGinley','profile_url' => null,                                                                   'character' => 'California',         'order' => 5],
            ['name' => 'Leland Orser',    'profile_url' => null,                                                                   'character' => 'Lust Victim',        'order' => 6],
            ['name' => 'Richard Roundtree','profile_url'=> null,                                                                   'character' => 'Talbot',             'order' => 7],
        ], [
            ['name' => 'David Fincher',   'profile_url' => 'https://images.tmdb.org/t/p/w185/tpEczFclQZuk81ATaw63meKLRxM.jpg', 'job' => 'Director',      'department' => 'Directing'],
            ['name' => 'Andrew Kevin Walker','profile_url'=> null,                                                               'job' => 'Screenplay',    'department' => 'Writing'],
            ['name' => 'Arnold Kopelson', 'profile_url' => null,                                                                 'job' => 'Producer',      'department' => 'Production'],
            ['name' => 'Howard Shore',    'profile_url' => null,                                                                 'job' => 'Original Score','department' => 'Sound'],
            ['name' => 'Darius Khondji',  'profile_url' => null,                                                                 'job' => 'Cinematography','department' => 'Camera'],
        ]);

        // ── M4: Parasite ─────────────────────────────────────────────────
        $this->seedCredits($m4->id, [
            ['name' => 'Choi Woo-shik',   'profile_url' => 'https://images.tmdb.org/t/p/w185/dN7hSHNGlbWJVtR0Pu5iIRrfWJk.jpg', 'character' => 'Ki-woo',      'order' => 0],
            ['name' => 'Park So-dam',     'profile_url' => 'https://images.tmdb.org/t/p/w185/n87GSMNPJc2MBmfKHjxjjGV6U3p.jpg', 'character' => 'Ki-jung',     'order' => 1],
            ['name' => 'Song Kang-ho',    'profile_url' => 'https://images.tmdb.org/t/p/w185/2OuFtrgI2fEkUbJqWB4VFt3Kdwf.jpg', 'character' => 'Ki-taek',     'order' => 2],
            ['name' => 'Jang Hye-jin',    'profile_url' => null,                                                                  'character' => 'Chung-sook',  'order' => 3],
            ['name' => 'Lee Sun-kyun',    'profile_url' => null,                                                                  'character' => 'Mr. Park',    'order' => 4],
            ['name' => 'Cho Yeo-jeong',   'profile_url' => null,                                                                  'character' => 'Yeon-gyo',    'order' => 5],
            ['name' => 'Choi Woo-sik',    'profile_url' => null,                                                                  'character' => 'Da-song',     'order' => 6],
            ['name' => 'Lee Jung-eun',    'profile_url' => null,                                                                  'character' => 'Moon-gwang',  'order' => 7],
        ], [
            ['name' => 'Bong Joon-ho',  'profile_url' => 'https://images.tmdb.org/t/p/w185/XzNTgOIKMV0lm0VVZB4aBcRqnk.jpg', 'job' => 'Director',   'department' => 'Directing'],
            ['name' => 'Han Jin-won',   'profile_url' => null,                                                                 'job' => 'Screenplay', 'department' => 'Writing'],
            ['name' => 'Kwak Sin-ae',   'profile_url' => null,                                                                 'job' => 'Producer',   'department' => 'Production'],
            ['name' => 'Jung Jae-il',   'profile_url' => null,                                                                 'job' => 'Composer',   'department' => 'Sound'],
            ['name' => 'Hong Kyung-pyo','profile_url' => null,                                                                 'job' => 'Director of Photography', 'department' => 'Camera'],
        ]);

        // ── M5: GoodFellas ───────────────────────────────────────────────
        $this->seedCredits($m5->id, [
            ['name' => 'Ray Liotta',      'profile_url' => 'https://images.tmdb.org/t/p/w185/rUOiU2OcARwfLpK5mHq3yZjmJfb.jpg', 'character' => 'Henry Hill',       'order' => 0],
            ['name' => 'Robert De Niro',  'profile_url' => 'https://images.tmdb.org/t/p/w185/cT8htcckIuyI1Bqx7KjpltQjLeA.jpg', 'character' => 'James Conway',     'order' => 1],
            ['name' => 'Joe Pesci',       'profile_url' => 'https://images.tmdb.org/t/p/w185/qCPSywnMoNLePVxphrAB5ew8Noc.jpg', 'character' => 'Tommy DeVito',     'order' => 2],
            ['name' => 'Lorraine Bracco', 'profile_url' => 'https://images.tmdb.org/t/p/w185/9pAWVLlB4Vgm3b4pFBPWLnXFEaU.jpg', 'character' => 'Karen Hill',       'order' => 3],
            ['name' => 'Paul Sorvino',    'profile_url' => null,                                                                  'character' => 'Paul Cicero',      'order' => 4],
            ['name' => 'Frank Sivero',    'profile_url' => null,                                                                  'character' => 'Frankie Carbone',  'order' => 5],
            ['name' => 'Tony Darrow',     'profile_url' => null,                                                                  'character' => 'Sonny Bunz',       'order' => 6],
            ['name' => 'Mike Starr',      'profile_url' => null,                                                                  'character' => 'Frenchy',          'order' => 7],
        ], [
            ['name' => 'Martin Scorsese', 'profile_url' => 'https://images.tmdb.org/t/p/w185/9U9Y5GQuWX3EZy39B8nkk4NY01S.jpg', 'job' => 'Director',   'department' => 'Directing'],
            ['name' => 'Nicholas Pileggi','profile_url' => null,                                                                  'job' => 'Screenplay', 'department' => 'Writing'],
            ['name' => 'Irwin Winkler',   'profile_url' => null,                                                                  'job' => 'Producer',   'department' => 'Production'],
            ['name' => 'Thelma Schoonmaker','profile_url'=> null,                                                                 'job' => 'Editor',     'department' => 'Editing'],
            ['name' => 'Michael Ballhaus','profile_url' => null,                                                                  'job' => 'Cinematography','department' => 'Camera'],
        ]);

        // ── M6: Pulp Fiction ─────────────────────────────────────────────
        $this->seedCredits($m6->id, [
            ['name' => 'John Travolta',   'profile_url' => 'https://images.tmdb.org/t/p/w185/qOveRopRKKDZRXMsAqeakE1uSCE.jpg', 'character' => 'Vincent Vega',      'order' => 0],
            ['name' => 'Samuel L. Jackson','profile_url'=> 'https://images.tmdb.org/t/p/w185/nCJJ3NiWkSUGBs9QdXfOmFEDiTS.jpg', 'character' => 'Jules Winnfield',   'order' => 1],
            ['name' => 'Uma Thurman',     'profile_url' => 'https://images.tmdb.org/t/p/w185/uTH5jXBGrXSEWDRQINUOAJk7q7U.jpg', 'character' => 'Mia Wallace',       'order' => 2],
            ['name' => 'Bruce Willis',    'profile_url' => 'https://images.tmdb.org/t/p/w185/kI1OluWhLJk3pnR3ebUkebDInMm.jpg', 'character' => 'Butch Coolidge',    'order' => 3],
            ['name' => 'Harvey Keitel',   'profile_url' => 'https://images.tmdb.org/t/p/w185/oTkAAlGmHHGBGKN31yBPFObiSgw.jpg', 'character' => 'Winston Wolfe',     'order' => 4],
            ['name' => 'Tim Roth',        'profile_url' => null,                                                                  'character' => 'Pumpkin',           'order' => 5],
            ['name' => 'Amanda Plummer',  'profile_url' => null,                                                                  'character' => 'Honey Bunny',       'order' => 6],
            ['name' => 'Ving Rhames',     'profile_url' => null,                                                                  'character' => 'Marsellus Wallace', 'order' => 7],
            ['name' => 'Eric Stoltz',     'profile_url' => null,                                                                  'character' => 'Lance',             'order' => 8],
        ], [
            ['name' => 'Quentin Tarantino','profile_url'=> 'https://images.tmdb.org/t/p/w185/1gjcpAa99FAOWGnrUvHEXXsRs7o.jpg', 'job' => 'Director',   'department' => 'Directing'],
            ['name' => 'Roger Avary',      'profile_url'=> null,                                                                  'job' => 'Screenplay', 'department' => 'Writing'],
            ['name' => 'Lawrence Bender',  'profile_url'=> null,                                                                  'job' => 'Producer',   'department' => 'Production'],
            ['name' => 'Sally Menke',      'profile_url'=> null,                                                                  'job' => 'Editor',     'department' => 'Editing'],
            ['name' => 'Andrzej Sekula',   'profile_url'=> null,                                                                  'job' => 'Director of Photography', 'department' => 'Camera'],
        ]);

        // ============================================================
        // USERS (test accounts)
        // ============================================================
        User::create([
            'name'     => 'Admin CineDot',
            'email'    => 'admin@cinedot.com',
            'password' => Hash::make('password'),
        ]);
        User::create([
            'name'     => 'Editor CineDot',
            'email'    => 'editor@cinedot.com',
            'password' => Hash::make('password'),
        ]);
        User::create([
            'name'     => 'Viewer Test',
            'email'    => 'viewer@cinedot.com',
            'password' => Hash::make('password'),
        ]);

        // ============================================================
        // CINEMAS (5 rạp: HN + HCM)
        // ============================================================
        $cgvVincom    = Cinema::create(['name' => 'CGV Vincom Center Bà Triệu', 'chain' => 'CGV',    'city' => 'Hà Nội',  'address' => '191 Bà Triệu, Hai Bà Trưng',          'slug' => 'cgv-vincom-ba-trieu',       'screen_count' => 8]);
        $lotteHN      = Cinema::create(['name' => 'Lotte Cinema Hà Nội',        'chain' => 'Lotte',  'city' => 'Hà Nội',  'address' => '54 Liễu Giai, Ba Đình',               'slug' => 'lotte-cinema-ha-noi',       'screen_count' => 6]);
        $cgvAeonHCM   = Cinema::create(['name' => 'CGV Aeon Mall Bình Dương',   'chain' => 'CGV',    'city' => 'TP.HCM',  'address' => 'Aeon Mall Bình Dương',                 'slug' => 'cgv-aeon-binh-duong',       'screen_count' => 10]);
        $galaxyNguyenDu= Cinema::create(['name' => 'Galaxy Nguyễn Du',          'chain' => 'Galaxy', 'city' => 'TP.HCM',  'address' => '116 Nguyễn Du, Quận 1',               'slug' => 'galaxy-nguyen-du',          'screen_count' => 5]);
        $bhdPremium   = Cinema::create(['name' => 'BHD Star Phạm Hùng',        'chain' => 'BHD',    'city' => 'TP.HCM',  'address' => 'SC VivoCity, 1058 Nguyễn Văn Linh',   'slug' => 'bhd-star-pham-hung',        'screen_count' => 7]);

        // ============================================================
        // SHOWTIMES (3 ngày tới, 6 phim × 3-5 rạp)
        // ============================================================
        $movies = Movie::all()->keyBy('title');
        $today  = now()->toDateString();
        $day2   = now()->addDay()->toDateString();
        $day3   = now()->addDays(2)->toDateString();

        $schedule = [
            // [cinema, movie_title, date, start, end, screen, format, price, seats]
            [$cgvVincom,     'Se7en',              $today, '09:00', '11:15', 'Phòng 1', '2D',   75000, 120],
            [$cgvVincom,     'Se7en',              $today, '14:30', '16:45', 'Phòng 1', '2D',   75000, 95],
            [$cgvVincom,     'Se7en',              $today, '20:00', '22:15', 'Phòng 2', '3D',   95000, 140],
            [$cgvVincom,     'Parasite',           $today, '10:00', '12:15', 'Phòng 3', '2D',   75000, 110],
            [$cgvVincom,     'Parasite',           $today, '19:30', '21:45', 'Phòng 3', '2D',   75000, 88],
            [$cgvVincom,     'Pulp Fiction',       $today, '16:00', '18:40', 'Phòng 4', '2D',   75000, 130],
            [$lotteHN,       'GoodFellas',         $today, '11:00', '13:30', 'Phòng A', '2D',   80000, 100],
            [$lotteHN,       'GoodFellas',         $today, '17:00', '19:30', 'Phòng A', '2D',   80000, 72],
            [$lotteHN,       'Pulp Fiction',       $today, '20:30', '23:10', 'Phòng B', '2D',   80000, 115],
            [$lotteHN,       'Se7en',              $today, '08:30', '10:45', 'Phòng C', '2D',   80000, 90],
            [$cgvAeonHCM,    'Se7en',              $today, '10:30', '12:45', 'Phòng 5', 'IMAX', 150000, 200],
            [$cgvAeonHCM,    'Se7en',              $today, '15:00', '17:15', 'Phòng 5', 'IMAX', 150000, 180],
            [$cgvAeonHCM,    'Parasite',           $today, '13:00', '15:15', 'Phòng 6', '4DX',  130000, 90],
            [$cgvAeonHCM,    'GoodFellas',         $today, '18:00', '20:30', 'Phòng 7', '2D',   85000, 160],
            [$cgvAeonHCM,    'Pulp Fiction',       $today, '21:00', '23:40', 'Phòng 8', '3D',   100000, 140],
            [$galaxyNguyenDu,'Pulp Fiction',       $today, '14:00', '16:40', 'Phòng 2', '2D',   70000, 80],
            [$galaxyNguyenDu,'Parasite',           $today, '19:00', '21:15', 'Phòng 1', '2D',   70000, 95],
            [$bhdPremium,    'GoodFellas',         $today, '10:00', '12:30', 'Phòng P1','2D',   90000, 110],
            [$bhdPremium,    'Se7en',              $today, '16:30', '18:45', 'Phòng P2','3D',   110000, 120],
            // Day 2
            [$cgvVincom,     'Se7en',              $day2,  '10:00', '12:15', 'Phòng 1', '2D',   75000, 140],
            [$cgvVincom,     'Pulp Fiction',       $day2,  '15:30', '18:10', 'Phòng 2', '2D',   75000, 120],
            [$cgvVincom,     'Parasite',           $day2,  '20:00', '22:15', 'Phòng 3', '3D',   95000, 100],
            [$lotteHN,       'GoodFellas',         $day2,  '14:00', '16:30', 'Phòng A', '2D',   80000, 88],
            [$cgvAeonHCM,    'Se7en',              $day2,  '11:00', '13:15', 'Phòng 5', 'IMAX', 150000, 200],
            [$cgvAeonHCM,    'Parasite',           $day2,  '17:30', '19:45', 'Phòng 6', '4DX',  130000, 90],
            [$galaxyNguyenDu,'Pulp Fiction',       $day2,  '19:30', '22:10', 'Phòng 1', '2D',   70000, 95],
            [$bhdPremium,    'GoodFellas',         $day2,  '20:30', '23:00', 'Phòng P1','2D',   90000, 115],
            // Day 3
            [$cgvVincom,     'Se7en',              $day3,  '09:30', '11:45', 'Phòng 1', '2D',   75000, 140],
            [$cgvVincom,     'GoodFellas',         $day3,  '14:00', '16:30', 'Phòng 4', '2D',   75000, 110],
            [$cgvAeonHCM,    'Pulp Fiction',       $day3,  '18:00', '20:40', 'Phòng 8', '3D',   100000, 140],
            [$galaxyNguyenDu,'Parasite',           $day3,  '20:00', '22:15', 'Phòng 2', '2D',   70000, 80],
            [$bhdPremium,    'Se7en',              $day3,  '15:00', '17:15', 'Phòng P2','IMAX', 150000, 130],
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
    }

    /**
     * Helper: tạo Person + gắn vào movie_cast / movie_crew
     */
    private function seedCredits(int $movieId, array $castData, array $crewData): void
    {
        foreach ($castData as $c) {
            $person = Person::create([
                'name'        => $c['name'],
                'profile_url' => $c['profile_url'],
            ]);
            DB::table('movie_cast')->insert([
                'movie_id'  => $movieId,
                'person_id' => $person->id,
                'character' => $c['character'],
                'order'     => $c['order'],
            ]);
        }

        foreach ($crewData as $c) {
            $person = Person::create([
                'name'        => $c['name'],
                'profile_url' => $c['profile_url'],
            ]);
            DB::table('movie_crew')->insert([
                'movie_id'   => $movieId,
                'person_id'  => $person->id,
                'job'        => $c['job'],
                'department' => $c['department'],
            ]);
        }
    }
}
