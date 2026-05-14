<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;
use App\Models\Genre;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed toàn bộ dữ liệu từ thư mục git/:
     *  - movie-detail.json   → CineDot: The Beginning
     *  - movies-popular.json → Tanstack & Beyond
     *  - movie-similar.json  → Se7en, Parasite, GoodFellas, Pulp Fiction
     *  - movie-credits.json  → Cast & Crew (gắn cho movie ID 1 làm demo)
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

        // movie-detail.json → ID 1: CineDot: The Beginning | runtime: 120 = 2h0phút
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

        // movies-popular.json → ID 2: Tanstack & Beyond | runtime: 95 = 1h35phút
        $m2 = Movie::create([
            'title'        => 'Tanstack & Beyond',
            'overview'     => 'The epic story of state management.',
            'poster_url'   => 'https://images.tmdb.org/t/p/w500/poster2.jpg',
            'backdrop_url' => 'https://images.tmdb.org/t/p/original/backdrop2.jpg',
            'trailer_url'  => null,
            'status'       => 'now_showing',
            'runtime'      => 95,
            'release_date' => '2024-06-20',
            'rating'       => 8.8,
            'vote_count'   => 850,
        ]);
        $m2->genres()->attach([$action->id]);

        // movie-similar.json → Se7en | runtime: 127 = 2h7phút
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
        $m3->genres()->attach([$crime->id, $mystery->id]);

        // movie-similar.json → Parasite | runtime: 132 = 2h12phút
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
        $m4->genres()->attach([$comedy->id, $thriller->id]);

        // movie-similar.json → GoodFellas | runtime: 146 = 2h26phút
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

        // movie-similar.json → Pulp Fiction | runtime: 154 = 2h34phút
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
        $m6->genres()->attach([$thriller->id, $crime->id]);

        // ============================================================
        // PEOPLE + CREDITS (từ movie-credits.json)
        // Credits được gắn vào $m1 (CineDot: The Beginning) làm demo
        // ============================================================

        // -- Cast --
        $castData = [
            ['id' => 819,   'name' => 'Edward Norton',       'profile_url' => 'https://images.tmdb.org/t/p/w185/5XBzD5WuTyVQZeS4VI25z2moMeY.jpg', 'character' => 'The Narrator',          'order' => 0],
            ['id' => 287,   'name' => 'Brad Pitt',           'profile_url' => 'https://images.tmdb.org/t/p/w185/cckcYc2v0yh1tc9QjRelptcOBko.jpg',  'character' => 'Tyler Durden',           'order' => 1],
            ['id' => 1283,  'name' => 'Helena Bonham Carter','profile_url' => 'https://images.tmdb.org/t/p/w185/58oJPFG1wefMC0BnCsAoSEQBPko.jpg',  'character' => 'Marla Singer',           'order' => 2],
            ['id' => 7470,  'name' => 'Meat Loaf',           'profile_url' => 'https://images.tmdb.org/t/p/w185/7FMHDdRHpUCPfJGBaLQU1MgF6Q2.jpg',  'character' => "Robert 'Bob' Paulsen",  'order' => 3],
            ['id' => 7499,  'name' => 'Zach Grenier',        'profile_url' => 'https://images.tmdb.org/t/p/w185/rcMHs7uyFdSfGsM4hEjqYxEeOB2.jpg',  'character' => 'Richard Chesler',        'order' => 4],
            ['id' => 7471,  'name' => 'Richmond Arquette',   'profile_url' => null,                                                                   'character' => 'Intern',                 'order' => 5],
            ['id' => 69280, 'name' => 'David Andrews',       'profile_url' => null,                                                                   'character' => 'Thomas',                 'order' => 6],
            ['id' => 7486,  'name' => 'Holt McCallany',      'profile_url' => null,                                                                   'character' => 'The Mechanic',           'order' => 7],
        ];

        foreach ($castData as $c) {
            $person = Person::create(['name' => $c['name'], 'profile_url' => $c['profile_url']]);
            DB::table('movie_cast')->insert([
                'movie_id'  => $m1->id,
                'person_id' => $person->id,
                'character' => $c['character'],
                'order'     => $c['order'],
            ]);
        }

        // -- Crew --
        $crewData = [
            ['name' => 'David Fincher', 'profile_url' => 'https://images.tmdb.org/t/p/w185/tpEczFclQZuk81ATaw63meKLRxM.jpg', 'job' => 'Director',   'department' => 'Directing'],
            ['name' => 'Jim Uhls',      'profile_url' => null,                                                                 'job' => 'Screenplay', 'department' => 'Writing'],
            ['name' => 'Art Linson',    'profile_url' => null,                                                                 'job' => 'Producer',   'department' => 'Production'],
            ['name' => 'Ceán Chaffin',  'profile_url' => null,                                                                 'job' => 'Producer',   'department' => 'Production'],
        ];

        foreach ($crewData as $c) {
            $person = Person::create(['name' => $c['name'], 'profile_url' => $c['profile_url']]);
            DB::table('movie_crew')->insert([
                'movie_id'   => $m1->id,
                'person_id'  => $person->id,
                'job'        => $c['job'],
                'department' => $c['department'],
            ]);
        }
    }
}
