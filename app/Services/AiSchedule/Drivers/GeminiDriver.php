<?php

namespace App\Services\AiSchedule\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiDriver implements AiDriverInterface
{
    protected string $apiKey;
    protected string $model;
    protected float $temperature;
    protected int $timeoutSeconds;

    public function __construct(string $apiKey, string $model = 'gemini-2.0-flash', float $temperature = 0.2, int $timeoutSeconds = 60)
    {
        $this->apiKey = $apiKey;
        $this->model = $model ?: 'gemini-2.0-flash';
        $this->temperature = $temperature;
        $this->timeoutSeconds = max(10, $timeoutSeconds);
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(15)
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'Chào bạn, vui lòng trả lời "OK" để xác nhận kết nối.']
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 10,
                        'temperature'     => 0.1,
                    ]
                ]);

            $latency = (int) round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                return [
                    'success'    => true,
                    'message'    => "Kết nối thành công tới Google Gemini ({$this->model})! Độ trễ: {$latency}ms.",
                    'latency_ms' => $latency,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body() ?? 'Không thể kết nối tới Google Gemini.';
            return [
                'success'    => false,
                'message'    => "Lỗi từ Gemini API: {$errorMessage}",
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            $latency = (int) round((microtime(true) - $start) * 1000);
            return [
                'success'    => false,
                'message'    => "Lỗi kết nối Gemini: " . $e->getMessage(),
                'latency_ms' => $latency,
            ];
        }
    }

    public function generateStructuredSchedule(string $systemPrompt, string $userPrompt, array $schemaStructure = []): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $combinedPrompt = $systemPrompt . "\n\n=== YÊU CẦU CỦA ADMIN ===\n" . $userPrompt . "\n\nLƯU Ý QUAN TRỌNG: Chỉ trả về định dạng JSON thuần túy (valid JSON), không kèm giải thích markdown code block hoặc text bên ngoài JSON.";

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout($this->timeoutSeconds)
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $combinedPrompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature'      => $this->temperature,
                        'responseMimeType' => 'application/json',
                    ]
                ]);

            if (!$response->successful()) {
                $err = $response->json()['error']['message'] ?? $response->body();
                throw new \Exception("Gemini API Error: " . $err);
            }

            $result = $response->json();
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

            // Clean markdown wrap if any
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
                throw new \Exception("Gemini không trả về đúng định dạng JSON hợp lệ: " . substr($cleaned, 0, 200));
            }

            return $parsed;
        } catch (\Exception $e) {
            Log::error("GeminiDriver Exception: " . $e->getMessage());
            throw $e;
        }
    }
}
