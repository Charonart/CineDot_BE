<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Movie;
use App\Models\Genre;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tạo một số thể loại
        $action = Genre::create(['name' => 'Hành động', 'slug' => 'hanh-dong']);
        $comedy = Genre::create(['name' => 'Hài hước', 'slug' => 'hai-huoc']);
        $drama = Genre::create(['name' => 'Tâm lý', 'slug' => 'tam-ly']);
        $scifi = Genre::create(['name' => 'Viễn tưởng', 'slug' => 'vien-tuong']);

        // Tạo một số bộ phim
        $movie1 = Movie::create([
            'title' => 'Avengers: Endgame',
            'description' => 'Biệt đội siêu anh hùng tập hợp lại để đảo ngược thiệt hại do Thanos gây ra.',
            'poster_url' => 'https://example.com/avengers.jpg',
            'trailer_url' => 'https://youtube.com/watch?v=TcMBFSGVi1c',
            'status' => 'ended',
            'duration' => 181,
            'release_date' => '2019-04-26',
        ]);

        $movie2 = Movie::create([
            'title' => 'Dune: Part Two',
            'description' => 'Paul Atreides hợp nhất với Chani và người Fremen trên hành trình báo thù.',
            'poster_url' => 'https://example.com/dune2.jpg',
            'trailer_url' => 'https://youtube.com/watch?v=Way9Dexny3w',
            'status' => 'now_showing',
            'duration' => 166,
            'release_date' => '2024-03-01',
        ]);

        $movie3 = Movie::create([
            'title' => 'Deadpool & Wolverine',
            'description' => 'Deadpool thay đổi lịch sử MCU với sự giúp đỡ của Wolverine.',
            'poster_url' => 'https://example.com/deadpool3.jpg',
            'trailer_url' => 'https://youtube.com/watch?v=73_1biulkYk',
            'status' => 'coming_soon',
            'duration' => 120,
            'release_date' => '2024-07-26',
        ]);

        // Gắn thể loại cho phim
        $movie1->genres()->attach([$action->id, $scifi->id]);
        $movie2->genres()->attach([$action->id, $scifi->id, $drama->id]);
        $movie3->genres()->attach([$action->id, $comedy->id]);
    }
}
