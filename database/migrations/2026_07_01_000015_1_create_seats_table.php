<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id('seat_id');
            $table->foreignId('room_id')->constrained('rooms', 'room_id')->cascadeOnDelete();
            $table->string('seat_type');
            $table->foreign('seat_type')->references('seat_type')->on('seat_types')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('row_name', 10);
            $table->string('seat_number', 10);
            $table->integer('row_index')->default(0);
            $table->integer('col_index')->default(0);
            $table->integer('coord_x')->default(0);
            $table->integer('coord_y')->default(0);
            $table->smallInteger('angle')->default(0);
            $table->unsignedBigInteger('couple_partner_id')->nullable();
            $table->foreign('couple_partner_id')->references('seat_id')->on('seats')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Performance Indexes
            $table->unique(['room_id', 'row_name', 'seat_number', 'deleted_at'], 'uq_room_row_seat_del');
            $table->index(['room_id', 'is_active']);
            $table->index('seat_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
