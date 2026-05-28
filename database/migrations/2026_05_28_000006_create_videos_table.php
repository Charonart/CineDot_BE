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
            $table->unsignedBigInteger('movie_id');
            $table->string('name')->nullable();
            $table->string('key_value')->nullable();
            $table->string('site')->nullable();
            $table->integer('size')->nullable();
            $table->string('type')->nullable();
            $table->boolean('official')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('iso_639_1', 10)->nullable();
            $table->string('iso_3166_1', 10)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
