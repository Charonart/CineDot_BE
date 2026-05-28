<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng movie_cast: diễn viên trong phim (từ movie-credits.json → data.cast)
     * Fields: character, order khớp JSON FE
     */
    public function up(): void
    {
        Schema::create('movie_cast', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->string('character')->nullable();
            $table->integer('order')->default(0); // thứ tự xuất hiện trong credit
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_cast');
    }
};
