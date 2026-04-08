<?php

declare(strict_types=1);

final class UserController extends Controller
{
    public function manageUsers(): void
    {
        require_admin_permission('admin.users.view');

        /** @var UserAdminService $userAdminService */
        $userAdminService = $this->service('admin/UserAdminService');
        $data = $userAdminService->buildManageUsersData($_GET);

        $this->adminView('admin/manage_users', [
            'users' => $data['users'],
            'keyword' => $data['keyword'],
            'state' => $data['state'],
            'page' => $data['page'],
            'perPage' => $data['perPage'],
            'total' => $data['total'],
            'totalPages' => $data['totalPages'],
            'notice' => (string) ($_GET['notice'] ?? ''),
            'roleNames' => $data['roleNames'],
            'canManageRoles' => admin_has_permission('admin.users.role.assign'),
        ]);
    }

    public function createAdminAccount(): void
    {
        require_admin_permission('admin.users.role.assign');

        /** @var UserAdminService $userAdminService */
        $userAdminService = $this->service('admin/UserAdminService');

        $ok = $userAdminService->createAdminAccount(
            (string) ($_POST['name'] ?? ''),
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['role'] ?? 'mod')
        );

        $this->redirect('/admin/users?notice=' . ($ok ? 'admin_created' : 'admin_create_failed'));
    }

    public function updateUserRole(string $id): void
    {
        require_admin_permission('admin.users.role.assign');

        $userId = (int) $id;
        $newRole = trim((string) ($_POST['role'] ?? 'user'));
        $admin = current_admin();
        $adminId = (int) ($admin['id'] ?? 0);

        if ($userId <= 0 || $userId === $adminId) {
            $this->redirect('/admin/users?notice=role_update_failed');
        }

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        $roles = $userModel->listRoleNames();
        if (!in_array($newRole, $roles, true)) {
            $this->redirect('/admin/users?notice=role_update_failed');
        }

        $target = $userModel->findById($userId);
        if (!$target) {
            $this->redirect('/admin/users?notice=role_update_failed');
        }

        $ok = $userModel->updateRoleById($userId, $newRole);
        $this->redirect('/admin/users?notice=' . ($ok ? 'role_updated' : 'role_update_failed'));
    }

    public function banUser(string $id): void
    {
        require_admin_permission('admin.users.ban');
        $userId = (int) $id;
        if ($userId > 0) {
            $reason = trim((string) ($_POST['ban_reason'] ?? ''));
            $reasonValue = $reason !== '' ? $reason : null;
            $durationDays = (int) ($_POST['ban_days'] ?? 0);
            $bannedUntil = null;
            if ($durationDays > 0) {
                $bannedUntil = date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'));
            }
            $admin = current_admin();
            $bannedBy = (int) ($admin['id'] ?? 0);

            /** @var UserModel $userModel */
            $userModel = $this->model('UserModel');
            $userModel->banById($userId, $bannedBy, $reasonValue, $bannedUntil);
            system_log_write('admin_action', 'admin.user.lock', 'success', null, 'user', $userId, [
                'reason' => $reasonValue,
                'banned_until' => $bannedUntil,
            ], $bannedBy > 0 ? $bannedBy : null, (string) (current_admin()['role'] ?? 'admin'));
        }
        $this->redirect('/admin/users?notice=banned');
    }

    public function unbanUser(string $id): void
    {
        require_admin_permission('admin.users.ban');
        $userId = (int) $id;
        if ($userId > 0) {
            /** @var UserModel $userModel */
            $userModel = $this->model('UserModel');
            $userModel->unbanById($userId);
            $admin = current_admin();
            $adminId = (int) ($admin['id'] ?? 0);
            system_log_write('admin_action', 'admin.user.unlock', 'success', null, 'user', $userId, null, $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));
        }
        $this->redirect('/admin/users?notice=unbanned');
    }

    public function deleteUser(string $id): void
    {
        require_admin_permission('admin.users.ban');
        $userId = (int) $id;
        if ($userId > 0) {
            /** @var UserModel $userModel */
            $userModel = $this->model('UserModel');
            $userModel->softDeleteById($userId);
            $admin = current_admin();
            $adminId = (int) ($admin['id'] ?? 0);
            system_log_write('admin_action', 'admin.user.soft_delete', 'success', null, 'user', $userId, null, $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));
        }
        $this->redirect('/admin/users?notice=deleted');
    }

    public function restoreUser(string $id): void
    {
        require_admin_permission('admin.users.ban');
        $userId = (int) $id;
        if ($userId > 0) {
            /** @var UserModel $userModel */
            $userModel = $this->model('UserModel');
            $userModel->restoreById($userId);
        }
        $this->redirect('/admin/users?notice=restored');
    }

    public function manageBans(): void
    {
        require_admin_permission('admin.users.ban');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $type = (string) ($_GET['type'] ?? '');
        if (!in_array($type, ['', 'account', 'comment', 'recipe'], true)) {
            $type = '';
        }
        $status = (string) ($_GET['status'] ?? '');
        if (!in_array($status, ['', 'active'], true)) {
            $status = '';
        }

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');

        $rows = [];
        foreach ($userModel->listActiveAccountBans($keyword !== '' ? $keyword : null) as $item) {
            $rows[] = [
                'source' => 'user_ban',
                'row_id' => (int) ($item['id'] ?? 0),
                'user_id' => (int) ($item['user_id'] ?? 0),
                'user_name' => (string) ($item['user_name'] ?? 'N/A'),
                'user_email' => (string) ($item['user_email'] ?? ''),
                'reason' => (string) ($item['reason'] ?? ''),
                'type_label' => 'Ban tạm thời',
                'started_at' => (string) ($item['started_at'] ?? ''),
                'expires_at' => (string) ($item['expires_at'] ?? ''),
                'status' => (string) ($item['status'] ?? 'active'),
                'action_key' => 'account',
            ];
        }

        foreach ($penaltyModel->listActiveBans($keyword !== '' ? $keyword : null) as $item) {
            $banType = (string) ($item['ban_type'] ?? '');
            // Ban từ bảng user_bans, bỏ qua action ban_* ở penalty để tránh trùng lặp
            if (str_starts_with($banType, 'ban_')) {
                continue;
            }
            $actionKey = 'comment';
            $typeLabel = 'Khóa bình luận';
            if (str_starts_with($banType, 'recipe_post_')) {
                $actionKey = 'recipe';
                $typeLabel = 'Khóa đăng bài';
            }

            $rows[] = [
                'source' => 'penalty',
                'row_id' => (int) ($item['id'] ?? 0),
                'user_id' => (int) ($item['user_id'] ?? 0),
                'user_name' => (string) ($item['user_name'] ?? 'N/A'),
                'user_email' => (string) ($item['user_email'] ?? ''),
                'reason' => (string) ($item['reason'] ?? ''),
                'type_label' => $typeLabel,
                'started_at' => (string) ($item['started_at'] ?? ''),
                'expires_at' => (string) ($item['expires_at'] ?? ''),
                'status' => (string) ($item['status'] ?? 'active'),
                'action_key' => $actionKey,
                'penalty_action' => $banType,
            ];
        }

        if ($type !== '') {
            $rows = array_values(array_filter($rows, static fn(array $r): bool => (string) ($r['action_key'] ?? '') === $type));
        }
        if ($status !== '') {
            $rows = array_values(array_filter($rows, static fn(array $r): bool => (string) ($r['status'] ?? '') === $status));
        }

        usort($rows, static function (array $a, array $b): int {
            $at = strtotime((string) ($a['started_at'] ?? '')) ?: 0;
            $bt = strtotime((string) ($b['started_at'] ?? '')) ?: 0;
            return $bt <=> $at;
        });

        $this->adminView('admin/manage_bans', [
            'rows' => $rows,
            'keyword' => $keyword,
            'type' => $type,
            'status' => $status,
            'notice' => (string) ($_GET['notice'] ?? ''),
        ]);
    }

    public function releaseBan(): void
    {
        require_admin_permission('admin.users.ban');

        $source = (string) ($_POST['source'] ?? '');
        $rowId = (int) ($_POST['row_id'] ?? 0);
        $userId = (int) ($_POST['user_id'] ?? 0);
        $penaltyAction = (string) ($_POST['penalty_action'] ?? '');

        $ok = false;
        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');

        if ($source === 'user_ban' && $userId > 0) {
            $ok = $userModel->unbanById($userId);
        } elseif ($source === 'penalty' && $rowId > 0) {
            $ok = $penaltyModel->deactivateById($rowId);
            if ($ok && str_starts_with($penaltyAction, 'ban_') && $userId > 0) {
                $userModel->unbanById($userId);
            }
        }

        $qs = [];
        $returnQ = trim((string) ($_POST['return_q'] ?? ''));
        $returnType = (string) ($_POST['return_type'] ?? '');
        $returnStatus = (string) ($_POST['return_status'] ?? '');
        if ($returnQ !== '') {
            $qs['q'] = $returnQ;
        }
        if ($returnType !== '') {
            $qs['type'] = $returnType;
        }
        if ($returnStatus !== '') {
            $qs['status'] = $returnStatus;
        }
        $qs['notice'] = $ok ? 'released' : 'release_failed';

        $this->redirect('/admin/bans?' . http_build_query($qs));
    }

    public function penalizeUser(string $id): void
    {
        require_admin_permission('admin.users.ban');
        $userId = (int) $id;
        if ($userId <= 0) {
            $this->redirect('/admin/reports?type=comment&notice=update_failed');
        }

        $penaltyAction = (string) ($_POST['penalty_action'] ?? 'warn');
        if (!in_array($penaltyAction, ['warn', 'comment_lock_3', 'comment_lock_7', 'comment_lock_permanent', 'recipe_lock_3', 'recipe_lock_7', 'recipe_lock_permanent', 'ban_permanent'], true)) {
            $penaltyAction = 'warn';
        }

        $reason = trim((string) ($_POST['reason'] ?? 'Vi phạm nội dung cộng đồng'));
        if ($reason === '') {
            $reason = 'Vi phạm nội dung cộng đồng';
        }
        $sourceType = (string) ($_POST['source_type'] ?? 'comment');
        if (!in_array($sourceType, ['comment', 'recipe', 'tip', 'ingredient', 'account'], true)) {
            $sourceType = 'comment';
        }
        $sourceId = (int) ($_POST['source_id'] ?? 0);
        $sourceIdValue = $sourceId > 0 ? $sourceId : null;

        $admin = current_admin();
        $adminId = (int) ($admin['id'] ?? 0);

        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');
        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');

        $ok = false;
        if ($penaltyAction === 'warn') {
            $ok = $penaltyModel->create(
                $userId,
                $adminId > 0 ? $adminId : null,
                $sourceType,
                $sourceIdValue,
                'warn',
                $reason,
                null,
                null
            );
        } elseif (str_starts_with($penaltyAction, 'comment_lock')) {
            $durationDays = null;
            $bannedUntil = null;
            $actionDb = 'comment_lock_permanent';
            if ($penaltyAction === 'comment_lock_3') {
                $durationDays = 3;
                $bannedUntil = date('Y-m-d H:i:s', strtotime('+3 days'));
                $actionDb = 'comment_lock_temp';
            } elseif ($penaltyAction === 'comment_lock_7') {
                $durationDays = 7;
                $bannedUntil = date('Y-m-d H:i:s', strtotime('+7 days'));
                $actionDb = 'comment_lock_temp';
            }
            $ok = $penaltyModel->create(
                $userId,
                $adminId > 0 ? $adminId : null,
                $sourceType,
                $sourceIdValue,
                $actionDb,
                $reason,
                $durationDays,
                $bannedUntil
            );
        } elseif (str_starts_with($penaltyAction, 'recipe_lock')) {
            $durationDays = null;
            $bannedUntil = null;
            $actionDb = 'recipe_post_lock_permanent';
            if ($penaltyAction === 'recipe_lock_3') {
                $durationDays = 3;
                $bannedUntil = date('Y-m-d H:i:s', strtotime('+3 days'));
                $actionDb = 'recipe_post_lock_temp';
            } elseif ($penaltyAction === 'recipe_lock_7') {
                $durationDays = 7;
                $bannedUntil = date('Y-m-d H:i:s', strtotime('+7 days'));
                $actionDb = 'recipe_post_lock_temp';
            }
            $ok = $penaltyModel->create(
                $userId,
                $adminId > 0 ? $adminId : null,
                $sourceType,
                $sourceIdValue,
                $actionDb,
                $reason,
                $durationDays,
                $bannedUntil
            );
        } else {
            $durationDays = null;
            $bannedUntil = null;
            $actionDb = 'ban_permanent';

            $banned = $userModel->banById($userId, $adminId, $reason, $bannedUntil);
            if ($banned) {
                $ok = $penaltyModel->create(
                    $userId,
                    $adminId > 0 ? $adminId : null,
                    $sourceType,
                    $sourceIdValue,
                    $actionDb,
                    $reason,
                    $durationDays,
                    $bannedUntil
                );
            }
        }

        $this->redirect('/admin/reports?type=comment&notice=' . ($ok ? 'updated' : 'update_failed'));
    }



    public function manageRelationships(): void
    {
        require_admin_permission('admin.relationships.view');

        /** @var UserAdminService $userAdminService */
        $userAdminService = $this->service('admin/UserAdminService');
        $data = $userAdminService->buildManageRelationshipsData($_GET);

        $this->adminView('admin/manage_relationships', [
            'rows' => $data['rows'],
            'keyword' => $data['keyword'],
            'userId' => $data['userId'],
            'side' => $data['side'],
            'risk' => $data['risk'],
            'page' => $data['page'],
            'perPage' => $data['perPage'],
            'total' => $data['total'],
            'totalPages' => $data['totalPages'],
            'top24h' => $data['top24h'],
            'notice' => (string) ($_GET['notice'] ?? ''),
            'canModerate' => admin_has_permission('admin.relationships.moderate'),
        ]);
    }

        public function removeRelationship(): void
    {
        require_admin_permission('admin.relationships.moderate');

        $followerId = (int) ($_POST['follower_id'] ?? 0);
        $followingId = (int) ($_POST['following_id'] ?? 0);

        /** @var UserAdminService $userAdminService */
        $userAdminService = $this->service('admin/UserAdminService');
        $ok = $userAdminService->removeRelationship($followerId, $followingId);

        $qs = $this->relationshipReturnQueryFromPost();
        $qs['notice'] = $ok ? 'removed' : 'remove_failed';

        $this->redirect('/admin/relationships?' . http_build_query($qs));
    }

        public function updateRelationshipLock(): void
    {
        require_admin_permission('admin.relationships.moderate');

        $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
        $mode = (string) ($_POST['mode'] ?? '');
        $days = (int) ($_POST['lock_days'] ?? 0);
        $reasonRaw = trim((string) ($_POST['reason'] ?? ''));
        $reason = $reasonRaw !== '' ? $reasonRaw : null;

        /** @var UserAdminService $userAdminService */
        $userAdminService = $this->service('admin/UserAdminService');
        $ok = $userAdminService->updateRelationshipLock($targetUserId, $mode, $days, $reason);

        $qs = $this->relationshipReturnQueryFromPost();
        $qs['notice'] = $ok ? 'lock_updated' : 'lock_update_failed';
        $this->redirect('/admin/relationships?' . http_build_query($qs));
    }

    private function relationshipReturnQueryFromPost(): array
    {
        $qs = [];
        $q = trim((string) ($_POST['return_q'] ?? ''));
        if ($q !== '') {
            $qs['q'] = $q;
        }
        $userId = max(0, (int) ($_POST['return_user_id'] ?? 0));
        if ($userId > 0) {
            $qs['user_id'] = $userId;
        }
        $side = (string) ($_POST['return_side'] ?? 'all');
        if (in_array($side, ['all', 'as_follower', 'as_following'], true) && $side !== 'all') {
            $qs['side'] = $side;
        }
        $risk = (string) ($_POST['return_risk'] ?? 'all');
        if (in_array($risk, ['all', 'suspicious', 'high_risk'], true) && $risk !== 'all') {
            $qs['risk'] = $risk;
        }
        return $qs;
    }


}
