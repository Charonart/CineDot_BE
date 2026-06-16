<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id('voucher_id');
            $table->string('code')->unique();
            $table->enum('discount_type', ['fixed', 'percent']);
            $table->integer('discount_value');
            $table->integer('min_order_value')->default(0);
            $table->integer('max_discount_value')->nullable(); // cho percent
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
