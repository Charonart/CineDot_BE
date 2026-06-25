<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\User;
use App\Models\Movie;
use Carbon\Carbon;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->count() === 0) return;

        // Movie 1 (CineDot): Target 9.5
        $this->seedReviews(1, $users, [
            [10.0, 'Tuyệt tác về phần mềm! Đáng xem từng giây.'],
            [9.0, 'Phim rất truyền cảm hứng cho lập trình viên trẻ.'],
            [10.0, 'Đỉnh cao của clean architecture.'],
            [9.0, 'Excellent documentary. A must watch!'],
        ]);

        // Movie 2 (Tanstack): Target 8.8
        $this->seedReviews(2, $users, [
            [9.0, 'Tôi cực kỳ thích câu chuyện quản lý state trong này.'],
            [8.0, 'Nhịp phim nhanh, lôi cuốn.'],
            [9.0, 'Rất hữu ích để hiểu sâu về server state.'],
            [9.0, 'Fantastic storyline.'],
        ]);

        // Movie 3 (Se7en): Target 8.6
        $this->seedReviews(3, $users, [
            [9.0, 'Cực kỳ ám ảnh và nghẹt thở.'],
            [8.0, 'Diễn xuất của Brad Pitt quá đỉnh.'],
            [9.0, 'Cái kết hay nhất mọi thời đại.'],
            [8.0, 'Bầu không khí u tối xuất sắc.'],
            [9.0, 'One of the best psychological thrillers ever.'],
        ]);
        
        $newMoviesRatings = [
            4 => 9.0,
            120 => 8.0,
        ];

        foreach ($newMoviesRatings as $movieId => $targetRating) {
            $this->seedReviews($movieId, $users, [
                [$targetRating, 'Phim xem rất hay, cực kỳ thích kỹ xảo và âm thanh.'],
                [$targetRating, 'Kịch bản cuốn hút từ đầu đến cuối.'],
                [$targetRating, 'Diễn xuất của các diễn viên vô cùng tuyệt vời.'],
            ]);
        }
    }

    private function seedReviews(int $movieId, $usersList, array $reviewsList): void
    {
        foreach ($reviewsList as $idx => $rev) {
            $user = $usersList[$idx % count($usersList)];
            [$rating, $comment] = $rev;

            Review::create([
                'user_id'    => $user->user_id,
                'movie_id'   => $movieId,
                'rating'     => $rating,
                'comment'    => $comment,
                'created_at' => Carbon::now()->subDays(rand(1, 60)), // Random trong 2 tháng
            ]);
        }
    }
}
