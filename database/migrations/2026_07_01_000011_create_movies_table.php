<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id('movie_id');
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('original_title')->nullable();
            $table->text('overview')->nullable();
            $table->date('release_date')->nullable();
            $table->string('original_language', 10)->nullable();
            $table->double('popularity')->default(0);
            $table->decimal('vote_average', 3, 1)->default(0.0);
            $table->unsignedInteger('vote_count')->default(0);
            $table->string('imdb_id', 20)->nullable();
            $table->unsignedBigInteger('tmdb_id')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->string('poster_path')->nullable();
            $table->integer('duration')->nullable();
            $table->string('age_rating', 10)->default('P');
            $table->enum('status', ['upcoming', 'now_showing', 'ended'])->default('upcoming');
            $table->timestamps();

            // Performance Indexes
            $table->index('popularity');
            $table->index('vote_average');
            $table->index('imdb_id');
            $table->index('age_rating');
            $table->index('status');
            $table->index(['status', 'popularity']);
            $table->index('release_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
