<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id('person_id');
            $table->unsignedBigInteger('tmdb_person_id')->nullable()->unique();
            $table->string('name');
            $table->string('original_name')->nullable();
            $table->integer('gender')->nullable();
            $table->string('profile_path')->nullable();
            $table->boolean('adult')->default(false);
            $table->decimal('popularity', 10, 3)->default(0);
            $table->string('known_for_department')->nullable();
            $table->text('biography')->nullable();
            $table->date('birthday')->nullable();
            $table->date('deathday')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('imdb_id')->nullable();
            $table->string('homepage')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
