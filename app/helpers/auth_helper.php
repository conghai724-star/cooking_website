<?php

declare(strict_types=1);

const USER_SESSION_NAME = 'cooking_user';
const ADMIN_SESSION_NAME = 'cooking_admin';

function session_cookie_secure(): bool
{
    $https = (string) ($_SERVER['HTTPS'] ?? '');
    if ($https !== '' && strtolower($https) !== 'off') {
        return true;
    }

    return (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;
}

function base_path_for_cookie(): string
{
    if (defined('URLROOT')) {
        $parsed = parse_url(URLROOT);
        if (!empty($parsed['path'])) {
            return rtrim($parsed['path'], '/');
        }
    }
    return '';
}

function start_session_named(string $name): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (session_name() === $name) {
            return;
        }
        session_write_close();
    }

    $params = session_get_cookie_params();
    $basePath = base_path_for_cookie();
    $cookiePath = $basePath !== '' ? $basePath : '/';
    if ($name === ADMIN_SESSION_NAME) {
        $cookiePath = ($cookiePath === '/' ? '' : $cookiePath) . '/admin';
        if ($cookiePath === '') {
            $cookiePath = '/admin';
        }
    }

    session_set_cookie_params([
        'lifetime' => $params['lifetime'],
        'path' => $cookiePath,
        'domain' => $params['domain'],
        'secure' => (bool) ($params['secure'] ?? false) || session_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name($name);
    session_start();
}

function is_admin_request(): bool
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $base = base_path_for_cookie();
    $adminPrefix = ($base !== '' ? $base : '') . '/admin';
    return str_starts_with($uri, $adminPrefix) || str_starts_with($uri, '/admin');
}

$GLOBALS['_ADMIN_SESSION'] = null;
$GLOBALS['_USER_SESSION_STARTED'] = false;
$GLOBALS['_PERMISSION_CACHE'] = [];
$GLOBALS['_USER_SESSION_CHECKED'] = false;
$GLOBALS['_ADMIN_SESSION_CHECKED'] = false;

function user_account_is_active(int $userId): bool
{
    try {
        $db = Database::getInstance();
        $db->query(
            "UPDATE user_bans
             SET is_active = 0
             WHERE user_id = :id
               AND is_active = 1
               AND ban_type = 'temporary'
               AND ban_until IS NOT NULL
               AND ban_until <= NOW()"
        )->bind(':id', $userId)->execute();

        $db->query(
            "UPDATE users
             SET status = 'active', ban_reason = NULL, banned_until = NULL
             WHERE id = :id
               AND status = 'banned'
               AND id NOT IN (SELECT user_id FROM user_bans WHERE is_active = 1)"
        )->bind(':id', $userId)->execute();

        $db->query(
            "SELECT u.id
             FROM users u
             LEFT JOIN user_bans ub ON ub.user_id = u.id AND ub.is_active = 1
             WHERE u.id = :id
               AND u.deleted_at IS NULL
               AND ub.id IS NULL
             LIMIT 1"
        )
            ->bind(':id', $userId)
            ->execute();
        return (bool) $db->single();
    } catch (Throwable $e) {
        // If DB is temporarily unavailable, avoid force-logout to reduce accidental lockout.
        return true;
    }
}

function ensure_user_session(): void
{
    if (!empty($GLOBALS['_USER_SESSION_STARTED'])) {
        return;
    }

    if (is_admin_request() && !isset($_COOKIE[USER_SESSION_NAME])) {
        return;
    }

    start_session_named(USER_SESSION_NAME);
    $GLOBALS['_USER_SESSION_STARTED'] = true;

    if (empty($GLOBALS['_USER_SESSION_CHECKED']) && isset($_SESSION['user']['id'])) {
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if ($userId > 0 && !user_account_is_active($userId)) {
            unset($_SESSION['user']);
        }
        $GLOBALS['_USER_SESSION_CHECKED'] = true;
    }
}

function ensure_admin_session(): void
{
    if ($GLOBALS['_ADMIN_SESSION'] !== null) {
        return;
    }

    $shouldLoad = is_admin_request() || isset($_COOKIE[ADMIN_SESSION_NAME]);
    if (!$shouldLoad) {
        $GLOBALS['_ADMIN_SESSION'] = [];
        return;
    }

    $userStarted = !empty($GLOBALS['_USER_SESSION_STARTED']);
    start_session_named(ADMIN_SESSION_NAME);
    if (empty($GLOBALS['_ADMIN_SESSION_CHECKED']) && isset($_SESSION['admin']['id'])) {
        $adminId = (int) ($_SESSION['admin']['id'] ?? 0);
        if ($adminId > 0 && !user_account_is_active($adminId)) {
            unset($_SESSION['admin']);
        }
        $GLOBALS['_ADMIN_SESSION_CHECKED'] = true;
    }
    $GLOBALS['_ADMIN_SESSION'] = $_SESSION ?? [];
    session_write_close();
    if ($userStarted) {
        start_session_named(USER_SESSION_NAME);
    }
}

