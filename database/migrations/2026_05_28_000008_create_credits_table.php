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
            $table->unsignedBigInteger('movie_id');
            $table->unsignedBigInteger('person_id');
            $table->enum('credit_type', ['cast', 'crew']);
            $table->string('character_name')->nullable();
            $table->string('department')->nullable();
            $table->string('job')->nullable();
            $table->integer('order')->nullable();
            $table->string('credit_original_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
            $table->foreign('person_id')->references('person_id')->on('persons')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
