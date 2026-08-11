<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $movieId = DB::table('movies')->value('movie_id');
        if (!$movieId) {
            return;
        }

        $reviews = [
            [
                'review_id' => 1,
                'user_id' => 3,
                'movie_id' => $movieId,
                'rating' => 5,
                'comment' => 'Phim siêu hay, kỹ xảo đỉnh cao và rất hài hước!',
                'status' => 'approved',
                'is_spoiler' => false,
                'created_at' => now(),
            ],
        ];

        DB::table('reviews')->insertOrIgnore($reviews);
    }
}
