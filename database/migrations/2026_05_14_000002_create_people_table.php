<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng people: lưu thông tin diễn viên & đoàn phim
     * Dùng chung cho cả cast lẫn crew (giống TMDB)
     */
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('gender')->nullable()->comment('1: Female, 2: Male, 0: Unknown');
            $table->string('profile_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
