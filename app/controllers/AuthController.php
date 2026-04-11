<?php

declare(strict_types=1);

class AuthController extends Controller
{
    public function login(): void
    {
        if (is_logged_in()) {
            $this->redirect('/');
        }

        $error = '';
        $success = '';

        if (isset($_GET['error'])) {
            if ($_GET['error'] === 'google_cancel') {
                $error = 'Bạn đã hủy quá trình đăng nhập qua Google.';
            } elseif ($_GET['error'] === 'google_failed') {
                $error = 'Có lỗi xảy ra khi xác thực qua Google. Vui lòng thử lại.';
            }
        }

        if (isset($_GET['registered']) && $_GET['registered'] === '1') {
            $success = 'Đăng ký thành công. Vui lòng đăng nhập.';
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $ipAddress = $this->clientIp();

            /** @var AuthService $authService */
            $authService = $this->service('AuthService');
            $result = $authService->authenticateUserPortal($email, $password, $ipAddress);

            $status = (string) ($result['status'] ?? 'failed');
            $error = (string) ($result['error'] ?? 'Đăng nhập thất bại.');
            $user = isset($result['user']) && is_array($result['user']) ? $result['user'] : null;

            if ($status === 'success' && $user !== null) {
                session_regenerate_id(true);
                set_user_session([
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ]);
                $authService->logUserLoginResult($status, $email, $user);
                $this->redirect('/');
            }

            $authService->logUserLoginResult($status, $email, $user, isset($result['lock_until']) ? (string) $result['lock_until'] : null);
        }

        $this->view('auth/login', [
            'title' => 'Đăng nhập',
            'useRecipeHubLayout' => true,
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function forgotPassword(): void
    {
        if (is_logged_in()) {
            $this->redirect('/');
        }

        $error = '';
        $success = '';
        $oldEmail = '';

        $cooldownRemaining = 0;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = (string) ($_POST['email'] ?? '');

            /** @var AuthService $authService */
            $authService = $this->service('AuthService');
            $result = $authService->requestForgotPassword($email);

            $error = (string) ($result['error'] ?? '');
            $success = (string) ($result['success'] ?? '');
            $oldEmail = (string) ($result['old_email'] ?? '');
            $cooldownRemaining = isset($result['cooldown_remaining']) ? (int) $result['cooldown_remaining'] : 0;

            if (!empty($result['submitted'])) {
                $authService->logForgotPasswordRequest((string) ($result['email_for_log'] ?? $email));
            }
        }

        $this->view('auth/forgot_password', [
            'title' => 'Quên mật khẩu',
            'useRecipeHubLayout' => true,
            'error' => $error,
            'success' => $success,
            'oldEmail' => $oldEmail,
            'cooldownRemaining' => $cooldownRemaining,
        ]);
    }

    public function resetPassword(): void
    {
        if (is_logged_in()) {
            $this->redirect('/');
        }

        $error = '';
        $success = '';
        $token = '';
        $showForm = true;

        /** @var AuthService $authService */
        $authService = $this->service('AuthService');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $token = (string) ($_POST['token'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_new_password'] ?? '');

            $result = $authService->resetPassword($token, $newPassword, $confirmPassword);
            $error = (string) ($result['error'] ?? '');
            $success = (string) ($result['success'] ? 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập ngay.' : '');
            $showForm = isset($result['show_form']) ? (bool) $result['show_form'] : false;
        } else {
            $token = trim((string) ($_GET['token'] ?? ''));
            if ($token === '') {
                $error = 'Liên kết đặt lại mật khẩu không hợp lệ.';
                $showForm = false;
            } else {
                $validation = $authService->validateForgotPasswordToken($token);
                if ($validation['status'] !== 'valid') {
                    $error = (string) ($validation['error'] ?? 'Liên kết đặt lại mật khẩu không hợp lệ.');
                    $showForm = false;
                }
            }
        }

        $this->view('auth/reset_password', [
            'title' => 'Đặt lại mật khẩu',
            'useRecipeHubLayout' => true,
            'error' => $error,
            'success' => $success,
            'token' => $token,
            'showForm' => $showForm,
        ]);
    }

    public function adminLogin(): void
    {
        if (is_admin()) {
            $this->redirect('/admin');
        }

        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $ipAddress = $this->clientIp();

            /** @var AuthService $authService */
            $authService = $this->service('AuthService');
            $result = $authService->authenticateAdminPortal($email, $password, $ipAddress);

            $status = (string) ($result['status'] ?? 'failed');
            $error = (string) ($result['error'] ?? 'Đăng nhập thất bại.');
            $user = isset($result['user']) && is_array($result['user']) ? $result['user'] : null;

            if ($status === 'success' && $user !== null) {
                set_admin_session([
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ]);
                $authService->logAdminLoginResult($status, $email, $user);
                $this->redirect('/admin');
            }

            $authService->logAdminLoginResult($status, $email, $user, isset($result['lock_until']) ? (string) $result['lock_until'] : null);
        }

        $this->view('admin/auth/admin_login', [
            'title' => 'Đăng nhập quản trị',
            'useRecipeHubLayout' => true,
            'error' => $error,
        ]);
    }

    public function googleLogin(): void
    {
        if (is_logged_in()) {
            $this->redirect('/');
        }

        $client = new \Google\Client();
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $client->setRedirectUri(GOOGLE_REDIRECT_URI);
        $client->addScope('email');
        $client->addScope('profile');

        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $client->setState($state);

        $authUrl = $client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }

    public function googleCallback(): void
    {
        if (is_logged_in()) {
            $this->redirect('/');
        }

        if (isset($_GET['error']) || !isset($_GET['code'])) {
            $this->redirect('/login?error=google_cancel');
        }

        if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
            $this->redirect('/login?error=google_failed');
        }

        $client = new \Google\Client();
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $client->setRedirectUri(GOOGLE_REDIRECT_URI);

        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (isset($token['error'])) {
            $this->redirect('/login?error=google_failed');
        }

        $client->setAccessToken($token);
        $googleOauth = new \Google\Service\Oauth2($client);
        $googleAccountInfo = $googleOauth->userinfo->get();

        if (!$googleAccountInfo->email || !$googleAccountInfo->verifiedEmail) {
            $this->redirect('/login?error=google_failed');
        }

        $googleData = [
            'google_id' => $googleAccountInfo->id,
            'email' => $googleAccountInfo->email,
            'name' => $googleAccountInfo->name,
            'avatar' => !empty($googleAccountInfo->picture) ? $googleAccountInfo->picture : '/assets/images/default-avatar.png',
        ];

        /** @var AuthService $authService */
        $authService = $this->service('AuthService');
        $result = $authService->authenticateWithGoogle($googleData);

        $status = (string) ($result['status'] ?? 'failed');
        $user = isset($result['user']) && is_array($result['user']) ? $result['user'] : null;

        if ($status === 'success' && $user !== null) {
            session_regenerate_id(true);
            set_user_session([
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ]);
            $authService->logUserLoginResult('success', $googleData['email'], $user, null, 'google');
            $this->redirect('/');
        }

        $this->redirect('/login?error=google_failed');
    }

    public function register(): void
    {
        if (is_logged_in()) {
            $this->redirect('/');
        }

        $error = '';
        $old = ['username' => '', 'email' => ''];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $username = (string) ($_POST['username'] ?? '');
            $email = (string) ($_POST['email'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            /** @var AuthService $authService */
            $authService = $this->service('AuthService');
            $result = $authService->registerUser($username, $email, $password, $confirmPassword);

            $error = (string) ($result['error'] ?? '');
            $old = is_array($result['old'] ?? null) ? $result['old'] : $old;

            if (!empty($result['success'])) {
                $newUser = is_array($result['new_user'] ?? null) ? $result['new_user'] : null;
                $authService->logRegisterSuccess($email, $username, $newUser);
                $this->redirect('/login?registered=1');
            }
        }

        $this->view('auth/register', [
            'title' => 'Đăng ký',
            'useRecipeHubLayout' => true,
            'error' => $error,
            'old' => $old,
        ]);
    }

    public function logout(): void
    {
        $user = current_user();
        system_log_write(
            'auth',
            'user.logout',
            'success',
            null,
            'user',
            (int) ($user['id'] ?? 0),
            null,
            (int) ($user['id'] ?? 0),
            (string) ($user['role'] ?? 'user')
        );
        clear_user_session();
        session_regenerate_id(true);
        $this->redirect('/login');
    }

    public function adminLogout(): void
    {
        $admin = current_admin();
        system_log_write(
            'auth',
            'admin.logout',
            'success',
            null,
            'user',
            (int) ($admin['id'] ?? 0),
            null,
            (int) ($admin['id'] ?? 0),
            (string) ($admin['role'] ?? 'admin')
        );
        clear_admin_session();
        session_regenerate_id(true);
        $this->redirect('/admin/login');
    }

    private function clientIp(): string
    {
        $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (!filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            $remoteAddr = '0.0.0.0';
        }

        if (!$this->isTrustedProxy($remoteAddr)) {
            return $remoteAddr;
        }

        $xff = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($xff !== '') {
            $parts = array_map('trim', explode(',', $xff));
            foreach ($parts as $candidate) {
                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        return $remoteAddr;
    }

    private function isTrustedProxy(string $ip): bool
    {
        $trusted = $this->trustedProxyIps();
        if ($trusted === []) {
            return false;
        }

        return in_array($ip, $trusted, true);
    }

    private function trustedProxyIps(): array
    {
        $raw = getenv('TRUSTED_PROXY_IPS');
        if ($raw === false || trim((string) $raw) === '') {
            return [];
        }

        $items = array_map('trim', explode(',', (string) $raw));
        $ips = [];
        foreach ($items as $item) {
            if ($item !== '' && filter_var($item, FILTER_VALIDATE_IP)) {
                $ips[] = $item;
            }
        }

        return array_values(array_unique($ips));
    }
}
