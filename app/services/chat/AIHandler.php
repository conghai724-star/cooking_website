<?php

declare(strict_types=1);

final class AIHandler
{
    public function handle(string $message, array $context = []): array
    {
        $apiKey = env_value('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            return [
                'success' => false,
                'code' => 'AI_DISABLED',
                'message' => 'AI handler chưa được cấu hình.',
            ];
        }

        // Placeholder để mở rộng gọi API sau này.
        return [
            'success' => true,
            'code' => 'AI_PLACEHOLDER',
            'message' => 'AI handler đã được kích hoạt nhưng chưa tích hợp gọi model.',
            'source' => 'ai_handler',
        ];
    }
}


