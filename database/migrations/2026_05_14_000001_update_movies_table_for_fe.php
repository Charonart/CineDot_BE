<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cập nhật bảng movies để khớp với dữ liệu JSON từ phía FE.
     * - Đổi tên: description -> overview, duration -> runtime
     * - Thêm mới: backdrop_url, rating, vote_count
     */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // Đổi tên description -> overview
            $table->renameColumn('description', 'overview');

            // Đổi tên duration -> runtime
            $table->renameColumn('duration', 'runtime');

            // Thêm các cột mới từ FE
            $table->string('backdrop_url')->nullable()->after('poster_url');
            // $table->decimal('rating', 3, 1)->default(0)->after('release_date');
            // $table->integer('vote_count')->default(0)->after('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->renameColumn('overview', 'description');
            $table->renameColumn('runtime', 'duration');
            $table->dropColumn(['backdrop_url', 'rating', 'vote_count']);
        });
    }
};
