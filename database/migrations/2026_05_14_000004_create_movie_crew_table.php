<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng movie_crew: đoàn làm phim (từ movie-credits.json → data.crew)
     * Fields: job, department khớp JSON FE
     */
    public function up(): void
    {
        Schema::create('movie_crew', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->string('job')->nullable();           // e.g. "Director", "Screenplay"
            $table->string('department')->nullable();    // e.g. "Directing", "Writing"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_crew');
    }
};
