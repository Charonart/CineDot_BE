<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id('credit_id');
            $table->foreignId('movie_id')->constrained('movies', 'movie_id')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('persons', 'person_id')->cascadeOnDelete();
            $table->string('credit_type')->nullable();
            $table->string('character_name')->nullable();
            $table->string('department')->nullable();
            $table->string('job')->nullable();
            $table->integer('order')->default(0);
            $table->string('credit_original_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
