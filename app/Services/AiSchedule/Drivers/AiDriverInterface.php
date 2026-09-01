<?php

namespace App\Services\AiSchedule\Drivers;

interface AiDriverInterface
{
    /**
     * Kiểm tra kết nối tới nhà cung cấp AI
     * @return array ['success' => bool, 'message' => string, 'latency_ms' => int]
     */
    public function testConnection(): array;

    /**
     * Gửi prompt kèm context hệ thống và nhận structured JSON kết quả
     * @param string $systemPrompt
     * @param string $userPrompt
     * @param array $schemaStructure
     * @return array
     */
    public function generateStructuredSchedule(string $systemPrompt, string $userPrompt, array $schemaStructure = []): array;
}
