<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_combos', function (Blueprint $table) {
            $table->id('booking_combo_id');
            $table->foreignId('booking_id')->constrained('bookings', 'booking_id')->cascadeOnDelete();
            $table->foreignId('combo_id')->constrained('combos', 'combo_id')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price_at_booking', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_combos');
    }
};
