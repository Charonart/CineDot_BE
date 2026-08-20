<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_types', function (Blueprint $table) {
            $table->string('seat_type')->primary(); // e.g. 'standard', 'vip', 'couple', 'sweetbox', 'deluxe', 'bed'
            $table->string('type_name'); // e.g. 'Ghế Tiêu Chuẩn', 'Ghế VIP', 'Ghế Đôi Sweetbox'
            $table->decimal('surcharge_amount', 12, 2)->default(0);
            $table->string('color_code', 20)->default('#64748B'); // e.g. '#7C6FE8', '#EC4899'
            $table->string('icon_name', 30)->default('seat'); // e.g. 'seat', 'star', 'heart', 'crown', 'bed'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_types');
    }
};
