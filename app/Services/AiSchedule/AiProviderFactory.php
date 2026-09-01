<?php

namespace App\Services\AiSchedule;

use App\Models\AiScheduleConfig;
use App\Services\AiSchedule\Drivers\AiDriverInterface;
use App\Services\AiSchedule\Drivers\OpenAiDriver;

class AiProviderFactory
{
    /**
     * Tạo driver từ cấu hình hoặc tham số tùy chỉnh
     */
    public static function createFromConfig(AiScheduleConfig $config, array $override = []): AiDriverInterface
    {
        $provider = $override['ai_provider'] ?? $config->ai_provider ?? 'custom';
        $baseUrl = $override['ai_base_url'] ?? $config->ai_base_url ?? 'https://api.openai.com/v1';
        $model = $override['ai_model_name'] ?? $config->ai_model_name ?? 'gpt-4o-mini';
        $apiKey = $override['ai_api_key'] ?? $config->ai_api_key ?? null;
        $temperature = (float) ($override['ai_temperature'] ?? $config->ai_temperature ?? 0.2);
        $timeout = (int) ($override['ai_timeout_seconds'] ?? $config->ai_timeout_seconds ?? 60);

        return self::create($provider, $apiKey, $model, $baseUrl, $temperature, $timeout);
    }

    /**
     * Khởi tạo driver trực tiếp qua Custom URL Endpoint
     */
    public static function create(
        string $provider = 'custom',
        ?string $apiKey = null,
        ?string $model = null,
        ?string $baseUrl = null,
        float $temperature = 0.2,
        int $timeoutSeconds = 60
    ): AiDriverInterface {
        $effectiveBaseUrl = $baseUrl ?: env('AI_BASE_URL') ?: 'https://api.openai.com/v1';
        $effectiveModel = $model ?: env('AI_MODEL_NAME') ?: 'gpt-4o-mini';
        $key = $apiKey ?: env('AI_API_KEY') ?: env('OPENAI_API_KEY') ?: env('GEMINI_API_KEY') ?: env('DEEPSEEK_API_KEY');

        if (empty($key) || str_contains($key, '••••')) {
            $configWithKey = AiScheduleConfig::whereNotNull('ai_api_key')->where('ai_api_key', '!=', '')->first();
            if ($configWithKey && !empty($configWithKey->ai_api_key)) {
                $key = $configWithKey->ai_api_key;
                $effectiveBaseUrl = $baseUrl ?: $configWithKey->ai_base_url ?: $effectiveBaseUrl;
                $effectiveModel = $model ?: $configWithKey->ai_model_name ?: $effectiveModel;
            }
        }

        if (empty($key) || str_contains($key, '••••')) {
            throw new \InvalidArgumentException('Chưa cấu hình API Key cho AI Endpoint. Vui lòng nhập API Key trong tab Cài đặt.');
        }

        return new OpenAiDriver($key, $effectiveModel, $effectiveBaseUrl, $temperature, $timeoutSeconds);
    }
}
