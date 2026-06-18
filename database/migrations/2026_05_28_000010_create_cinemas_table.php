<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cinemas', function (Blueprint $table) {
            $table->id('cinema_id');
            $table->string('cinema_name');
            $table->string('slug')->unique()->index();
            $table->string('cinema_address')->nullable();
            $table->unsignedBigInteger('province_id')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('create_at')->nullable();

            $table->foreign('province_id')->references('province_id')->on('provinces')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cinemas');
    }
};
