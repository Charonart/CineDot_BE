<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;
use App\Models\Genre;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed dữ liệu mẫu khớp với JSON từ phía FE (git/movie-detail.json, movies-popular.json, ...)
     */
    public function run(): void
    {
        // ---- Genres ----
        $documentary = Genre::firstOrCreate(['slug' => 'documentary'], ['name' => 'Documentary']);
        $tech        = Genre::firstOrCreate(['slug' => 'tech'],        ['name' => 'Tech']);
        $action      = Genre::firstOrCreate(['slug' => 'action'],      ['name' => 'Action']);
        $drama       = Genre::firstOrCreate(['slug' => 'drama'],       ['name' => 'Drama']);
        $scifi       = Genre::firstOrCreate(['slug' => 'sci-fi'],      ['name' => 'Sci-Fi']);

        // ---- Movies ----
        // ID 1 – khớp với movie-detail.json & movies-trending.json & movies-search.json
        $movie1 = Movie::create([
            'title'        => 'CineDot: The Beginning',
            'overview'     => 'A senior architect\'s journey to build the perfect movie app. This documentary explores the challenges of clean architecture and the beauty of modular design.',
            'poster_url'   => 'https://images.tmdb.org/t/p/w500/poster1.jpg',
            'backdrop_url' => 'https://images.tmdb.org/t/p/original/backdrop1.jpg',
            'trailer_url'  => null,
            'status'       => 'now_showing',
            'runtime'      => 120,
            'release_date' => '2024-05-14',
            'rating'       => 9.5,
            'vote_count'   => 1200,
        ]);

        // ID 2 – khớp với movies-popular.json
        $movie2 = Movie::create([
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

        // Thêm vài phim nữa để test pagination
        $movie3 = Movie::create([
            'title'        => 'Deadpool & Wolverine',
            'overview'     => 'Deadpool thay đổi lịch sử MCU với sự giúp đỡ của Wolverine.',
            'poster_url'   => 'https://images.tmdb.org/t/p/w500/poster3.jpg',
            'backdrop_url' => 'https://images.tmdb.org/t/p/original/backdrop3.jpg',
            'trailer_url'  => 'https://youtube.com/watch?v=73_1biulkYk',
            'status'       => 'coming_soon',
            'runtime'      => 127,
            'release_date' => '2024-07-26',
            'rating'       => 8.2,
            'vote_count'   => 600,
        ]);

        // ---- Attach genres ----
        $movie1->genres()->attach([$documentary->id, $tech->id]);
        $movie2->genres()->attach([$action->id]);
        $movie3->genres()->attach([$action->id, $drama->id]);
    }
}
