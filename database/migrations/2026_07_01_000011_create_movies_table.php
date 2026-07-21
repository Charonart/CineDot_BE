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
            $table->boolean('adult')->default(false);
            $table->boolean('video')->default(false);
            $table->double('popularity')->default(0);
            $table->string('backdrop_path')->nullable();
            $table->string('poster_path')->nullable();
            $table->integer('duration')->nullable();
            $table->enum('status', ['upcoming', 'now_showing', 'ended'])->default('upcoming');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
