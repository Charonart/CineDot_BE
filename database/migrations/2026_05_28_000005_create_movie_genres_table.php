<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_genres', function (Blueprint $table) {
            $table->id('movie_genre_id');
            $table->unsignedBigInteger('movie_id');
            $table->unsignedBigInteger('genre_id');

            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
            $table->foreign('genre_id')->references('genre_id')->on('genres')->onDelete('cascade');

            $table->unique(['movie_id', 'genre_id']);
            $table->index('genre_id', 'idx_movie_genres_genre_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_genres');
    }
};