function is_logged_in(): bool
{
    ensure_user_session();
    return isset($_SESSION['user']);
}

function current_user(): ?array
{
    ensure_user_session();
    return $_SESSION['user'] ?? null;
}

function current_user_id(): ?int
{
    ensure_user_session();
    return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
}

function is_admin(): bool
{
    ensure_admin_session();
    return isset(($GLOBALS['_ADMIN_SESSION'] ?? [])['admin']);
}

function current_admin(): ?array
{
    ensure_admin_session();
    return ($GLOBALS['_ADMIN_SESSION'] ?? [])['admin'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . URLROOT . '/login');
        exit;
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: ' . URLROOT . '/admin/login');
        exit;
    }
}

function permission_set_for_actor(int $userId, string $role): array
{
    $cacheKey = $userId . ':' . $role;
    if (isset($GLOBALS['_PERMISSION_CACHE'][$cacheKey]) && is_array($GLOBALS['_PERMISSION_CACHE'][$cacheKey])) {
        return $GLOBALS['_PERMISSION_CACHE'][$cacheKey];
    }

    try {
        $db = Database::getInstance();
        $permissions = [];

        // Permissions inherited from role name in users.role
        $db->query(
            'SELECT DISTINCT p.permission_name
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN roles r ON r.id = rp.role_id
             WHERE r.role_name = :role'
        )->bind(':role', $role)->execute();
        foreach ($db->resultSet() as $row) {
            $name = (string) ($row['permission_name'] ?? '');
            if ($name !== '') {
                $permissions[$name] = true;
            }
        }

        // Extra permissions from user_roles mapping (if any)
        $db->query(
            'SELECT DISTINCT p.permission_name
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = :user_id'
        )->bind(':user_id', $userId)->execute();
        foreach ($db->resultSet() as $row) {
            $name = (string) ($row['permission_name'] ?? '');
            if ($name !== '') {
                $permissions[$name] = true;
            }
        }

        $GLOBALS['_PERMISSION_CACHE'][$cacheKey] = $permissions;
        return $permissions;
    } catch (Throwable $e) {
        // Keep old behavior if RBAC tables are not ready yet.
        if ($role === 'super_admin') {
            return ['*' => true];
        }
        return [];
    }
}

function admin_has_permission(string $permission): bool
{
    if (!is_admin()) {
        return false;
    }

    $admin = current_admin();
    $adminId = (int) ($admin['id'] ?? 0);
    $role = (string) ($admin['role'] ?? '');
    if ($adminId <= 0 || $role === '') {
        return false;
    }

    $set = permission_set_for_actor($adminId, $role);

    if (isset($set['*'])) {
        return true;
    }

    return isset($set[$permission]);
}

function require_admin_permission(string $permission): void
{
    require_admin();
    if (admin_has_permission($permission)) {
        return;
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '403 Forbidden';
    exit;
}

function middleware_require_admin_permission(string $permission): callable
{
    return static function () use ($permission): void {
        require_admin_permission($permission);
    };
}

function middleware_require_login(): callable
{
    return static function (): void {
        require_login();
    };
}

function user_has_permission(string $permission): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $user = current_user();
    $userId = (int) ($user['id'] ?? 0);
    $role = (string) ($user['role'] ?? 'user');
    if ($userId <= 0 || $role === '') {
        return false;
    }

    $set = permission_set_for_actor($userId, $role);
    if (isset($set['*'])) {
        return true;
    }

    return isset($set[$permission]);
}

function require_user_permission(string $permission): void
{
    require_login();
    if (user_has_permission($permission)) {
        return;
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '403 Forbidden';
    exit;
}

function middleware_require_user_permission(string $permission): callable
{
    return static function () use ($permission): void {
        require_user_permission($permission);
    };
}

function set_user_session(array $data): void
{
    ensure_user_session();
    $_SESSION['user'] = $data;
}

function clear_user_session(): void
{
    ensure_user_session();
    if (!empty($GLOBALS['_USER_SESSION_STARTED'])) {
        unset($_SESSION['user']);
    }
}

function set_admin_session(array $data): void
{
    start_session_named(ADMIN_SESSION_NAME);
    session_regenerate_id(true);
    $_SESSION['admin'] = $data;
    $GLOBALS['_ADMIN_SESSION'] = ['admin' => $data];
    session_write_close();
    if (!empty($GLOBALS['_USER_SESSION_STARTED'])) {
        start_session_named(USER_SESSION_NAME);
    }
}

function clear_admin_session(): void
{
    start_session_named(ADMIN_SESSION_NAME);
    unset($_SESSION['admin']);
    $GLOBALS['_ADMIN_SESSION'] = [];
    session_write_close();
    if (!empty($GLOBALS['_USER_SESSION_STARTED'])) {
        start_session_named(USER_SESSION_NAME);
    }
}
