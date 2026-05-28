<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cập nhật bảng users để khớp với schema CineDot:
     * - Thêm: username, fullname, avatar, birthday, gender, city, phone, point, last_login
     * - Xóa: name, email_verified_at, remember_token
     * - Đổi timestamps() sang create_at / update_at
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm username sau id
            $table->string('username')->unique()->nullable()->after('id');

            // Thêm fullname (thay thế name)
            $table->string('fullname')->nullable()->after('email');

            // Thêm avatar
            $table->string('avatar')->nullable()->after('fullname');

            // Thêm birthday
            $table->date('birthday')->nullable()->after('avatar');

            // Thêm gender
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('birthday');

            // Thêm city
            $table->string('city')->nullable()->after('gender');

            // Thêm phone
            $table->string('phone', 20)->nullable()->after('city');
            // Thêm last_login
            $table->timestamp('last_login')->nullable()->after('point');

            // Xóa các cột không cần thiết
            $table->dropColumn(['name', 'email_verified_at', 'remember_token']);
        });

        // Đổi tên created_at -> create_at, updated_at -> update_at
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('created_at', 'create_at');
            $table->renameColumn('updated_at', 'update_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('create_at', 'created_at');
            $table->renameColumn('update_at', 'updated_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->rememberToken();

            $table->dropColumn([
                'username', 'fullname', 'avatar', 'birthday',
                'gender', 'city', 'phone', 'point', 'last_login'
            ]);
        });
    }
};
