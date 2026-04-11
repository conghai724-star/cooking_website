<?php

declare(strict_types=1);

const REQUEST_GUARD_STORAGE_DIR = APPROOT . '/storage/ratelimit';

function request_guard_storage_ready(): bool
{
    if (is_dir(REQUEST_GUARD_STORAGE_DIR)) {
        return true;
    }

    return @mkdir(REQUEST_GUARD_STORAGE_DIR, 0775, true);
}

function request_guard_client_ip(): string
{
    $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (!filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
        $remoteAddr = '0.0.0.0';
    }

    $rawTrusted = getenv('TRUSTED_PROXY_IPS');
    $trusted = [];
    if ($rawTrusted !== false && trim((string) $rawTrusted) !== '') {
        foreach (explode(',', (string) $rawTrusted) as $item) {
            $ip = trim($item);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                $trusted[$ip] = true;
            }
        }
    }

    if (!isset($trusted[$remoteAddr])) {
        return $remoteAddr;
    }

    $xff = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($xff === '') {
        return $remoteAddr;
    }

    foreach (array_map('trim', explode(',', $xff)) as $candidate) {
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    return $remoteAddr;
}

function request_guard_user_agent(): string
{
    return trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

function request_guard_ipv4_in_range(string $ip, string $cidr): bool
{
    [$subnet, $bitsRaw] = explode('/', $cidr, 2);
    $bits = (int) $bitsRaw;
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    if ($ipLong === false || $subnetLong === false || $bits < 0 || $bits > 32) {
        return false;
    }

    if ($bits === 0) {
        return true;
    }

    $mask = -1 << (32 - $bits);
    return (($ipLong & $mask) === ($subnetLong & $mask));
}

function request_guard_is_internal_ip(string $ip): bool
{
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return true;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $privateCidrs = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '169.254.0.0/16',
        ];
        foreach ($privateCidrs as $cidr) {
            if (request_guard_ipv4_in_range($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $normalized = strtolower($ip);
        return str_starts_with($normalized, 'fc')
            || str_starts_with($normalized, 'fd')
            || str_starts_with($normalized, 'fe80:');
    }

    return false;
}

function request_guard_whitelist_ips(): array
{
    $raw = getenv('REQUEST_GUARD_IP_WHITELIST');
    if ($raw === false || trim((string) $raw) === '') {
        return [];
    }

    $ips = [];
    foreach (explode(',', (string) $raw) as $item) {
        $ip = trim($item);
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            $ips[$ip] = true;
        }
    }

    return $ips;
}

function request_guard_is_whitelisted_ip(string $ip): bool
{
    if (request_guard_is_internal_ip($ip)) {
        return true;
    }

    $whitelist = request_guard_whitelist_ips();
    return isset($whitelist[$ip]);
}

function request_guard_is_suspicious_agent(string $ua): bool
{
    if ($ua === '') {
        return true;
    }

    $uaLower = strtolower($ua);
    $deny = [
        'python-requests',
        'scrapy',
        'curl/',
        'wget/',
        'httpclient',
        'go-http-client',
        'aiohttp',
        'node-fetch',
        'axios/',
        'libwww-perl',
        'java/',
        'okhttp',
        'postmanruntime',
    ];

    foreach ($deny as $token) {
        if (str_contains($uaLower, $token)) {
            return true;
        }
    }

    return false;
}

function request_guard_rate_limit(string $scope, int $limit, int $windowSeconds): array
{
    if ($limit <= 0 || $windowSeconds <= 0) {
        return ['allowed' => true, 'remaining' => $limit, 'retry_after' => 0];
    }

    if (!request_guard_storage_ready()) {
        // Fail-open: do not break site if filesystem is temporarily unavailable.
        return ['allowed' => true, 'remaining' => $limit, 'retry_after' => 0];
    }

    $ip = request_guard_client_ip();
    $key = sha1($scope . '|' . $ip);
    $path = REQUEST_GUARD_STORAGE_DIR . '/' . $key . '.json';

    $now = time();
    $windowStart = $now - $windowSeconds;

    $timestamps = [];
    $handle = @fopen($path, 'c+');
    if ($handle === false) {
        return ['allowed' => true, 'remaining' => $limit, 'retry_after' => 0];
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return ['allowed' => true, 'remaining' => $limit, 'retry_after' => 0];
        }

        $raw = stream_get_contents($handle);
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $ts) {
                    $value = (int) $ts;
                    if ($value > $windowStart && $value <= $now) {
                        $timestamps[] = $value;
                    }
                }
            }
        }

        $allowed = count($timestamps) < $limit;
        $retryAfter = 0;
        if ($allowed) {
            $timestamps[] = $now;
        } elseif ($timestamps !== []) {
            $oldest = min($timestamps);
            $retryAfter = max(1, ($oldest + $windowSeconds) - $now);
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($timestamps, JSON_UNESCAPED_UNICODE));
        fflush($handle);

        return [
            'allowed' => $allowed,
            'remaining' => max(0, $limit - count($timestamps)),
            'retry_after' => $retryAfter,
        ];
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function request_guard_blocked_response(int $status, string $message, int $retryAfter = 0): void
{
    http_response_code($status);
    if ($retryAfter > 0) {
        header('Retry-After: ' . $retryAfter);
    }
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

function global_request_guard(string $path, string $method): bool
{
    $path = '/' . ltrim($path, '/');
    $method = strtoupper($method);
    $ip = request_guard_client_ip();

    if (request_guard_is_whitelisted_ip($ip)) {
        return true;
    }

    if (function_exists('is_admin') && is_admin()) {
        return true;
    }

    // Skip admin paths to avoid affecting internal operations.
    if (str_starts_with($path, '/admin')) {
        return true;
    }

    $isApiPath = str_starts_with($path, '/chat') || str_starts_with($path, '/ml/');
    $isPublicDataPath = (bool) preg_match(
        '#^/(recipes|ingredients|tips|posts|quizzes|users/\d+|meal-plans/shared/)#',
        $path
    );

    if ($isApiPath || $isPublicDataPath) {
        $ua = request_guard_user_agent();
        if (request_guard_is_suspicious_agent($ua)) {
            request_guard_blocked_response(403, '403 Forbidden');
        }
    }

    if ($isApiPath) {
        $result = request_guard_rate_limit('api', 45, 60);
        if (!$result['allowed']) {
            request_guard_blocked_response(429, '429 Too Many Requests', (int) $result['retry_after']);
        }
        return true;
    }

    if ($method === 'GET' && $isPublicDataPath) {
        $result = request_guard_rate_limit('public_read', 180, 60);
        if (!$result['allowed']) {
            request_guard_blocked_response(429, '429 Too Many Requests', (int) $result['retry_after']);
        }
    }

    return true;
}
