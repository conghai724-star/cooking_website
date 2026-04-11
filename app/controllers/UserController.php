<?php

declare(strict_types=1);

class UserController extends Controller
{
    public function profile(): void
    {
        require_login();
        $this->renderProfile((int) current_user_id());
    }

    public function show(string $id): void
    {
        $this->renderProfile((int) $id);
    }

    public function followers(string $id): void
    {
        $this->renderConnections((int) $id, 'followers');
    }

    public function following(string $id): void
    {
        $this->renderConnections((int) $id, 'following');
    }

    public function follow(string $id): void
    {
        require_login();

        $targetUserId = (int) $id;
        $currentUserId = (int) current_user_id();
        $isAjax = $this->isAjaxRequest();
        $followingNow = false;
        $changed = false;
        $message = 'Đã cập nhật theo dõi.';

        if ($targetUserId > 0 && $targetUserId !== $currentUserId) {
            /** @var UserModel $userModel */
            $userModel = $this->model('UserModel');
            /** @var UserSafetyModel $safetyModel */
            $safetyModel = $this->model('UserSafetyModel');
            $targetUser = $userModel->findById($targetUserId);
            /** @var UserPenaltyModel $penaltyModel */
            $penaltyModel = $this->model('UserPenaltyModel');
            $activeFollowLock = $penaltyModel->getActiveFollowLock($currentUserId);
            if ($activeFollowLock !== null) {
                $message = 'Tài khoản đang bị khóa theo dõi tạm thời.';
                system_log_write('user_action', 'user.follow', 'blocked', 'follow_locked', 'user', $targetUserId, [
                    'target_user_id' => $targetUserId,
                ], $currentUserId, (string) (current_user()['role'] ?? 'user'));
            } elseif ($targetUser && !$safetyModel->isAnyBlockBetween($currentUserId, $targetUserId)) {
                /** @var FollowModel $followModel */
                $followModel = $this->model('FollowModel');
                $followModel->follow($currentUserId, $targetUserId);
                $followingNow = $followModel->isFollowing($currentUserId, $targetUserId);
                $changed = true;
                $message = $followingNow ? 'Đã theo dõi.' : 'Đã cập nhật theo dõi.';
                if ($followingNow) {
                    system_log_write('user_action', 'user.follow', 'success', null, 'user', $targetUserId, [
                        'target_user_id' => $targetUserId,
                    ], $currentUserId, (string) (current_user()['role'] ?? 'user'));
                    /** @var NotificationModel $notificationModel */
                    $notificationModel = $this->model('NotificationModel');
                    $actorName = (string) (current_user()['name'] ?? ('User #' . $currentUserId));
                    $notificationModel->create(
                        $targetUserId,
                        'follow',
                        $actorName . ' đã theo dõi bạn.',
                        '/users/' . $currentUserId
                    );
                }
            } else {
                $message = 'Không thể theo dõi tài khoản này.';
            }
        } else {
            $message = 'Không thể theo dõi tài khoản này.';
        }

        if ($isAjax) {
            if (!$changed) {
                $this->jsonError('FOLLOW_FAILED', $message, 422);
            }
            $this->jsonResponse([
                'success' => true,
                'following' => $followingNow,
                'message' => $message,
            ], 200);
        }

        $this->redirect($this->resolveRedirectPath('/users/' . $targetUserId));
    }

    public function unfollow(string $id): void
    {
        require_login();

        $targetUserId = (int) $id;
        $currentUserId = (int) current_user_id();
        $isAjax = $this->isAjaxRequest();
        $changed = false;
        $message = 'Đã cập nhật theo dõi.';

        if ($targetUserId > 0 && $targetUserId !== $currentUserId) {
            /** @var FollowModel $followModel */
            $followModel = $this->model('FollowModel');
            $followModel->unfollow($currentUserId, $targetUserId);
            $changed = true;
            $message = 'Đã hủy theo dõi.';
            system_log_write('user_action', 'user.unfollow', 'success', null, 'user', $targetUserId, [
                'target_user_id' => $targetUserId,
            ], $currentUserId, (string) (current_user()['role'] ?? 'user'));
        } else {
            $message = 'Không thể hủy theo dõi tài khoản này.';
        }

        if ($isAjax) {
            if (!$changed) {
                $this->jsonError('UNFOLLOW_FAILED', $message, 422);
            }
            $this->jsonResponse([
                'success' => true,
                'following' => false,
                'message' => $message,
            ], 200);
        }

        $this->redirect($this->resolveRedirectPath('/users/' . $targetUserId));
    }

