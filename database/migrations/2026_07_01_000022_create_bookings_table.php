<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('showtime_id')->constrained('showtimes', 'showtime_id')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers', 'voucher_id')->nullOnDelete();
            $table->json('price_breakdown')->nullable();
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->enum('booking_status', ['pending', 'paid', 'confirmed', 'cancelled', 'cancelling', 'refunded', 'completed'])->default('pending');
            $table->string('booking_code')->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
