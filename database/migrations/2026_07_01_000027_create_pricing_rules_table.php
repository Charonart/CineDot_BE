<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id('pricing_rule_id');
            $table->string('name');
            $table->string('rule_category')->nullable();
            $table->json('conditions')->nullable();
            $table->enum('modifier_type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->decimal('modifier_value', 12, 2)->default(0);
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
