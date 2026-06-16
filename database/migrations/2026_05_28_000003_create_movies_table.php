<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->index();
            $table->string('original_title')->nullable();
            $table->text('overview')->nullable();
            $table->date('release_date')->nullable();
            $table->string('original_language', 10)->nullable();
            $table->boolean('adult')->default(false);
            $table->boolean('video')->default(false);
            $table->decimal('popularity', 10, 3)->default(0);
            $table->string('backdrop_path')->nullable();
            $table->string('poster_path')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('status')->default('now_showing');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
