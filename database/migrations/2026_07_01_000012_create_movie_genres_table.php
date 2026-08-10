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
            $table->foreignId('movie_id')->constrained('movies', 'movie_id')->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained('genres', 'genre_id')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_genres');
    }
};
