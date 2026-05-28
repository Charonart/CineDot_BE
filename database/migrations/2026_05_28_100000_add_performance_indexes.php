<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index cho bảng movies
        Schema::table('movies', function (Blueprint $table) {
            // Lọc theo status (now_showing, coming_soon, ended)
            $table->index('status', 'idx_movies_status');
            // Sắp xếp theo popularity
            $table->index('popularity', 'idx_movies_popularity');
            // Tìm kiếm theo title (LIKE)
            $table->index('title', 'idx_movies_title');
        });

        // Index cho bảng reviews – dùng cho withAvg & withCount
        Schema::table('reviews', function (Blueprint $table) {
            // JOIN reviews ON movie_id
            $table->index('movie_id', 'idx_reviews_movie_id');
            // AVG(rating) nhanh hơn khi có composite index
            $table->index(['movie_id', 'rating'], 'idx_reviews_movie_rating');
        });

        // Index cho bảng movie_genres – dùng cho whereHas('genres')
        Schema::table('movie_genres', function (Blueprint $table) {
            $table->index('genre_id', 'idx_movie_genres_genre_id');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex('idx_movies_status');
            $table->dropIndex('idx_movies_popularity');
            $table->dropIndex('idx_movies_title');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_movie_id');
            $table->dropIndex('idx_reviews_movie_rating');
        });

        Schema::table('movie_genres', function (Blueprint $table) {
            $table->dropIndex('idx_movie_genres_genre_id');
        });
    }
};
