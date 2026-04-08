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
        $message = 'A�Ă£ cA�º­p nhA�º­t theo dĂµi.';

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
                $message = 'TĂ i khoA�º£n A�‘ang bA�»‹ khĂ³a theo dĂµi tA�º¡m thA�»i.';
                system_log_write('user_action', 'user.follow', 'blocked', 'follow_locked', 'user', $targetUserId, [
                    'target_user_id' => $targetUserId,
                ], $currentUserId, (string) (current_user()['role'] ?? 'user'));
            } elseif ($targetUser && !$safetyModel->isAnyBlockBetween($currentUserId, $targetUserId)) {
                /** @var FollowModel $followModel */
                $followModel = $this->model('FollowModel');
                $followModel->follow($currentUserId, $targetUserId);
                $followingNow = $followModel->isFollowing($currentUserId, $targetUserId);
                $changed = true;
                $message = $followingNow ? 'A�Ă£ theo dĂµi.' : 'A�Ă£ cA�º­p nhA�º­t theo dĂµi.';
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
                        $actorName . ' A�‘Ă£ theo dĂµi bA�º¡n.',
                        '/users/' . $currentUserId
                    );
                }
            } else {
                $message = 'KhĂ´ng thA�»ƒ theo dĂµi tĂ i khoA�º£n nĂ y.';
            }
        } else {
            $message = 'KhĂ´ng thA�»ƒ theo dĂµi tĂ i khoA�º£n nĂ y.';
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
        $message = 'A�Ă£ cA�º­p nhA�º­t theo dĂµi.';

        if ($targetUserId > 0 && $targetUserId !== $currentUserId) {
            /** @var FollowModel $followModel */
            $followModel = $this->model('FollowModel');
            $followModel->unfollow($currentUserId, $targetUserId);
            $changed = true;
            $message = 'A�Ă£ hA�»§y theo dĂµi.';
            system_log_write('user_action', 'user.unfollow', 'success', null, 'user', $targetUserId, [
                'target_user_id' => $targetUserId,
            ], $currentUserId, (string) (current_user()['role'] ?? 'user'));
        } else {
            $message = 'KhĂ´ng thA�»ƒ hA�»§y theo dĂµi tĂ i khoA�º£n nĂ y.';
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
                'CĂ³ bĂ¡o cĂ¡o tĂ i khoA�º£n mA�»›i (user ID: ' . $targetUserId . ').'
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
                'label' => 'Ban tĂ i khoA�º£n',
                'reason' => (string) ($accountBan['reason'] ?? ''),
                'expires_at' => (string) ($accountBan['ban_until'] ?? ''),
                'created_at' => (string) ($accountBan['created_at'] ?? ''),
            ];
        }

        foreach ($penaltyModel->listActiveAppealableByUserId($userId) as $row) {
            $action = (string) ($row['action'] ?? '');
            $label = match (true) {
                str_starts_with($action, 'comment_lock_') => 'KhĂ³a bĂ¬nh luA�º­n',
                str_starts_with($action, 'recipe_post_lock_') => 'KhÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â³a Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¢Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â€Â¬Ă‚ÂÄ‚â€Ă‚Â¬Ă„â€Ă¢â‚¬Â¹Ä‚â€¦Ă¢â‚¬Å“Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚Â Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¢ng cÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â´ng thĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡Ä‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â»Ä‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â©c',
                str_starts_with($action, 'tip_post_lock_') => 'KhÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â³a Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¢Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â€Â¬Ă‚ÂÄ‚â€Ă‚Â¬Ă„â€Ă¢â‚¬Â¹Ä‚â€¦Ă¢â‚¬Å“Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚Â Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¢ng mĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡Ä‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚ÂºÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¹o',
                str_starts_with($action, 'ingredient_post_lock_') => 'KhÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â³a Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¢Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â€Â¬Ă‚ÂÄ‚â€Ă‚Â¬Ă„â€Ă¢â‚¬Â¹Ä‚â€¦Ă¢â‚¬Å“Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚Â Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¢ng nguyÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Âªn liĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡Ä‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â»Ä‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¢Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â€Â¬Ă‚ÂÄ‚â€Ă‚Â¬Ă„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡u',
                str_starts_with($action, 'follow_lock_') => 'KhÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â³a theo dÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Âµi',
                str_starts_with($action, 'ban_') => 'Ban tĂ i khoA�º£n',
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
            'title' => 'KhiA�º¿u nA�º¡i xA�»­ lĂ½ tĂ i khoA�º£n',
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
            $this->renderNotFound('KhAA�¿½A�¿A�½ng tAA�¿½A�¿A�½m th?y ngu?i dAA�¿½A�¿A�½ng.');
            return;
        }

        $notice = (string) ($_GET['notice'] ?? '');
        if ($notice === 'email_verified') {
            $message = 'Email A�‘A�ƒng nhA�º­p A�‘A�°A�»£c cA�º­p nhA�º­t.';
        } elseif ($notice === 'email_token_invalid') {
            $error = 'LiAA�¿½A�¿A�½n k?t xAA�¿½A�¿A�½c nh?n khAA�¿½A�¿A�½ng h?p l? ho?c dAA�¿½A�¿A�½ h?t h?n.';
        } elseif ($notice === 'email_already_used') {
            $error = 'Email m?i dAA�¿½A�¿A�½ du?c s? d?ng.';
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
                $error = 'Vui lAA�¿½A�¿A�½ng nh?p d?y d? tAA�¿½A�¿A�½n vAA�¿½A�¿A�½ email.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email khAAA�¿½A�¿A�½ng hAA�¿½A�»A�£p lAA�¿½A�»A�€¡.';
            } elseif (mb_strlen($name) > 100) {
                $error = 'TAA�¿½A�¿A�½n t?i da 100 kAA�¿½A�¿A�½ t?.';
            } elseif (mb_strlen($bio) > 500) {
                $error = 'Gi?i thi?u t?i da 500 kAA�¿½A�¿A�½ t?.';
            } elseif ($userModel->findByEmailExceptId($email, $userId)) {
                $error = 'Email dAA�¿½A�¿A�½ du?c s? d?ng.';
            } elseif ($passwordChangeRequested && strlen($newPassword) < 6) {
                $error = 'MAA�¿½A�ºA�­t khAA�¿½A�ºA�©u mAA�¿½A�»A�€ºi phAA�¿½A�ºA�£i cAAA�¿½A�¿A�½ AAA�¿½A�¿A�½t nhAA�¿½A�ºA�¥t 6 kAAA�¿½A�¿A�½ tAA�¿½A�»A�±.';
            } elseif ($passwordChangeRequested && $newPassword !== $confirmNewPassword) {
                $error = 'XAAA�¿½A�¿A�½c nhAA�¿½A�ºA�­n mAA�¿½A�ºA�­t khAA�¿½A�ºA�©u mAA�¿½A�»A�€ºi khAAA�¿½A�¿A�½ng khAA�¿½A�»A�€ºp.';
            } elseif ($emailChanged || $passwordChangeRequested) {
                $authUser = $userModel->findAuthById($userId);
                if (!$authUser || !password_verify($currentPassword, (string) ($authUser['password'] ?? ''))) {
                    $error = 'C?n nh?p dAA�¿½A�¿A�½ng m?t kh?u hi?n t?i d? d?i email ho?c m?t kh?u.';
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
                        $error = 'KhAA�¿½A�¿A�½ng thAA�¿½A�»A�’ cAA�¿½A�ºA�­p nhAA�¿½A�ºA�­t mAA�¿½A�ºA�­t khAA�¿½A�ºA�©u.';
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
                        $sendOk = send_email_change_verification($email, $verifyUrl, (string) ($user['name'] ?? ''), '30 phAA�¿½A�¿A�½t');
                        $message = $sendOk
                            ? 'AA�¿½A�¿A�½AA�¿½A�¿A�½ g?i email xAA�¿½A�¿A�½c nh?n d?i email. Vui lAA�¿½A�¿A�½ng ki?m tra h?p thu. LiAA�¿½A�¿A�½n k?t h?t h?n sau 30 phAA�¿½A�¿A�½t.'
                            : 'AA�¿½A�¿A�½AA�¿½A�¿A�½ t?o yAA�¿½A�¿A�½u c?u d?i email. TrAA�¿½A�¿A�½n localhost: vui lAA�¿½A�¿A�½ng xem file storage/logs/mail.log d? l?y liAA�¿½A�¿A�½n k?t xAA�¿½A�¿A�½c nh?n (h?t h?n sau 30 phAA�¿½A�¿A�½t).';
                    } else {
                        $message = $passwordChangeRequested
                            ? 'CAA�¿½A�ºA�­p nhAA�¿½A�ºA�­t hAA�¿½A�»A�€œ sAA�¿½A�¡ vAAA�¿½A�¿A�½ mAA�¿½A�ºA�­t khAA�¿½A�ºA�©u thAAA�¿½A�¿A�½nh cAAA�¿½A�¿A�½ng.'
                            : 'CAA�¿½A�ºA�­p nhAA�¿½A�ºA�­t hAA�¿½A�»A�€œ sAA�¿½A�¡ thAAA�¿½A�¿A�½nh cAAA�¿½A�¿A�½ng.';
                    }

                    $user = $userModel->findById($userId) ?: $user;
                } elseif ($error === '') {
                    $error = 'KhAA�¿½A�¿A�½ng thAA�¿½A�»A�’ cAA�¿½A�ºA�­p nhAA�¿½A�ºA�­t hAA�¿½A�»A�€œ sAA�¿½A�¡.';
                }
            }
        }

        $this->view('user/edit', [
            'title' => 'SAA�¿½A�»A�­a hAA�¿½A�»A�€œ sAA�¿½A�¡',
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
                $error = 'MAA�¿½A�ºA�­t khAA�¿½A�ºA�©u mAA�¿½A�»A�€ºi phAA�¿½A�ºA�£i cAAA�¿½A�¿A�½ AAA�¿½A�¿A�½t nhAA�¿½A�ºA�¥t 6 kAAA�¿½A�¿A�½ tAA�¿½A�»A�±.';
            } else {
                /** @var UserModel $userModel */
                $userModel = $this->model('UserModel');
                $uid = (int) current_user_id();
                $userModel->updatePassword($uid, $newPassword);
                system_log_write('auth', 'user.change_password', 'success', null, 'user', $uid, null, $uid, (string) (current_user()['role'] ?? 'user'));
                $message = 'AA�¿½A�¿A�½?i m?t kh?u thAA�¿½A�¿A�½nh cAA�¿½A�¿A�½ng.';
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
            $this->renderNotFound('KhAA�¿½A�¿A�½ng tAAA�¿½A�¿A�½m thAA�¿½A�ºA�¥y ngAA�¿½A�°AA�¿½A�»A�i dAAA�¿½A�¿A�½ng.');
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
            $this->renderNotFound('KhAA�¿½A�¿A�½ng tAAA�¿½A�¿A�½m thAA�¿½A�ºA�¥y ngAA�¿½A�°AA�¿½A�»A�i dAAA�¿½A�¿A�½ng.');
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
            'title' => 'HÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â»Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¢Ä‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¢Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¬Ä‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚Â¦Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€¦Ă¢â‚¬Å“ sÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¢Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â€Â¬Ă‚ÂÄ‚â€Ă‚Â¬Ă„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡',
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
            $this->renderNotFound('KhAA�¿½A�¿A�½ng tAAA�¿½A�¿A�½m thAA�¿½A�ºA�¥y ngAA�¿½A�°AA�¿½A�»A�i dAAA�¿½A�¿A�½ng.');
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
            $this->renderNotFound('KhAA�¿½A�¿A�½ng tAAA�¿½A�¿A�½m thAA�¿½A�ºA�¥y ngAA�¿½A�°AA�¿½A�»A�i dAAA�¿½A�¿A�½ng.');
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
            'title' => $type === 'followers' ? 'NgA�°A�»i theo dĂµi' : 'A�ang theo dĂµi',
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
