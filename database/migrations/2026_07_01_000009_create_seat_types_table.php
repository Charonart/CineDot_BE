<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_types', function (Blueprint $table) {
            $table->string('seat_type')->primary();
            $table->decimal('surcharge_amount', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_types');
    }
};
