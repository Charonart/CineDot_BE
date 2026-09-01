<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id('room_id');
            $table->foreignId('cinema_id')->constrained('cinemas', 'cinema_id')->cascadeOnDelete();
            $table->string('room_name');
            $table->string('room_type')->nullable(); // e.g., 'IMAX Laser', 'Dolby Cinema', 'ScreenX'
            $table->string('screen_type', 50)->default('standard_2d'); // standard_2d, standard_3d, imax_laser, screenx, dolby_cinema, onyx_led
            $table->string('sound_technology', 50)->default('surround_71'); // surround_71, dolby_atmos, imax_sound
            $table->json('screen_config')->nullable(); // Canvas screen object: shape, aspect_ratio, width, curve_depth, side_walls
            $table->json('features')->nullable(); // Tags: laser_projection, dolby_atmos, recliner, etc.
            $table->decimal('surcharge_amount', 12, 2)->default(0.00); // Phụ thu theo định dạng phòng (vd: IMAX +60k, 3D +30k)
            $table->integer('total_seats')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('screen_type', 'idx_rooms_screen_type');
            $table->index('sound_technology', 'idx_rooms_sound_tech');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
