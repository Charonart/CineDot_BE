<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id('room_id');
            $table->unsignedBigInteger('cinema_id');
            $table->string('room_name');
            $table->string('room_type')->nullable();
            $table->integer('total_seats')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamp('created_at')->nullable();

            $table->foreign('cinema_id')->references('cinema_id')->on('cinemas')->onDelete('cascade');

            $table->index('cinema_id', 'idx_rooms_cinema_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
