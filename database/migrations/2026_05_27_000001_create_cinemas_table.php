<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng cinemas: Cụm rạp chiếu phim (CGV, Lotte, Galaxy, BHD…)
     * Hỗ trợ Epic: "Xem Lịch chiếu theo Ngày và Cụm rạp"
     */
    public function up(): void
    {
        Schema::create('cinemas', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // e.g. "CGV Vincom Center"
            $table->string('chain')->nullable();           // e.g. "CGV", "Lotte", "Galaxy", "BHD"
            $table->string('city');                        // e.g. "Hà Nội", "TP.HCM"
            $table->string('address')->nullable();
            $table->string('slug')->unique();              // e.g. "cgv-vincom-center"
            $table->integer('screen_count')->default(1);  // số phòng chiếu
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cinemas');
    }
};
