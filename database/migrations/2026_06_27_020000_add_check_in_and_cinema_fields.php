<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('cinema_id')->nullable();
            $table->foreign('cinema_id')->references('cinema_id')->on('cinemas')->onDelete('set null');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->foreign('checked_in_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['checked_in_by']);
            $table->dropColumn(['checked_in_at', 'checked_in_by']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cinema_id']);
            $table->dropColumn(['cinema_id']);
        });
    }
};
