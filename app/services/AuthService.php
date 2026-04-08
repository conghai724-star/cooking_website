<?php

declare(strict_types=1);

final class AuthService
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCK_MINUTES = 15;

    private UserModel $userModel;
    private LoginAttemptModel $attemptModel;

    public function __construct()
    {
        require_once APPROOT . '/app/models/UserModel.php';
        require_once APPROOT . '/app/models/LoginAttemptModel.php';

        $this->userModel = new UserModel();
        $this->attemptModel = new LoginAttemptModel();
    }

    public function authenticateUserPortal(string $email, string $password, string $ipAddress): array
    {
        $credential = $this->normalizedCredential($email);
        $locked = $this->checkRateLimit($credential, $ipAddress);
        if ($locked !== null) {
            return [
                'status' => 'locked',
                'error' => 'Tài khoản tạm khóa do nhập sai quá 5 lần. Thử lại sau ' . $locked['minutes'] . ' phút.',
                'lock_until' => $locked['lock_until'],
            ];
        }

        $user = $this->freshUserByEmail($email);
        if (!$user || !password_verify($password, (string) $user['password'])) {
            $this->attemptModel->registerFailure($credential, $ipAddress, self::LOGIN_MAX_ATTEMPTS, self::LOGIN_LOCK_MINUTES);
            return [
                'status' => $user ? 'wrong_password' : 'email_not_found',
                'error' => 'Email hoặc mật khẩu không đúng.',
                'user' => $user,
            ];
        }

        if (!empty($user['deleted_at'])) {
            return [
                'status' => 'deleted',
                'error' => 'Tài khoản đã bị xóa hoặc vô hiệu hóa.',
                'user' => $user,
            ];
        }

        if (($user['status'] ?? 'active') === 'banned') {
            return [
                'status' => 'banned',
                'error' => 'Tài khoản đã bị khóa.',
                'user' => $user,
            ];
        }

        if ($this->canAccessAdminPortal($user)) {
            $this->attemptModel->registerFailure($credential, $ipAddress, self::LOGIN_MAX_ATTEMPTS, self::LOGIN_LOCK_MINUTES);
            return [
                'status' => 'admin_account_on_user_portal',
                'error' => 'Tài khoản admin vui lòng đăng nhập tại trang quản trị.',
                'user' => $user,
            ];
        }

        $this->attemptModel->clear($credential, $ipAddress);
        return [
            'status' => 'success',
            'error' => '',
            'user' => $user,
        ];
    }

    public function authenticateAdminPortal(string $email, string $password, string $ipAddress): array
    {
        $credential = $this->normalizedCredential($email);
        $locked = $this->checkRateLimit($credential, $ipAddress);
        if ($locked !== null) {
            return [
                'status' => 'locked',
                'error' => 'Tài khoản tạm khóa do nhập sai quá 5 lần. Thử lại sau ' . $locked['minutes'] . ' phút.',
                'lock_until' => $locked['lock_until'],
            ];
        }

        $user = $this->freshUserByEmail($email);
        if (!$user || !password_verify($password, (string) $user['password'])) {
            $this->attemptModel->registerFailure($credential, $ipAddress, self::LOGIN_MAX_ATTEMPTS, self::LOGIN_LOCK_MINUTES);
            return [
                'status' => $user ? 'wrong_password' : 'email_not_found',
                'error' => 'Email hoặc mật khẩu không đúng.',
                'user' => $user,
            ];
        }

        if (!empty($user['deleted_at'])) {
            return [
                'status' => 'deleted',
                'error' => 'Tài khoản đã bị xóa hoặc vô hiệu hóa.',
                'user' => $user,
            ];
        }

        if (($user['status'] ?? 'active') === 'banned') {
            return [
                'status' => 'banned',
                'error' => 'Tài khoản đã bị khóa.',
                'user' => $user,
            ];
        }

        if (!$this->canAccessAdminPortal($user)) {
            $this->attemptModel->registerFailure($credential, $ipAddress, self::LOGIN_MAX_ATTEMPTS, self::LOGIN_LOCK_MINUTES);
            return [
                'status' => 'no_admin_permission',
                'error' => 'Tài khoản này không có quyền quản trị.',
                'user' => $user,
            ];
        }

        $this->attemptModel->clear($credential, $ipAddress);
        return [
            'status' => 'success',
            'error' => '',
            'user' => $user,
        ];
    }

    public function registerUser(string $username, string $email, string $password, string $confirmPassword): array
    {
        $username = trim($username);
        $email = trim($email);

        $old = [
            'username' => $username,
            'email' => $email,
        ];

        if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
            return ['success' => false, 'error' => 'Vui lòng nhập đầy đủ thông tin bắt buộc.', 'old' => $old];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email không hợp lệ.', 'old' => $old];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Mật khẩu phải có ít nhất 6 ký tự.', 'old' => $old];
        }
        if ($password !== $confirmPassword) {
            return ['success' => false, 'error' => 'Xác nhận mật khẩu không khớp.', 'old' => $old];
        }

        if ($this->userModel->findByEmail($email)) {
            return ['success' => false, 'error' => 'Email đã tồn tại.', 'old' => $old];
        }
        if ($this->userModel->findByUsername($username)) {
            return ['success' => false, 'error' => 'Tên đăng nhập đã tồn tại.', 'old' => $old];
        }

        if (!$this->userModel->create($username, $email, $password)) {
            return ['success' => false, 'error' => 'Không thể đăng ký tài khoản.', 'old' => $old];
        }

        return [
            'success' => true,
            'error' => '',
            'old' => $old,
            'new_user' => $this->userModel->findByEmail($email),
        ];
    }

    public function requestForgotPassword(string $email): array
    {
        $email = trim($email);
        $oldEmail = $email;

        if ($email === '') {
            return [
                'submitted' => false,
                'error' => 'Vui lòng nhập email.',
                'success' => '',
                'old_email' => $oldEmail,
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'submitted' => false,
                'error' => 'Email không hợp lệ.',
                'success' => '',
                'old_email' => $oldEmail,
            ];
        }

        return [
            'submitted' => true,
            'error' => '',
            'success' => 'Nếu email tồn tại, hệ thống sẽ gửi hướng dẫn đặt lại mật khẩu.',
            'old_email' => $oldEmail,
            'email_for_log' => $email,
        ];
    }

    public function logUserLoginResult(string $status, string $email, ?array $user, ?string $lockUntil = null): void
    {
        if ($status === 'locked') {
            system_log_write('auth', 'user.login', 'blocked', 'locked_by_rate_limit', 'user', null, [
                'email' => $email,
                'lock_until' => $lockUntil,
            ]);
            return;
        }
        if ($status === 'admin_account_on_user_portal') {
            system_log_write('auth', 'user.login', 'failed', 'admin_account_on_user_portal', 'user', (int) ($user['id'] ?? 0), [
                'email' => $email,
            ]);
            return;
        }
        if ($status === 'email_not_found') {
            system_log_write('auth', 'user.login', 'failed', 'email_not_found', 'user', null, [
                'email' => $email,
            ]);
            return;
        }
        if ($status === 'wrong_password') {
            system_log_write('auth', 'user.login', 'failed', 'wrong_password', 'user', (int) ($user['id'] ?? 0), [
                'email' => $email,
            ]);
            return;
        }
        if ($status === 'success') {
            system_log_write('auth', 'user.login', 'success', null, 'user', (int) ($user['id'] ?? 0), [
                'email' => $email,
            ], (int) ($user['id'] ?? 0), (string) ($user['role'] ?? 'user'));
        }
    }

    public function logAdminLoginResult(string $status, string $email, ?array $user, ?string $lockUntil = null): void
    {
        if ($status === 'locked') {
            system_log_write('auth', 'admin.login', 'blocked', 'locked_by_rate_limit', 'user', null, [
                'email' => $email,
                'lock_until' => $lockUntil,
            ]);
            return;
        }
        if ($status === 'no_admin_permission') {
            system_log_write('auth', 'admin.login', 'failed', 'no_admin_permission', 'user', (int) ($user['id'] ?? 0), [
                'email' => $email,
            ]);
            return;
        }
        if ($status === 'email_not_found') {
            system_log_write('auth', 'admin.login', 'failed', 'email_not_found', 'user', null, [
                'email' => $email,
            ]);
            return;
        }
        if ($status === 'wrong_password') {
            system_log_write('auth', 'admin.login', 'failed', 'wrong_password', 'user', (int) ($user['id'] ?? 0), [
                'email' => $email,
            ]);
            return;
        }
        if ($status === 'success') {
            system_log_write('auth', 'admin.login', 'success', null, 'user', (int) ($user['id'] ?? 0), [
                'email' => $email,
            ], (int) ($user['id'] ?? 0), (string) ($user['role'] ?? 'admin'));
        }
    }

    public function logRegisterSuccess(string $email, string $username, ?array $newUser): void
    {
        system_log_write('user_action', 'user.register', 'success', null, 'user', (int) ($newUser['id'] ?? 0), [
            'email' => $email,
            'username' => $username,
        ], (int) ($newUser['id'] ?? 0), 'user');
    }

    public function logForgotPasswordRequest(string $email): void
    {
        system_log_write('auth', 'user.forgot_password.request', 'success', null, 'user', null, [
            'email' => $email,
        ]);
    }

    private function normalizedCredential(string $email): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower(trim($email), 'UTF-8')
            : strtolower(trim($email));
    }

    private function freshUserByEmail(string $email): ?array
    {
        $user = $this->userModel->findByEmail($email);
        $this->userModel->clearExpiredBans();
        if ($user) {
            $user = $this->userModel->findByEmail($email);
        }

        return $user ?: null;
    }

    private function checkRateLimit(string $credential, string $ipAddress): ?array
    {
        $lockInfo = $this->attemptModel->getLockInfo($credential, $ipAddress);
        $lockUntil = (string) ($lockInfo['lock_until'] ?? '');
        if ($lockUntil === '' || strtotime($lockUntil) <= time()) {
            return null;
        }

        $seconds = max(1, strtotime($lockUntil) - time());
        return [
            'minutes' => (int) ceil($seconds / 60),
            'lock_until' => $lockUntil,
        ];
    }

    private function canAccessAdminPortal(array $user): bool
    {
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');
        if ($userId <= 0 || $role === '') {
            return false;
        }

        $permissionSet = permission_set_for_actor($userId, $role);
        return isset($permissionSet['*']) || isset($permissionSet['admin.dashboard.view']);
    }
}