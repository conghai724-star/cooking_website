<?php

declare(strict_types=1);

class ChatController extends Controller
{
    public function ask(): void
    {
        $startedAt = microtime(true);
        $status = 200;
        $message = '';
        $result = [];
        $failureReason = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $status = 400;
            $result = [
                'success' => false,
                'code' => 'BAD_REQUEST',
                'message' => 'Phương thức không hợp lệ.',
            ];
            $failureReason = 'invalid_method';
            $this->finalizeResponse($result, $status, $startedAt, $message, $failureReason);
        }

        $input = $this->readInput();
        $message = trim((string) ($input['message'] ?? ''));

        if ($message === '') {
            $status = 422;
            $result = [
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => 'Vui lòng nhập câu hỏi.',
            ];
            $failureReason = 'empty_message';
            $this->finalizeResponse($result, $status, $startedAt, $message, $failureReason);
        }

        $context = [
            'is_logged_in' => is_logged_in(),
            'user_id' => (int) (current_user_id() ?? 0),
        ];
        $rawContext = $input['context'] ?? null;
        if (is_array($rawContext)) {
            foreach ($rawContext as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }
                if (is_scalar($value) || $value === null || is_array($value)) {
                    $context[$key] = $value;
                }
            }
        }

        try {
            /** @var ChatService $chatService */
            $chatService = $this->service('ChatService');
            $result = $chatService->handle($message, $context);
        } catch (Throwable $e) {
            $status = 500;
            $result = [
                'success' => false,
                'code' => 'CHAT_RUNTIME_ERROR',
                'message' => 'Chatbot tạm thời đang bảo trì. Vui lòng thử lại sau.',
            ];
            $failureReason = 'runtime_exception';
            $this->finalizeResponse($result, $status, $startedAt, $message, $failureReason);
        }

        $ok = (bool) ($result['success'] ?? false);
        if (!$ok) {
            $status = (string) ($result['code'] ?? '') === 'EMPTY_MESSAGE' ? 422 : 400;
            $failureReason = (string) ($result['code'] ?? 'chat_failed');
        }

        $this->finalizeResponse($result, $status, $startedAt, $message, $failureReason);
    }

    private function readInput(): array
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        $rawBody = file_get_contents('php://input');
        if (!is_string($rawBody) || trim($rawBody) === '') {
            return [];
        }

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function finalizeResponse(array $result, int $status, float $startedAt, string $message, ?string $failureReason): void
    {
        $elapsedMs = round((microtime(true) - $startedAt) * 1000, 2);
        $result['latency_ms'] = $elapsedMs;

        $success = (bool) ($result['success'] ?? false);
        system_log_write(
            'chatbot',
            'chat.ask',
            $success ? 'success' : 'failed',
            $success ? null : ($failureReason ?? 'chat_failed'),
            'chat_intent',
            null,
            [
                'message_preview' => $this->truncate($message, 160),
                'message_length' => function_exists('mb_strlen') ? mb_strlen($message) : strlen($message),
                'http_status' => $status,
                'code' => (string) ($result['code'] ?? ''),
                'intent' => (string) ($result['intent'] ?? ''),
                'source' => (string) ($result['source'] ?? ''),
                'confidence' => isset($result['confidence']) ? (float) $result['confidence'] : null,
                'latency_ms' => $elapsedMs,
            ],
            current_user_id()
        );

        $this->saveChatHistory($message, $result, $status, $elapsedMs);
        $this->jsonResponse($result, $status);
    }

    private function truncate(string $value, int $limit): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value) <= $limit) {
                return $value;
            }
            return mb_substr($value, 0, $limit) . '...';
        }

        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit) . '...';
    }

    private function saveChatHistory(string $message, array $result, int $status, float $elapsedMs): void
    {
        try {
            /** @var ChatHistoryModel $historyModel */
            $historyModel = $this->model('ChatHistoryModel');

            $meta = [
                'suggestions' => (array) ($result['suggestions'] ?? []),
                'matched_signals' => (array) ($result['matched_signals'] ?? []),
                'matched_entities' => (array) ($result['matched_entities'] ?? []),
                'actions_count' => count((array) ($result['actions'] ?? [])),
                'chat_state' => (array) (($_SESSION['chat_context']['chat_state'] ?? [])),
            ];

            $historyModel->create([
                'user_id' => current_user_id(),
                'session_id' => session_id() !== '' ? session_id() : null,
                'user_message' => $this->truncate($message, 2000),
                'bot_message' => $this->truncate((string) ($result['message'] ?? ''), 4000),
                'matched_intent' => (string) ($result['intent'] ?? ''),
                'confidence_score' => isset($result['confidence']) ? (float) $result['confidence'] : null,
                'result_code' => (string) ($result['code'] ?? ''),
                'http_status' => $status,
                'source' => (string) ($result['source'] ?? ''),
                'latency_ms' => $elapsedMs,
                'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable $e) {
            // Keep response flow stable even if chat history logging fails.
        }
    }
}
