<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

const APPROOT = __DIR__ . '/..';
const URLROOT = '/cooking_website/public';
const SITENAME = 'Website Nấu Ăn';

if (!function_exists('load_env_file')) {
    function load_env_file(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if ($key === '') {
                continue;
            }

            if (
                strlen($value) >= 2 &&
                (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }
            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $value;
            }
        }
    }
}

if (!function_exists('env_value')) {
    function env_value(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        $value = trim((string) $value);
        return $value !== '' ? $value : $default;
    }
}

load_env_file(APPROOT . '/.env');

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', env_value('DB_PORT', '3307'));
define('DB_NAME', env_value('DB_NAME', 'cooking_website'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));
define('DB_CHARSET', env_value('DB_CHARSET', 'utf8mb4'));
define('DB_COLLATION', env_value('DB_COLLATION', 'utf8mb4_unicode_ci'));

define('ROBOFLOW_API_KEY', env_value('ROBOFLOW_API_KEY', ''));
define('ROBOFLOW_MODEL', env_value('ROBOFLOW_MODEL', ''));
define('ROBOFLOW_BASE_URL', env_value('ROBOFLOW_BASE_URL', 'https://detect.roboflow.com'));
define('ROBOFLOW_CONFIDENCE', (int) env_value('ROBOFLOW_CONFIDENCE', '35'));
define('ROBOFLOW_DEBUG', env_value('ROBOFLOW_DEBUG', '0') === '1');
define('ROBOFLOW_FALLBACK_PYTHON', env_value('ROBOFLOW_FALLBACK_PYTHON', '0') === '1');

define('MAIL_DRIVER', env_value('MAIL_DRIVER', 'smtp'));
define('MAIL_HOST', env_value('MAIL_HOST', ''));
define('MAIL_PORT', (int) env_value('MAIL_PORT', '587'));
define('MAIL_ENCRYPTION', env_value('MAIL_ENCRYPTION', 'tls'));
define('MAIL_USERNAME', env_value('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env_value('MAIL_PASSWORD', ''));
define('MAIL_FROM_EMAIL', env_value('MAIL_FROM_EMAIL', 'no-reply@localhost'));
define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', 'Cooking Website'));
