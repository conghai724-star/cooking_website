<?php

declare(strict_types=1);

const APPROOT = __DIR__ . '/..';
const URLROOT = '/cooking_website/public';
const SITENAME = 'Website Nấu Ăn';

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

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', env_value('DB_PORT', '3307'));
define('DB_NAME', env_value('DB_NAME', 'cooking_website'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));

define('MAIL_DRIVER', env_value('MAIL_DRIVER', 'smtp'));
define('MAIL_HOST', env_value('MAIL_HOST', ''));
define('MAIL_PORT', (int) env_value('MAIL_PORT', '587'));
define('MAIL_ENCRYPTION', env_value('MAIL_ENCRYPTION', 'tls'));
define('MAIL_USERNAME', env_value('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env_value('MAIL_PASSWORD', ''));
define('MAIL_FROM_EMAIL', env_value('MAIL_FROM_EMAIL', 'no-reply@localhost'));
define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', 'Cooking Website'));