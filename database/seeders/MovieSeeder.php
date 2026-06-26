<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;
use App\Models\Video;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
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
                'genres' => [99, 10001], // Documentary, Tech
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
                'id' => 120,
                'title' => 'The Lord of the Rings: The Fellowship of the Ring',
                'original_title' => 'The Lord of the Rings: The Fellowship of the Ring',
                'overview' => 'Young hobbit Frodo Baggins, after inheriting a mysterious ring from his uncle Bilbo, must leave his home in order to keep it from falling into the hands of its evil creator.',
                'poster_path' => 'https://images.tmdb.org/t/p/w500/6oom5QYQ2yQTMJIbnvbkBL9cHo6.jpg',
                'backdrop_path' => 'https://images.tmdb.org/t/p/original/dUVbWINfRMGojGZRcO6GF1Z2nV8.jpg',
                'status' => 'now_showing',
                'duration_minutes' => 178,
                'release_date' => '2001-12-18',
                'original_language' => 'en',
                'popularity' => 84.737,
                'genres' => [12, 14, 28],
            ]
        ];

        foreach ($moviesData as $md) {
            $genreIds = $md['genres'];
            unset($md['genres']);
            $md['slug'] = Str::slug($md['title']);
            $md['created_at'] = Carbon::now()->subDays(rand(10, 60));
            $md['updated_at'] = $md['created_at'];
            
            $movie = Movie::create($md);

            // Link genres
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
                'published_at' => $md['created_at'],
            ]);
        }
    }
}
