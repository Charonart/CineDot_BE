<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_schedule_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cinema_id')->nullable()->constrained('cinemas', 'cinema_id')->cascadeOnDelete();
            
            // 1. Cấu hình vận hành rạp
            $table->string('opening_time', 5)->default('08:30');      // HH:mm
            $table->string('closing_time', 5)->default('23:30');      // HH:mm
            $table->integer('default_buffer_minutes')->default(15);   // Thời gian dọn phòng/quảng cáo
            $table->integer('staggering_gap_minutes')->default(15);   // Giãn cách giờ bắt đầu giữa các phòng
            
            // 2. Tích hợp định giá & khung giờ vàng
            $table->boolean('sync_prime_time_from_pricing_rules')->default(true); // Tự động lấy từ PricingRule
            $table->string('custom_prime_time_start', 5)->nullable()->default('18:00');
            $table->string('custom_prime_time_end', 5)->nullable()->default('22:30');
            $table->decimal('default_base_price', 12, 2)->default(100000.00); // Giá vé cơ sở mặc định (VNĐ)
            
            // 3. Cấu hình kết nối AI Custom Endpoint (OpenAI-Compatible)
            $table->string('ai_provider', 50)->default('custom');
            $table->string('ai_base_url', 255)->default('https://api.openai.com/v1'); // Endpoint URL
            $table->string('ai_model_name', 100)->default('gpt-4o-mini');
            $table->text('ai_api_key')->nullable();                   // Mã hóa bảo mật
            $table->decimal('ai_temperature', 3, 2)->default(0.20);
            $table->integer('ai_timeout_seconds')->default(60);       // Timeout tùy chỉnh (giây)
            
            // 4. Quy tắc tùy biến mở rộng
            $table->json('custom_rules')->nullable();
            
            $table->timestamps();
            $table->unique('cinema_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_schedule_configs');
    }
};
