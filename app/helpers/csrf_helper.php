<?php

declare(strict_types=1);

const CSRF_COOKIE_NAME = 'cooking_csrf';
const CSRF_FIELD_NAME = '_csrf';

function csrf_cookie_path(): string
{
    if (function_exists('base_path_for_cookie')) {
        $basePath = base_path_for_cookie();
        if ($basePath !== '') {
            return $basePath;
        }
    }

    return '/';
}

function csrf_cookie_secure(): bool
{
    $https = (string) ($_SERVER['HTTPS'] ?? '');
    if ($https !== '' && strtolower($https) !== 'off') {
        return true;
    }

    return (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;
}

function csrf_is_valid_token(?string $token): bool
{
    if ($token === null) {
        return false;
    }

    return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
}

function csrf_issue_cookie(string $token): void
{
    setcookie(CSRF_COOKIE_NAME, $token, [
        'expires' => 0,
        'path' => csrf_cookie_path(),
        'domain' => '',
        'secure' => csrf_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_COOKIE[CSRF_COOKIE_NAME] = $token;
}

function csrf_token(): string
{
    $token = isset($_COOKIE[CSRF_COOKIE_NAME]) ? (string) $_COOKIE[CSRF_COOKIE_NAME] : '';
    if (!csrf_is_valid_token($token)) {
        $token = bin2hex(random_bytes(32));
        csrf_issue_cookie($token);
    }

    return $token;
}

function csrf_field(): string
{
    $value = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="' . CSRF_FIELD_NAME . '" value="' . $value . '">';
}

function csrf_request_token(): string
{
    $fromPost = isset($_POST[CSRF_FIELD_NAME]) ? (string) $_POST[CSRF_FIELD_NAME] : '';
    if ($fromPost !== '') {
        return $fromPost;
    }

    $header = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return $header;
}

function csrf_verify_request(): bool
{
    $cookieToken = isset($_COOKIE[CSRF_COOKIE_NAME]) ? (string) $_COOKIE[CSRF_COOKIE_NAME] : '';
    $requestToken = csrf_request_token();

    if (!csrf_is_valid_token($cookieToken) || !csrf_is_valid_token($requestToken)) {
        return false;
    }

    return hash_equals($cookieToken, $requestToken);
}

function csrf_reject(): void
{
    http_response_code(419);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '419 CSRF token mismatch';
    exit;
}