    public function removeFollower(string $id): void
    {
        require_login();

        $targetFollowerId = (int) $id;
        $currentUserId = (int) current_user_id();

        if ($targetFollowerId > 0 && $targetFollowerId !== $currentUserId) {
            /** @var FollowModel $followModel */
            $followModel = $this->model('FollowModel');
            $followModel->removeFollower($currentUserId, $targetFollowerId);
            system_log_write('user_action', 'user.remove_follower', 'success', null, 'user', $targetFollowerId, [
                'target_user_id' => $targetFollowerId,
            ], $currentUserId, (string) (current_user()['role'] ?? 'user'));
        }

        $this->redirect($this->resolveRedirectPath('/users/' . $currentUserId . '/followers'));
    }

    public function openNotification(string $id): void
    {
        require_login();

        $notificationId = (int) $id;
        $userId = (int) current_user_id();
        $fallback = '/profile';

        /** @var NotificationModel $notificationModel */
        $notificationModel = $this->model('NotificationModel');
        $item = $notificationModel->findByIdForUser($notificationId, $userId);
        if (!$item) {
            $this->redirect($fallback);
        }

        $notificationModel->markReadByIdForUser($notificationId, $userId);

        $actionUrl = trim((string) ($item['action_url'] ?? ''));
        if ($actionUrl === '' || !str_starts_with($actionUrl, '/')) {
            $this->redirect($fallback);
        }

        $this->redirect($actionUrl);
    }

    public function reportUser(string $id): void
    {
        require_login();

        $targetUserId = (int) $id;
        $currentUserId = (int) current_user_id();
        if ($targetUserId <= 0) {
            $this->redirect('/profile?notice=user_action_failed');
        }
        if ($targetUserId === $currentUserId) {
            $this->redirect('/users/' . $targetUserId . '?notice=cannot_report_self');
        }

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        if (!$userModel->findById($targetUserId)) {
            $this->redirect('/profile?notice=user_action_failed');
        }

        $reason = trim((string) ($_POST['reason'] ?? ''));
        $details = trim((string) ($_POST['details'] ?? ''));
        if ($reason === '') {
            $this->redirect('/users/' . $targetUserId . '?notice=report_reason_required');
        }

        /** @var UserSafetyModel $safetyModel */
        $safetyModel = $this->model('UserSafetyModel');
        if ($safetyModel->hasReportedUser($currentUserId, $targetUserId)) {
            $this->redirect('/users/' . $targetUserId . '?notice=report_user_exists');
        }

        $ok = $safetyModel->reportUser($currentUserId, $targetUserId, $reason, $details !== '' ? $details : null);
        if ($ok) {
            /** @var NotificationModel $notificationModel */
            $notificationModel = $this->model('NotificationModel');
            $notificationModel->createForAdmins(
                'report_user',
                'Có báo cáo tài khoản mới (user ID: ' . $targetUserId . ').'
            );
        }
        $this->redirect('/users/' . $targetUserId . '?notice=' . ($ok ? 'report_user_success' : 'user_action_failed'));
    }

