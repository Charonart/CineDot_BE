<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Genre;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
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

        foreach ($genreData as $g) {
            Genre::create([
                'genre_id'   => $g['id'],
                'genre_name' => $g['name'],
            ]);
        }
    }
}
