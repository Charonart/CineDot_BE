<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiScheduleConfig extends Model
{
    use HasFactory;

    protected $table = 'ai_schedule_configs';

    protected $fillable = [
        'cinema_id',
        'opening_time',
        'closing_time',
        'default_buffer_minutes',
        'staggering_gap_minutes',
        'sync_prime_time_from_pricing_rules',
        'custom_prime_time_start',
        'custom_prime_time_end',
        'default_base_price',
        'ai_provider',
        'ai_model_name',
        'ai_api_key',
        'ai_base_url',
        'ai_temperature',
        'ai_timeout_seconds',
        'custom_rules',
    ];

    protected $casts = [
        'sync_prime_time_from_pricing_rules' => 'boolean',
        'default_buffer_minutes'             => 'integer',
        'staggering_gap_minutes'             => 'integer',
        'ai_timeout_seconds'                 => 'integer',
        'default_base_price'                 => 'decimal:2',
        'ai_temperature'                     => 'decimal:2',
        'ai_api_key'                         => 'encrypted',
        'custom_rules'                       => 'array',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    /**
     * Lấy cấu hình áp dụng cho một rạp cụ thể (hoặc fallback về cấu hình mặc định)
     */
    public static function getEffectiveConfig(?int $cinemaId = null): self
    {
        if ($cinemaId) {
            $config = self::where('cinema_id', $cinemaId)->first();
            if ($config && !empty($config->ai_api_key)) {
                return $config;
            }
            if ($config) {
                // If this cinema config exists but has no key, check global or other cinema key
                $fallback = self::whereNotNull('ai_api_key')->where('ai_api_key', '!=', '')->first();
                if ($fallback) {
                    $config->ai_api_key = $fallback->ai_api_key;
                    $config->ai_base_url = $config->ai_base_url ?: $fallback->ai_base_url;
                    $config->ai_model_name = $config->ai_model_name ?: $fallback->ai_model_name;
                }
                return $config;
            }
        }

        $globalConfig = self::whereNull('cinema_id')->first();
        if ($globalConfig && !empty($globalConfig->ai_api_key)) {
            return $globalConfig;
        }

        // Fallback lấy bất kỳ cấu hình rạp nào đã có API Key
        $anyConfigWithKey = self::whereNotNull('ai_api_key')->where('ai_api_key', '!=', '')->first();
        if ($anyConfigWithKey) {
            return $anyConfigWithKey;
        }

        if ($globalConfig) {
            return $globalConfig;
        }

        // Nếu chưa có trong DB, trả về instance mặc định
        return new self([
            'cinema_id'                          => $cinemaId,
            'opening_time'                       => '08:30',
            'closing_time'                       => '23:30',
            'default_buffer_minutes'             => 15,
            'staggering_gap_minutes'             => 15,
            'sync_prime_time_from_pricing_rules' => true,
            'custom_prime_time_start'            => '18:00',
            'custom_prime_time_end'              => '22:30',
            'default_base_price'                 => 100000.00,
            'ai_provider'                        => 'custom',
            'ai_base_url'                        => 'https://api.openai.com/v1',
            'ai_model_name'                      => 'gpt-4o-mini',
            'ai_temperature'                     => 0.20,
            'ai_timeout_seconds'                 => 60,
        ]);
    }
}
