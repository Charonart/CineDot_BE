<?php

namespace App\Services\AiSchedule\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiDriver implements AiDriverInterface
{
    protected string $apiKey;
    protected string $model;
    protected ?string $baseUrl;
    protected float $temperature;
    protected int $timeoutSeconds;

    public function __construct(string $apiKey, string $model = 'gpt-4o-mini', ?string $baseUrl = null, float $temperature = 0.2, int $timeoutSeconds = 60)
    {
        $this->apiKey = $apiKey;
        $this->model = $model ?: 'gpt-4o-mini';
        $this->baseUrl = rtrim($baseUrl ?: 'https://api.openai.com/v1', '/');
        $this->temperature = $temperature;
        $this->timeoutSeconds = max(10, $timeoutSeconds);
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        $url = "{$this->baseUrl}/chat/completions";

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post($url, [
                    'model'       => $this->model,
                    'messages'    => [
                        ['role' => 'user', 'content' => 'Chào bạn, vui lòng trả lời "OK" để xác nhận kết nối.']
                    ],
                    'max_tokens'  => 10,
                    'temperature' => 0.1,
                ]);

            $latency = (int) round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                return [
                    'success'    => true,
                    'message'    => "Kết nối thành công tới OpenAI / Compatible Provider ({$this->model})! Độ trễ: {$latency}ms.",
                    'latency_ms' => $latency,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body() ?? 'Không thể kết nối tới AI Provider.';
            return [
                'success'    => false,
                'message'    => "Lỗi từ Provider API: {$errorMessage}",
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            $latency = (int) round((microtime(true) - $start) * 1000);
            return [
                'success'    => false,
                'message'    => "Lỗi kết nối Provider: " . $e->getMessage(),
                'latency_ms' => $latency,
            ];
        }
    }

    public function generateStructuredSchedule(string $systemPrompt, string $userPrompt, array $schemaStructure = []): array
    {
        $url = "{$this->baseUrl}/chat/completions";

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout($this->timeoutSeconds)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post($url, [
                    'model'           => $this->model,
                    'messages'        => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature'     => $this->temperature,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                $err = $response->json()['error']['message'] ?? $response->body();
                throw new \Exception("AI API Error: " . $err);
            }

            $result = $response->json();
            $rawText = $result['choices'][0]['message']['content'] ?? '{}';

            $cleaned = trim($rawText);
            if (str_starts_with($cleaned, '```json')) {
                $cleaned = substr($cleaned, 7);
            }
            if (str_starts_with($cleaned, '```')) {
                $cleaned = substr($cleaned, 3);
            }
            if (str_ends_with($cleaned, '```')) {
                $cleaned = substr($cleaned, 0, -3);
            }
            $cleaned = trim($cleaned);

            $parsed = json_decode($cleaned, true);
            if (!is_array($parsed)) {
                throw new \Exception("AI không trả về đúng định dạng JSON hợp lệ: " . substr($cleaned, 0, 200));
            }

            return $parsed;
        } catch (\Exception $e) {
            Log::error("OpenAiDriver Exception: " . $e->getMessage());
            throw $e;
        }
    }
}
