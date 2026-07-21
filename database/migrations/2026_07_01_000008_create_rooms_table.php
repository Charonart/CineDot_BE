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
            $table->foreignId('cinema_id')->constrained('cinemas', 'cinema_id')->cascadeOnDelete();
            $table->string('room_name');
            $table->string('room_type')->nullable();
            $table->json('seat_matrix')->nullable();
            $table->integer('total_seats')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
