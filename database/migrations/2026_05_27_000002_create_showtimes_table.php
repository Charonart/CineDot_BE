<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng showtimes: Lịch chiếu phim tại từng rạp
     * Hỗ trợ:
     *   - Lọc theo ngày:   ?date=2025-06-01
     *   - Lọc theo rạp:    ?cinema_id=1
     *   - Lọc theo phim:   ?movie_id=3
     */
    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->foreignId('cinema_id')->constrained()->onDelete('cascade');
            $table->date('show_date');                          // ngày chiếu
            $table->time('start_time');                        // giờ bắt đầu, e.g. "14:30"
            $table->time('end_time')->nullable();              // giờ kết thúc (tính từ runtime)
            $table->string('screen')->nullable();              // tên phòng chiếu, e.g. "Phòng 3"
            $table->enum('format', ['2D', '3D', '4DX', 'IMAX'])->default('2D');
            $table->decimal('price', 10, 0)->default(75000);  // giá vé (VNĐ)
            $table->integer('available_seats')->default(100); // ghế còn trống
            $table->timestamps();

            // Index để tối ưu query theo ngày + phim/rạp
            $table->index(['show_date', 'movie_id']);
            $table->index(['show_date', 'cinema_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};