    public function blockUser(string $id): void
    {
        require_login();

        $targetUserId = (int) $id;
        $currentUserId = (int) current_user_id();
        if ($targetUserId <= 0) {
            $this->redirect('/profile?notice=user_action_failed');
        }
        if ($targetUserId === $currentUserId) {
            $this->redirect('/users/' . $targetUserId . '?notice=cannot_block_self');
        }

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        if (!$userModel->findById($targetUserId)) {
            $this->redirect('/profile?notice=user_action_failed');
        }

        /** @var UserSafetyModel $safetyModel */
        $safetyModel = $this->model('UserSafetyModel');
        if ($safetyModel->hasBlocked($currentUserId, $targetUserId)) {
            $this->redirect('/users/' . $targetUserId . '?notice=block_user_exists');
        }

        $ok = $safetyModel->blockUser($currentUserId, $targetUserId);
        if ($ok) {
            /** @var FollowModel $followModel */
            $followModel = $this->model('FollowModel');
            $followModel->unfollow($currentUserId, $targetUserId);
            $followModel->removeFollower($currentUserId, $targetUserId);
        }

        $this->redirect('/users/' . $targetUserId . '?notice=' . ($ok ? 'block_user_success' : 'user_action_failed'));
    }

    public function unblockUser(string $id): void
    {
        require_login();

        $targetUserId = (int) $id;
        $currentUserId = (int) current_user_id();
        if ($targetUserId <= 0) {
            $this->redirect('/profile?notice=user_action_failed');
        }
        if ($targetUserId === $currentUserId) {
            $this->redirect('/profile?notice=user_action_failed');
        }

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        if (!$userModel->findById($targetUserId)) {
            $this->redirect('/profile?notice=user_action_failed');
        }

        /** @var UserSafetyModel $safetyModel */
        $safetyModel = $this->model('UserSafetyModel');
        if (!$safetyModel->hasBlocked($currentUserId, $targetUserId)) {
            $this->redirect('/users/' . $targetUserId . '?notice=unblock_user_not_blocked');
        }

        $ok = $safetyModel->unblockUser($currentUserId, $targetUserId);
        $this->redirect('/users/' . $targetUserId . '?notice=' . ($ok ? 'unblock_user_success' : 'user_action_failed'));
    }

