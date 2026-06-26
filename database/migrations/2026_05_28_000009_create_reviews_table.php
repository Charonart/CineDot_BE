<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id('review_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('movie_id');
            $table->decimal('rating', 3, 1);
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');

            $table->unique(['user_id', 'movie_id']);
            
            $table->index('movie_id', 'idx_reviews_movie_id');
            $table->index(['movie_id', 'rating'], 'idx_reviews_movie_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
