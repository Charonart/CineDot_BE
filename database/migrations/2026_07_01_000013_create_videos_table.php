<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id('video_id');
            $table->foreignId('movie_id')->constrained('movies', 'movie_id')->cascadeOnDelete();
            $table->string('name');
            $table->string('key_value');
            $table->string('site')->default('YouTube');
            $table->integer('size')->nullable();
            $table->string('type')->nullable();
            $table->boolean('official')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->string('iso_639_1', 10)->nullable();
            $table->string('iso_3166_1', 10)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
