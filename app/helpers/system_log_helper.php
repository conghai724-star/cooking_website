<?php

declare(strict_types=1);

function system_log_client_ip(): string
{
    $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($forwarded !== '') {
        $parts = explode(',', $forwarded);
        $candidate = trim((string) ($parts[0] ?? ''));
        if ($candidate !== '') {
            return $candidate;
        }
    }
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function system_log_user_agent(): string
{
    $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ua === '') {
        return '';
    }
    return function_exists('mb_substr') ? mb_substr($ua, 0, 255) : substr($ua, 0, 255);
}

function system_log_write(
    string $eventType,
    string $actionKey,
    string $result = 'success',
    ?string $reason = null,
    ?string $targetType = null,
    ?int $targetId = null,
    ?array $meta = null,
    ?int $actorId = null,
    ?string $actorRole = null
): void {
    try {
        if (!class_exists('SystemLogModel', false)) {
            require_once APPROOT . '/app/models/SystemLogModel.php';
        }

        if ($actorId === null || $actorRole === null) {
            if (function_exists('is_admin') && is_admin()) {
                $admin = function_exists('current_admin') ? current_admin() : null;
                $actorId = $actorId ?? (int) ($admin['id'] ?? 0);
                $actorRole = $actorRole ?? ((string) ($admin['role'] ?? 'admin'));
            } elseif (function_exists('is_logged_in') && is_logged_in()) {
                $user = function_exists('current_user') ? current_user() : null;
                $actorId = $actorId ?? (int) ($user['id'] ?? 0);
                $actorRole = $actorRole ?? ((string) ($user['role'] ?? 'user'));
            }
        }

        $payload = [
            'event_type' => $eventType,
            'action_key' => $actionKey,
            'actor_id' => ($actorId !== null && $actorId > 0) ? $actorId : null,
            'actor_role' => $actorRole !== null && $actorRole !== '' ? $actorRole : null,
            'target_type' => $targetType,
            'target_id' => $targetId !== null && $targetId > 0 ? $targetId : null,
            'result' => in_array($result, ['success', 'failed', 'blocked'], true) ? $result : 'success',
            'reason' => $reason,
            'meta_json' => $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => system_log_client_ip(),
            'user_agent' => system_log_user_agent(),
        ];

        $model = new SystemLogModel();
        $model->create($payload);
    } catch (Throwable $e) {
        // Avoid breaking user flow if logging fails.
    }
}