    public function appeals(): void
    {
        require_login();
        $userId = (int) current_user_id();

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');
        /** @var BanAppealModel $appealModel */
        $appealModel = $this->model('BanAppealModel');

        $activeTargets = [];

        $accountBan = $userModel->getActiveAccountBanByUserId($userId);
        if ($accountBan !== null) {
            $activeTargets[] = [
                'target_type' => 'user_ban',
                'target_id' => (int) ($accountBan['id'] ?? 0),
                'label' => 'Ban tài khoản',
                'reason' => (string) ($accountBan['reason'] ?? ''),
                'expires_at' => (string) ($accountBan['ban_until'] ?? ''),
                'created_at' => (string) ($accountBan['created_at'] ?? ''),
            ];
        }

        foreach ($penaltyModel->listActiveAppealableByUserId($userId) as $row) {
            $action = (string) ($row['action'] ?? '');
            $label = match (true) {
                str_starts_with($action, 'comment_lock_') => 'Khóa bình luận',
                str_starts_with($action, 'recipe_post_lock_') => 'Khóa đăng công thức',
                str_starts_with($action, 'tip_post_lock_') => 'Khóa đăng mẹo',
                str_starts_with($action, 'ingredient_post_lock_') => 'Khóa đăng nguyên liệu',
                str_starts_with($action, 'follow_lock_') => 'Khóa theo dõi',
                str_starts_with($action, 'ban_') => 'Ban tài khoản',
                default => $action,
            };
            $activeTargets[] = [
                'target_type' => 'user_penalty',
                'target_id' => (int) ($row['id'] ?? 0),
                'label' => $label,
                'reason' => (string) ($row['reason'] ?? ''),
                'expires_at' => (string) ($row['banned_until'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        $this->view('user/appeals', [
            'title' => 'Khiếu nại xử lý tài khoản',
            'useRecipeHubLayout' => true,
            'targets' => $activeTargets,
            'appeals' => $appealModel->listByUser($userId),
            'notice' => (string) ($_GET['notice'] ?? ''),
        ]);
    }

    public function submitAppeal(): void
    {
        require_login();
        $userId = (int) current_user_id();
        $targetType = (string) ($_POST['target_type'] ?? '');
        $targetId = (int) ($_POST['target_id'] ?? 0);
        $targetMixed = trim((string) ($_POST['target_type_target_id'] ?? ''));
        if ($targetMixed !== '' && str_contains($targetMixed, ':')) {
            [$targetTypePart, $targetIdPart] = explode(':', $targetMixed, 2);
            $targetType = trim($targetTypePart);
            $targetId = (int) trim($targetIdPart);
        }
        $appealReason = trim((string) ($_POST['appeal_reason'] ?? ''));
        $evidence = trim((string) ($_POST['evidence_text'] ?? ''));
        $evidenceValue = $evidence !== '' ? $evidence : null;

        if (!in_array($targetType, ['user_ban', 'user_penalty'], true) || $targetId <= 0 || $appealReason === '') {
            $this->redirect('/appeals?notice=appeal_invalid');
        }

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');
        /** @var BanAppealModel $appealModel */
        $appealModel = $this->model('BanAppealModel');

        $isValidTarget = false;
        if ($targetType === 'user_ban') {
            $activeBan = $userModel->getActiveAccountBanByUserId($userId);
            $isValidTarget = $activeBan !== null && (int) ($activeBan['id'] ?? 0) === $targetId;
        } else {
            foreach ($penaltyModel->listActiveAppealableByUserId($userId) as $penalty) {
                if ((int) ($penalty['id'] ?? 0) === $targetId) {
                    $isValidTarget = true;
                    break;
                }
            }
        }

        if (!$isValidTarget) {
            $this->redirect('/appeals?notice=appeal_target_not_found');
        }
        if ($appealModel->hasPendingAppeal($userId, $targetType, $targetId)) {
            $this->redirect('/appeals?notice=appeal_exists');
        }

        $ok = $appealModel->create($userId, $targetType, $targetId, $appealReason, $evidenceValue);
        $this->redirect('/appeals?notice=' . ($ok ? 'appeal_submitted' : 'appeal_failed'));
    }

    public function edit(): void
    {
        require_login();

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        /** @var EmailChangeRequestModel $emailChangeModel */
        $emailChangeModel = $this->model('EmailChangeRequestModel');
        $message = '';
        $error = '';
        $userId = (int) current_user_id();
        $user = $userModel->findById($userId);
        if (!$user) {
            $this->renderNotFound('Không tìm thấy người dùng.');
            return;
        }

        $notice = (string) ($_GET['notice'] ?? '');
        if ($notice === 'email_verified') {
            $message = 'Email đăng nhập được cập nhật.';
        } elseif ($notice === 'email_token_invalid') {
            $error = 'Liên kết xác nhận không hợp lệ hoặc đã hết hạn.';
        } elseif ($notice === 'email_already_used') {
            $error = 'Email mới đã được sử dụng.';
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $bio = trim((string) ($_POST['bio'] ?? ''));
            $currentPassword = (string) ($_POST['current_password'] ?? $_POST['email_current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmNewPassword = (string) ($_POST['confirm_new_password'] ?? '');
            $removeAvatar = (string) ($_POST['remove_avatar'] ?? '') === '1';
            $emailChanged = strcasecmp($email, (string) ($user['email'] ?? '')) !== 0;
            $passwordChangeRequested = ($newPassword !== '' || $confirmNewPassword !== '');

            if ($name === '' || $email === '') {
                $error = 'Vui lòng nhập đầy đủ tên và email.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email không hợp lệ.';
            } elseif (mb_strlen($name) > 100) {
                $error = 'Tên tối đa 100 ký tự.';
            } elseif (mb_strlen($bio) > 500) {
                $error = 'Giới thiệu tối đa 500 ký tự.';
            } elseif ($userModel->findByEmailExceptId($email, $userId)) {
                $error = 'Email đã được sử dụng.';
            } elseif ($passwordChangeRequested && strlen($newPassword) < 6) {
                $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            } elseif ($passwordChangeRequested && $newPassword !== $confirmNewPassword) {
                $error = 'Xác nhận mật khẩu mới không khớp.';
            } elseif ($emailChanged || $passwordChangeRequested) {
                $authUser = $userModel->findAuthById($userId);
                if (!$authUser || !password_verify($currentPassword, (string) ($authUser['password'] ?? ''))) {
                    $error = 'Cần nhập đúng mật khẩu hiện tại để đổi email hoặc mật khẩu.';
                }
            }

            if ($error === '') {
                $avatar = $removeAvatar ? null : (string) ($user['avatar'] ?? '');
                $uploadedAvatar = upload_image('avatar', APPROOT . '/public/uploads');
                if ($uploadedAvatar !== null) {
                    $avatar = $uploadedAvatar;
                }
                if ($avatar === '') {
                    $avatar = null;
                }

                $emailToSave = $emailChanged ? (string) ($user['email'] ?? '') : $email;
                $ok = $userModel->updateProfileDetails($userId, $name, $emailToSave, $bio, $avatar);
                if ($ok && $passwordChangeRequested) {
                    $ok = $userModel->updatePassword($userId, $newPassword);
                    if (!$ok) {
                        $error = 'Không thể cập nhật mật khẩu.';
                    }
                }

                if ($ok) {
                    system_log_write('user_action', 'user.update_profile', 'success', null, 'user', $userId, [
                        'email_changed' => $emailChanged,
                        'password_changed' => $passwordChangeRequested,
                        'avatar_removed' => $removeAvatar,
                    ], $userId, (string) (current_user()['role'] ?? 'user'));
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $emailToSave;
                    $_SESSION['user']['avatar'] = $avatar;
                    $_SESSION['user']['bio'] = $bio;

                    if ($emailChanged) {
                        $token = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $token);
                        $expiresAt = date('Y-m-d H:i:s', time() + 1800);
                        $emailChangeModel->createOrReplace($userId, $email, $tokenHash, $expiresAt);

                        $verifyPath = URLROOT . '/profile/verify-email-change?token=' . rawurlencode($token);
                        $verifyUrl = $this->absoluteUrl($verifyPath);
                        $sendOk = send_email_change_verification($email, $verifyUrl, (string) ($user['name'] ?? ''), '30 phút');
                        $message = $sendOk
                            ? 'Đã gửi email xác nhận đổi email. Vui lòng kiểm tra hộp thư. Liên kết hết hạn sau 30 phút.'
                            : 'Đã tạo yêu cầu đổi email. Trên localhost: vui lòng xem file storage/logs/mail.log để lấy liên kết xác nhận (hết hạn sau 30 phút).';
                    } else {
                        $message = $passwordChangeRequested
                            ? 'Cập nhật hồ sơ và mật khẩu thành công.'
                            : 'Cập nhật hồ sơ thành công.';
                    }

                    $user = $userModel->findById($userId) ?: $user;
                } elseif ($error === '') {
                    $error = 'Không thể cập nhật hồ sơ.';
                }
            }
        }

        $this->view('user/edit', [
            'title' => 'Sửa hồ sơ',
            'useRecipeHubLayout' => true,
            'user' => $user,
            'message' => $message,
            'error' => $error,
        ]);
    }

    public function verifyEmailChange(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            $this->redirect('/profile/edit?notice=email_token_invalid');
        }

        /** @var EmailChangeRequestModel $emailChangeModel */
        $emailChangeModel = $this->model('EmailChangeRequestModel');
        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');

        $tokenHash = hash('sha256', $token);
        $request = $emailChangeModel->findByTokenHash($tokenHash);
        if (!$request) {
            $this->redirect('/profile/edit?notice=email_token_invalid');
        }

        $userId = (int) ($request['user_id'] ?? 0);
        $newEmail = (string) ($request['new_email'] ?? '');
        $requestId = (int) ($request['id'] ?? 0);
        $usedAt = (string) ($request['used_at'] ?? '');
        $expiresAt = (string) ($request['expires_at'] ?? '');
        if ($userId <= 0 || $newEmail === '' || $requestId <= 0 || $usedAt !== '') {
            $this->redirect('/profile/edit?notice=email_token_invalid');
        }
        if ($expiresAt === '' || strtotime($expiresAt) < time()) {
            $this->redirect('/profile/edit?notice=email_token_invalid');
        }

        if ($userModel->findByEmailExceptId($newEmail, $userId)) {
            $emailChangeModel->markUsed($requestId);
            $this->redirect('/profile/edit?notice=email_already_used');
        }

        $updated = $userModel->updateEmail($userId, $newEmail);
        if (!$updated) {
            $this->redirect('/profile/edit?notice=email_token_invalid');
        }

        $emailChangeModel->markUsed($requestId);

        if (is_logged_in() && (int) (current_user_id() ?? 0) === $userId) {
            $_SESSION['user']['email'] = $newEmail;
        }

        $this->redirect('/profile/edit?notice=email_verified');
    }

    private function absoluteUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $normalizedPath = '/' . ltrim($path, '/');

        return $scheme . '://' . $host . $normalizedPath;
    }

    public function changePassword(): void
    {
        require_login();

        $message = '';
        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $newPassword = $_POST['new_password'] ?? '';
            if (strlen($newPassword) < 6) {
                $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            } else {
                /** @var UserModel $userModel */
                $userModel = $this->model('UserModel');
                $uid = (int) current_user_id();
                $userModel->updatePassword($uid, $newPassword);
                system_log_write('auth', 'user.change_password', 'success', null, 'user', $uid, null, $uid, (string) (current_user()['role'] ?? 'user'));
                $message = 'Đổi mật khẩu thành công.';
            }
        }

        $this->view('user/change_password', [
            'message' => $message,
            'error' => $error,
        ]);
    }

    private function renderProfile(int $profileUserId): void
    {
        if ($profileUserId <= 0) {
            $this->renderNotFound('Không tìm thấy người dùng.');
            return;
        }

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        /** @var FollowModel $followModel */
        $followModel = $this->model('FollowModel');
        /** @var UserSafetyModel $safetyModel */
        $safetyModel = $this->model('UserSafetyModel');
        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');

        $user = $userModel->findById($profileUserId);
        if (!$user) {
            $this->renderNotFound('Không tìm thấy người dùng.');
            return;
        }

        $viewerId = (int) (current_user_id() ?? 0);
        $isOwner = $viewerId > 0 && $viewerId === $profileUserId;
        $isFollowing = false;
        $isBlockedByViewer = false;
        $isViewerBlocked = false;
        $notice = (string) ($_GET['notice'] ?? '');

        if ($viewerId > 0 && !$isOwner) {
            $isBlockedByViewer = $safetyModel->hasBlocked($viewerId, $profileUserId);
            $isViewerBlocked = $safetyModel->hasBlocked($profileUserId, $viewerId);
            if (!$isBlockedByViewer && !$isViewerBlocked) {
                $isFollowing = $followModel->isFollowing($viewerId, $profileUserId);
            }
        }

        $blockRestricted = $viewerId > 0 && !$isOwner && ($isBlockedByViewer || $isViewerBlocked);

        if ($blockRestricted) {
            $recipes = [];
            $ingredients = [];
            $tips = [];
            $certificates = [];
            $certificateCount = 0;
            $followerCount = 0;
            $followingCount = 0;
        } else {
            $recipes = $recipeModel->byUser($profileUserId);
            $ingredients = $ingredientModel->byUser($profileUserId);
            $tips = $tipModel->byUser($profileUserId);
            $certificates = $quizModel->certificatesByUser($profileUserId);
            $certificateCount = $quizModel->certificateCountByUser($profileUserId);
            $followerCount = $followModel->countFollowers($profileUserId);
            $followingCount = $followModel->countFollowing($profileUserId);
        }

        $savedIngredients = ($isOwner && $viewerId > 0) ? $ingredientModel->savedByUser($viewerId) : [];
        $savedTips = ($isOwner && $viewerId > 0) ? $tipModel->savedByUser($viewerId) : [];
        $appeals = [];
        if ($isOwner && $viewerId > 0) {
            /** @var BanAppealModel $appealModel */
            $appealModel = $this->model('BanAppealModel');
            $appeals = $appealModel->listByUser($viewerId);
        }

        $this->view('user/profile', [
            'title' => 'Hồ sơ',
            'useRecipeHubLayout' => true,
            'user' => $user,
            'recipes' => $recipes,
            'ingredients' => $ingredients,
            'tips' => $tips,
            'saved_ingredients' => $savedIngredients,
            'saved_tips' => $savedTips,
            'certificates' => $certificates,
            'appeals' => $appeals,
            'follower_count' => $followerCount,
            'following_count' => $followingCount,
            'certificate_count' => $certificateCount,
            'is_owner' => $isOwner,
            'is_following' => $isFollowing,
            'is_blocked_by_viewer' => $isBlockedByViewer,
            'is_viewer_blocked' => $isViewerBlocked,
            'is_logged_in' => is_logged_in(),
            'notice' => $notice,
        ]);
    }

    private function renderConnections(int $profileUserId, string $type): void
    {
        $requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        if (str_ends_with($requestPath, '/following')) {
            $type = 'following';
        } elseif (str_ends_with($requestPath, '/followers')) {
            $type = 'followers';
        } elseif ($type !== 'following' && $type !== 'followers') {
            $type = 'followers';
        }
        if ($profileUserId <= 0) {
            $this->renderNotFound('Không tìm thấy người dùng.');
            return;
        }

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        /** @var FollowModel $followModel */
        $followModel = $this->model('FollowModel');
        /** @var UserSafetyModel $safetyModel */
        $safetyModel = $this->model('UserSafetyModel');

        $profileUser = $userModel->findById($profileUserId);
        if (!$profileUser) {
            $this->renderNotFound('Không tìm thấy người dùng.');
            return;
        }

        $viewerId = (int) (current_user_id() ?? 0);
        $isOwner = $viewerId > 0 && $viewerId === $profileUserId;

        $isBlockedByViewer = $viewerId > 0 && !$isOwner && $safetyModel->hasBlocked($viewerId, $profileUserId);
        $isViewerBlocked = $viewerId > 0 && !$isOwner && $safetyModel->hasBlocked($profileUserId, $viewerId);
        $blockRestricted = $isBlockedByViewer || $isViewerBlocked;

        if ($blockRestricted) {
            $followers = [];
            $following = [];
        } else {
            $followers = $followModel->followersOf($profileUserId, $viewerId);
            $following = $followModel->followingOf($profileUserId, $viewerId);
        }
        $items = $type === 'followers' ? $followers : $following;

        $this->view('user/connections', [
            'title' => $type === 'followers' ? 'Người theo dõi' : 'Đang theo dõi',
            'useRecipeHubLayout' => true,
            'profile_user' => $profileUser,
            'items' => $items,
            'followers' => $followers,
            'following' => $following,
            'type' => $type,
            'is_owner' => $isOwner,
            'viewer_id' => $viewerId,
            'is_logged_in' => is_logged_in(),
            'is_blocked_by_viewer' => $isBlockedByViewer,
            'is_viewer_blocked' => $isViewerBlocked,
            'block_restricted' => $blockRestricted,
        ]);
    }

    private function resolveRedirectPath(string $fallback): string
    {
        $redirectTo = trim((string) ($_POST['redirect_to'] ?? ''));
        if ($redirectTo !== '' && str_starts_with($redirectTo, '/')) {
            return $redirectTo;
        }

        return $fallback;
    }

    private function isAjaxRequest(): bool
    {
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        return strcasecmp($requestedWith, 'XMLHttpRequest') === 0 || str_contains($accept, 'application/json');
    }
}
