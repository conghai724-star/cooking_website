<?php

declare(strict_types=1);

final class AdminService
{
    private UserModel $userModel;

    public function __construct()
    {
        require_once APPROOT . '/app/models/UserModel.php';
        $this->userModel = new UserModel();
    }

    public function buildManageUsersData(array $query): array
    {
        $keyword = trim((string) ($query['q'] ?? ''));
        $state = (string) ($query['state'] ?? 'all');
        if (!in_array($state, ['all', 'active', 'banned', 'deleted'], true)) {
            $state = 'all';
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $stateFilter = $state === 'all' ? null : $state;

        $keywordFilter = $keyword !== '' ? $keyword : null;
        $total = $this->userModel->countForAdmin($keywordFilter, $stateFilter);
        $users = $this->userModel->allForAdminPaged($perPage, $offset, $keywordFilter, $stateFilter);
        $roleNames = $this->userModel->listRoleNames();

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'users' => $users,
            'keyword' => $keyword,
            'state' => $state,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'roleNames' => $roleNames,
        ];
    }

    public function createAdminAccount(string $name, string $email, string $password, string $role): bool
    {
        $name = trim($name);
        $email = trim($email);
        $role = trim($role);

        $roles = $this->userModel->listRoleNames();
        $adminRoles = array_values(array_filter($roles, static fn(string $r): bool => $r !== 'user'));
        if (!in_array($role, $adminRoles, true)) {
            $role = 'mod';
        }

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            return false;
        }

        if ($this->userModel->findByEmail($email)) {
            return false;
        }

        return $this->userModel->createWithRole($name, $email, $password, $role);
    }

    public function buildManageRelationshipsData(array $query): array
    {
        require_once APPROOT . '/app/models/FollowModel.php';
        require_once APPROOT . '/app/models/UserPenaltyModel.php';

        $keyword = trim((string) ($query['q'] ?? ''));
        $userId = max(0, (int) ($query['user_id'] ?? 0));

        $side = (string) ($query['side'] ?? 'all');
        if (!in_array($side, ['all', 'as_follower', 'as_following'], true)) {
            $side = 'all';
        }

        $risk = (string) ($query['risk'] ?? 'all');
        if (!in_array($risk, ['all', 'suspicious', 'high_risk'], true)) {
            $risk = 'all';
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $followModel = new FollowModel();
        $penaltyModel = new UserPenaltyModel();

        $keywordFilter = $keyword !== '' ? $keyword : null;
        $userFilter = $userId > 0 ? $userId : null;

        $total = $followModel->countForAdmin($keywordFilter, $userFilter, $side, $risk);
        $rows = $followModel->listForAdmin($perPage, $offset, $keywordFilter, $userFilter, $side, $risk);

        $followerIds = [];
        foreach ($rows as $row) {
            $fid = (int) ($row['follower_id'] ?? 0);
            if ($fid > 0) {
                $followerIds[] = $fid;
            }
        }

        $followLocks = $penaltyModel->mapActiveFollowLocks($followerIds);
        foreach ($rows as &$row) {
            $hour = (int) ($row['follows_last_hour'] ?? 0);
            $day = (int) ($row['follows_last_24h'] ?? 0);
            $row['risk_level'] = ($hour >= 20 || $day >= 80)
                ? 'high_risk'
                : (($hour >= 10 || $day >= 30) ? 'suspicious' : 'normal');
            $fid = (int) ($row['follower_id'] ?? 0);
            $row['follow_lock'] = $followLocks[$fid] ?? null;
        }
        unset($row);

        $top24h = $followModel->listTopFollowersBy24h(10);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'rows' => $rows,
            'keyword' => $keyword,
            'userId' => $userId,
            'side' => $side,
            'risk' => $risk,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'top24h' => $top24h,
        ];
    }

    public function removeRelationship(int $followerId, int $followingId): bool
    {
        if ($followerId <= 0 || $followingId <= 0) {
            return false;
        }

        require_once APPROOT . '/app/models/FollowModel.php';
        require_once APPROOT . '/app/models/NotificationModel.php';
        require_once APPROOT . '/app/models/AdminActionLogModel.php';

        $followModel = new FollowModel();
        $ok = $followModel->forceRemove($followerId, $followingId);
        if (!$ok) {
            return false;
        }

        $admin = current_admin();
        $adminId = (int) ($admin['id'] ?? 0);

        system_log_write('admin_action', 'admin.relationship.force_unfollow', 'success', null, 'follow', null, [
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ], $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));

        $notificationModel = new NotificationModel();
        $notificationModel->create(
            $followerId,
            'moderation_relationship',
            'Moi quan he theo dõi cua ban da bi go bo do vi pham quy dinh.',
            '/profile'
        );

        $logModel = new AdminActionLogModel();
        $logModel->create(
            $adminId > 0 ? $adminId : null,
            'relationship.force_unfollow',
            'follow',
            null,
            json_encode(['follower_id' => $followerId, 'following_id' => $followingId], JSON_UNESCAPED_UNICODE)
        );

        return true;
    }

    public function updateRelationshipLock(int $targetUserId, string $mode, int $days, ?string $reason): bool
    {
        if ($targetUserId <= 0) {
            return false;
        }

        require_once APPROOT . '/app/models/UserPenaltyModel.php';
        require_once APPROOT . '/app/models/AdminActionLogModel.php';
        require_once APPROOT . '/app/models/NotificationModel.php';

        $penaltyModel = new UserPenaltyModel();
        $logModel = new AdminActionLogModel();
        $notificationModel = new NotificationModel();

        $admin = current_admin();
        $adminId = (int) ($admin['id'] ?? 0);
        $adminRole = (string) ($admin['role'] ?? 'admin');

        if ($mode === 'unlock') {
            $ok = $penaltyModel->deactivateActiveByUserAndActions($targetUserId, ['follow_lock_temp', 'follow_lock_permanent']);
            if (!$ok) {
                return false;
            }

            system_log_write('admin_action', 'admin.user.follow_unlock', 'success', null, 'user', $targetUserId, [
                'mode' => 'unlock',
                'reason' => $reason,
            ], $adminId > 0 ? $adminId : null, $adminRole);

            $notificationModel->create(
                $targetUserId,
                'moderation_follow_unlock',
                'Tinh nang theo dõi cua ban da duoc mo khoa.',
                '/profile'
            );

            $logModel->create(
                $adminId > 0 ? $adminId : null,
                'relationship.follow_lock.unlock',
                'user',
                $targetUserId,
                json_encode(['target_user_id' => $targetUserId], JSON_UNESCAPED_UNICODE)
            );

            return true;
        }

        if ($mode !== 'temp' && $mode !== 'permanent') {
            return false;
        }

        $action = $mode === 'temp' ? 'follow_lock_temp' : 'follow_lock_permanent';
        $durationDays = $mode === 'temp' ? max(1, $days) : null;
        $bannedUntil = $durationDays !== null
            ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
            : null;

        $penaltyModel->deactivateActiveByUserAndActions($targetUserId, ['follow_lock_temp', 'follow_lock_permanent']);
        $ok = $penaltyModel->create(
            $targetUserId,
            $adminId > 0 ? $adminId : null,
            'account',
            null,
            $action,
            $reason,
            $durationDays,
            $bannedUntil
        );

        if (!$ok) {
            return false;
        }

        system_log_write('admin_action', 'admin.user.follow_lock', 'success', null, 'user', $targetUserId, [
            'mode' => $mode,
            'days' => $durationDays,
            'reason' => $reason,
        ], $adminId > 0 ? $adminId : null, $adminRole);

        $content = $mode === 'temp'
            ? ('Ban da bi khoa tinh nang theo dõi trong ' . $durationDays . ' ngay.')
            : 'Ban da bi khoa tinh nang theo dõi vo thoi han.';
        if ($reason !== null && $reason !== '') {
            $content .= ' Ly do: ' . $reason;
        }

        $notificationModel->create(
            $targetUserId,
            'moderation_follow_lock',
            $content,
            '/profile'
        );

        $logModel->create(
            $adminId > 0 ? $adminId : null,
            'relationship.follow_lock.set',
            'user',
            $targetUserId,
            json_encode([
                'target_user_id' => $targetUserId,
                'mode' => $mode,
                'days' => $durationDays,
                'reason' => $reason,
            ], JSON_UNESCAPED_UNICODE)
        );

        return true;
    }

    public function buildManageReportsData(array $query): array
    {
        $status = (string) ($query['status'] ?? '');
        if (!in_array($status, ['', 'pending', 'reviewed', 'resolved'], true)) {
            $status = '';
        }
        $type = (string) ($query['type'] ?? '');
        if (!in_array($type, ['', 'recipe', 'tip', 'ingredient', 'comment', 'account', 'post'], true)) {
            $type = '';
        }
        $keyword = trim((string) ($query['q'] ?? ''));
        require_once APPROOT . '/app/models/RecipeModel.php';
        require_once APPROOT . '/app/models/CommentModel.php';
        require_once APPROOT . '/app/models/TipModel.php';
        require_once APPROOT . '/app/models/IngredientModel.php';
        require_once APPROOT . '/app/models/UserSafetyModel.php';
        require_once APPROOT . '/app/models/UserPenaltyModel.php';

        /** @var RecipeModel $recipeModel */
        $recipeModel = new RecipeModel();
        /** @var CommentModel $commentModel */
        $commentModel = new CommentModel();
        /** @var TipModel $tipModel */
        $tipModel = new TipModel();
        /** @var IngredientModel $ingredientModel */
        $ingredientModel = new IngredientModel();
        /** @var UserSafetyModel $userSafetyModel */
        $userSafetyModel = new UserSafetyModel();
        /** @var UserModel $userModel */
        $userModel = new UserModel();
        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = new UserPenaltyModel();
        $recipeReports = $recipeModel->allReportsForAdmin($status !== '' ? $status : null);
        $tipReports = $tipModel->allReportsForAdmin($status !== '' ? $status : null);
        $ingredientReports = $ingredientModel->allReportsForAdmin($status !== '' ? $status : null);
        $commentReports = $commentModel->allReportsForAdmin($status !== '' ? $status : null);
        $userReports = $userSafetyModel->allUserReportsForAdmin($status !== '' ? $status : null);

        $rows = [];
        foreach ($recipeReports as $item) {
            $targetUserId = (int) ($item['target_user_id'] ?? 0);
            $rows[] = [
                'kind' => 'recipe',
                'content_type' => 'recipe',
                'id' => (int) ($item['id'] ?? 0),
                'target_id' => (int) ($item['recipe_id'] ?? 0),
                'status' => (string) ($item['status'] ?? 'pending'),
                'reason' => (string) ($item['reason'] ?? ''),
                'reporter_name' => (string) ($item['reporter_name'] ?? 'An danh'),
                'target_title' => (string) ($item['recipe_title'] ?? 'Không xác định'),
                'target_link' => URLROOT . '/admin/recipes/' . (int) ($item['recipe_id'] ?? 0),
                'created_at' => (string) ($item['created_at'] ?? ''),
                'meta' => 'Thong tin bo sung',
                'comment_content' => '',
                'target_comment_id' => 0,
                'target_user_id' => $targetUserId,
                'recipe_status' => (string) ($item['recipe_status'] ?? ''),
                'recipe_deleted_at' => (string) ($item['recipe_deleted_at'] ?? ''),
                'has_recipe_lock' => $targetUserId > 0 ? ($penaltyModel->getActiveRecipePostLock($targetUserId) !== null) : false,
                'has_account_ban' => $targetUserId > 0 ? $userModel->hasActiveBan($targetUserId) : false,
            ];
        }
        foreach ($commentReports as $item) {
            $contentType = (string) ($item['content_type'] ?? 'recipe');
            $targetId = (int) ($item['target_id'] ?? 0);
            $targetSlug = trim((string) ($item['target_slug'] ?? ''));
            $targetLink = match ($contentType) {
                'tip' => URLROOT . '/tips/' . ($targetSlug !== '' ? rawurlencode($targetSlug) : $targetId),
                'ingredient' => URLROOT . '/ingredients/' . $targetId,
                'post' => URLROOT . '/posts/' . $targetId,
                default => URLROOT . '/admin/recipes/' . $targetId,
            };
            $targetUserId = (int) ($item['target_user_id'] ?? 0);
            $rows[] = [
                'kind' => 'comment',
                'content_type' => $contentType,
                'id' => (int) ($item['id'] ?? 0),
                'target_id' => $targetId,
                'status' => (string) ($item['status'] ?? 'pending'),
                'reason' => (string) ($item['reason'] ?? ''),
                'reporter_name' => (string) ($item['reporter_name'] ?? 'An danh'),
                'target_title' => (string) ($item['target_title'] ?? 'Không xác định'),
                'target_link' => $targetLink,
                'created_at' => (string) ($item['created_at'] ?? ''),
                'meta' => 'Bao cao binh luan tren ' . $contentType,
                'comment_content' => (string) ($item['comment_content'] ?? ''),
                'comment_status' => (string) ($item['comment_status'] ?? 'active'),
                'target_comment_id' => (int) ($item['comment_id'] ?? 0),
                'target_user_id' => $targetUserId,
                'has_comment_lock' => $targetUserId > 0 ? ($penaltyModel->getActiveCommentLock($targetUserId) !== null) : false,
                'has_recipe_lock' => false,
                'has_account_ban' => $targetUserId > 0 ? $userModel->hasActiveBan($targetUserId) : false,
            ];
        }
        foreach ($tipReports as $item) {
            $targetUserId = (int) ($item['target_user_id'] ?? 0);
            $tipId = (int) ($item['tip_id'] ?? 0);
            $tipSlug = trim((string) ($item['tip_slug'] ?? ''));
            $rows[] = [
                'kind' => 'tip',
                'content_type' => 'tip',
                'id' => (int) ($item['id'] ?? 0),
                'target_id' => $tipId,
                'status' => (string) ($item['status'] ?? 'pending'),
                'reason' => (string) ($item['reason'] ?? ''),
                'reporter_name' => (string) ($item['reporter_name'] ?? 'An danh'),
                'target_title' => (string) ($item['tip_title'] ?? ('Tip #' . $tipId)),
                'target_link' => URLROOT . '/tips/' . ($tipSlug !== '' ? rawurlencode($tipSlug) : $tipId),
                'created_at' => (string) ($item['created_at'] ?? ''),
                'meta' => 'Thong tin bo sung',
                'comment_content' => '',
                'comment_status' => '',
                'target_comment_id' => 0,
                'target_user_id' => $targetUserId,
                'has_recipe_lock' => false,
                'has_tip_lock' => $targetUserId > 0 ? ($penaltyModel->getActiveTipPostLock($targetUserId) !== null) : false,
                'has_account_ban' => $targetUserId > 0 ? $userModel->hasActiveBan($targetUserId) : false,
                'content_status' => (string) ($item['content_status'] ?? ''),
            ];
        }
        foreach ($ingredientReports as $item) {
            $targetUserId = (int) ($item['target_user_id'] ?? 0);
            $ingredientId = (int) ($item['ingredient_id'] ?? 0);
            $rows[] = [
                'kind' => 'ingredient',
                'content_type' => 'ingredient',
                'id' => (int) ($item['id'] ?? 0),
                'target_id' => $ingredientId,
                'status' => (string) ($item['status'] ?? 'pending'),
                'reason' => (string) ($item['reason'] ?? ''),
                'reporter_name' => (string) ($item['reporter_name'] ?? 'An danh'),
                'target_title' => (string) ($item['ingredient_name'] ?? ('Ingredient #' . $ingredientId)),
                'target_link' => URLROOT . '/ingredients/' . $ingredientId,
                'created_at' => (string) ($item['created_at'] ?? ''),
                'meta' => 'Thong tin bo sung',
                'comment_content' => '',
                'comment_status' => '',
                'target_comment_id' => 0,
                'target_user_id' => $targetUserId,
                'has_recipe_lock' => false,
                'has_ingredient_lock' => $targetUserId > 0 ? ($penaltyModel->getActiveIngredientPostLock($targetUserId) !== null) : false,
                'has_account_ban' => $targetUserId > 0 ? $userModel->hasActiveBan($targetUserId) : false,
                'content_status' => (string) ($item['content_status'] ?? ''),
            ];
        }
        foreach ($userReports as $item) {
            $targetUserId = (int) ($item['reported_user_id'] ?? 0);
            $rows[] = [
                'kind' => 'account',
                'content_type' => 'account',
                'id' => (int) ($item['id'] ?? 0),
                'target_id' => $targetUserId,
                'status' => (string) ($item['status'] ?? 'pending'),
                'reason' => (string) ($item['reason'] ?? ''),
                'reporter_name' => (string) ($item['reporter_name'] ?? 'An danh'),
                'target_title' => (string) ($item['reported_name'] ?? ('User #' . $targetUserId)),
                'target_link' => URLROOT . '/users/' . $targetUserId,
                'created_at' => (string) ($item['created_at'] ?? ''),
                'meta' => 'Thong tin bo sung',
                'comment_content' => (string) ($item['details'] ?? ''),
                'comment_status' => '',
                'target_comment_id' => 0,
                'target_user_id' => $targetUserId,
                'has_recipe_lock' => false,
                'has_account_ban' => $targetUserId > 0 ? $userModel->hasActiveBan($targetUserId) : false,
            ];
        }

        if ($type !== '') {
            $rows = array_values(array_filter($rows, static fn(array $r): bool => (string) ($r['kind'] ?? '') === $type));
        }
        if ($keyword !== '') {
            $kw = function_exists('mb_strtolower') ? mb_strtolower($keyword, 'UTF-8') : strtolower($keyword);
            $rows = array_values(array_filter($rows, static function (array $r) use ($kw): bool {
                $haystack = (string) (($r['reason'] ?? '') . ' ' . ($r['target_title'] ?? '') . ' ' . ($r['reporter_name'] ?? '') . ' ' . ($r['meta'] ?? ''));
                $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
                return str_contains($haystack, $kw);
            }));
        }
        usort($rows, static function (array $a, array $b): int {
            $at = strtotime((string) ($a['created_at'] ?? '')) ?: 0;
            $bt = strtotime((string) ($b['created_at'] ?? '')) ?: 0;
            return $bt <=> $at;
        });

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 20;
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $rows = array_slice($rows, $offset, $perPage);

        return [
            'status' => $status,
            'type' => $type,
            'keyword' => $keyword,
            'rows' => $rows,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
        ];
    }

    public function handleReportAction(array $input): bool
    {

        $reportId = (int) ($input['report_id'] ?? 0);
        require_once APPROOT . '/app/models/RecipeModel.php';
        require_once APPROOT . '/app/models/CommentModel.php';
        require_once APPROOT . '/app/models/TipModel.php';
        require_once APPROOT . '/app/models/IngredientModel.php';
        require_once APPROOT . '/app/models/UserSafetyModel.php';
        require_once APPROOT . '/app/models/UserPenaltyModel.php';
        $kind = (string) ($input['kind'] ?? '');
        $contentType = (string) ($input['content_type'] ?? 'recipe');
        $targetId = (int) ($input['target_id'] ?? 0);
        $targetCommentId = (int) ($input['target_comment_id'] ?? 0);
        $targetUserId = (int) ($input['target_user_id'] ?? 0);
        $action = (string) ($input['action'] ?? '');
        $lockDays = max(0, (int) ($input['lock_days'] ?? 0));
        $banDays = max(0, (int) ($input['ban_days'] ?? 0));
        $actionReason = trim((string) ($input['action_reason'] ?? ''));

        $ok = false;
        if ($kind === 'recipe' && $targetId > 0) {
            /** @var RecipeModel $recipeModel */
            $recipeModel = new RecipeModel();
            if ($targetUserId <= 0) {
                $targetUserId = (int) ($recipeModel->findOwnerIdAnyStatus($targetId) ?? 0);
            }
            if ($action === 'recipe_reject' || $action === 'recipe_hide') {
                $ok = $recipeModel->setStatus($targetId, 'rejected');
            } elseif ($action === 'recipe_unhide') {
                $ok = $recipeModel->setStatus($targetId, 'approved');
                if ($ok) {
                    $recipeModel->setUserState($targetId, 'published');
                }
            } elseif ($action === 'recipe_restore') {
                $ok = $recipeModel->restoreById($targetId);
                if ($ok) {
                    $recipeModel->setStatus($targetId, 'approved');
                    $recipeModel->setUserState($targetId, 'published');
                }
            } elseif ($action === 'recipe_delete') {
                // Da loai bo chuoi comment loi ma hoa qua dai de tranh ton bo nho.
                $ok = $recipeModel->deleteById($targetId);
            } elseif (($action === 'user_recipe_lock_7' || $action === 'user_recipe_lock') && $targetUserId > 0) {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                $admin = current_admin();
                $adminId = (int) ($admin['id'] ?? 0);
                $durationDays = $action === 'user_recipe_lock_7' ? 7 : ($lockDays > 0 ? $lockDays : null);
                $bannedUntil = $durationDays !== null
                    ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
                    : null;
                $penaltyAction = $durationDays === null ? 'recipe_post_lock_permanent' : 'recipe_post_lock_temp';
                $ok = $penaltyModel->create(
                    $targetUserId,
                    $adminId > 0 ? $adminId : null,
                    'recipe',
                    $targetId,
                    $penaltyAction,
                    $actionReason !== '' ? $actionReason : 'Vi pham tieu chuan cong dong.',
                    $durationDays,
                    $bannedUntil
                );
            } elseif ($action === 'user_recipe_unlock' && $targetUserId > 0) {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                $ok = $penaltyModel->deactivateActiveByUserAndActions($targetUserId, [
                    'recipe_post_lock_temp',
                    'recipe_post_lock_permanent',
                ]);
            } elseif ($action === 'user_ban_account' && $targetUserId > 0) {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                /** @var UserModel $userModel */
                $userModel = new UserModel();
                $admin = current_admin();
                $adminId = (int) ($admin['id'] ?? 0);
                $durationDays = $banDays > 0 ? $banDays : null;
                $bannedUntil = $durationDays !== null
                    ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
                    : null;
                $reason = $actionReason !== '' ? $actionReason : 'Vi pham tieu chuan cong dong.';

                $banned = $userModel->banById($targetUserId, $adminId, $reason, $bannedUntil);
                if ($banned) {
                    $ok = $penaltyModel->create(
                        $targetUserId,
                        $adminId > 0 ? $adminId : null,
                        'recipe',
                        $targetId,
                        $durationDays === null ? 'ban_permanent' : 'ban_temp',
                        $reason,
                        $durationDays,
                        $bannedUntil
                    );
                }
            } elseif ($action === 'user_unban_account' && $targetUserId > 0) {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                /** @var UserModel $userModel */
                $userModel = new UserModel();
                $ok = $userModel->unbanById($targetUserId);
                if ($ok) {
                    $penaltyModel->deactivateActiveByUserAndActions($targetUserId, [
                        'ban_temp',
                        'ban_permanent',
                    ]);
                }
            }
            if ($ok && $reportId > 0) {
                $recipeModel->updateReportStatus($reportId, 'resolved');
            }
        } elseif ($kind === 'comment' && $targetCommentId > 0) {
            /** @var CommentModel $commentModel */
            $commentModel = new CommentModel();
            if ($targetUserId <= 0) {
                $commentRow = $commentModel->findById($targetCommentId);
                $targetUserId = (int) (($commentRow['user_id'] ?? 0));
            }
            if ($action === 'comment_hide') {
                $ok = $commentModel->setStatusByType($targetCommentId, $contentType, 'hidden');
            } elseif ($action === 'comment_unhide') {
                $ok = $commentModel->setStatusByType($targetCommentId, $contentType, 'active');
            } elseif ($action === 'comment_delete') {
                $ok = $commentModel->deleteByType($targetCommentId, $contentType);
            } elseif ($action === 'comment_restore') {
                $ok = $commentModel->restoreByType($targetCommentId, $contentType);
            } elseif ($action === 'user_comment_lock' && $targetUserId > 0) {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                $admin = current_admin();
                $adminId = (int) ($admin['id'] ?? 0);
                $durationDays = $lockDays > 0 ? $lockDays : null;
                $bannedUntil = $durationDays !== null
                    ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
                    : null;
                $penaltyAction = $durationDays === null ? 'comment_lock_permanent' : 'comment_lock_temp';
                $ok = $penaltyModel->create(
                    $targetUserId,
                    $adminId > 0 ? $adminId : null,
                    'comment',
                    $targetCommentId,
                    $penaltyAction,
                    $actionReason !== '' ? $actionReason : 'Vi pham tieu chuan cong dong.',
                    $durationDays,
                    $bannedUntil
                );
            } elseif ($action === 'user_ban_account' && $targetUserId > 0) {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                /** @var UserModel $userModel */
                $userModel = new UserModel();
                $admin = current_admin();
                $adminId = (int) ($admin['id'] ?? 0);
                $durationDays = $banDays > 0 ? $banDays : null;
                $bannedUntil = $durationDays !== null
                    ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
                    : null;
                $reason = $actionReason !== '' ? $actionReason : 'Vi pham tieu chuan cong dong.';

                $banned = $userModel->banById($targetUserId, $adminId, $reason, $bannedUntil);
                if ($banned) {
                    $ok = true;
                    $penaltyModel->create(
                        $targetUserId,
                        $adminId > 0 ? $adminId : null,
                        'comment',
                        $targetCommentId,
                        $durationDays === null ? 'ban_permanent' : 'ban_temp',
                        $reason,
                        $durationDays,
                        $bannedUntil
                    );
                }
            } elseif ($action === 'user_unban_account' && $targetUserId > 0) {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                /** @var UserModel $userModel */
                $userModel = new UserModel();
                $ok = $userModel->unbanById($targetUserId);
                if ($ok) {
                    $penaltyModel->deactivateActiveByUserAndActions($targetUserId, [
                        'ban_temp',
                        'ban_permanent',
                    ]);
                }
            } elseif ($action === 'user_comment_unlock' && $targetUserId > 0) {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                $ok = $penaltyModel->deactivateActiveByUserAndActions($targetUserId, [
                    'comment_lock_temp',
                    'comment_lock_permanent',
                ]);
            } elseif ($action === 'user_comment_lock_7' && $targetUserId > 0) {
                // Backward compatibility from older UI
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                $admin = current_admin();
                $adminId = (int) ($admin['id'] ?? 0);
                $ok = $penaltyModel->create(
                    $targetUserId,
                    $adminId > 0 ? $adminId : null,
                    'comment',
                    $targetCommentId,
                    'comment_lock_temp',
                    'Vi pham tieu chuan cong dong.',
                    7,
                    date('Y-m-d H:i:s', strtotime('+7 days'))
                );
            }
            if ($ok && $reportId > 0) {
                $commentModel->updateReportStatus($reportId, $contentType, 'resolved');
            }
        } elseif (($kind === 'tip' || $kind === 'ingredient') && $targetId > 0) {
            if ($kind === 'tip') {
                /** @var TipModel $tipModel */
                $tipModel = new TipModel();
                if ($targetUserId <= 0) {
                    $tip = $tipModel->findById($targetId);
                    $targetUserId = (int) (($tip['user_id'] ?? 0));
                }
                if ($action === 'content_hide') {
                    $ok = $tipModel->setStatus($targetId, 'rejected');
                } elseif ($action === 'content_unhide') {
                    $ok = $tipModel->setStatus($targetId, 'approved');
                } elseif ($action === 'content_delete') {
                    $ok = $tipModel->setStatus($targetId, 'rejected');
                } elseif ($action === 'content_restore') {
                    $ok = $tipModel->setStatus($targetId, 'approved');
                } elseif (($action === 'user_tip_lock_7' || $action === 'user_tip_lock') && $targetUserId > 0) {
                    /** @var UserPenaltyModel $penaltyModel */
                    $penaltyModel = new UserPenaltyModel();
                    $admin = current_admin();
                    $adminId = (int) ($admin['id'] ?? 0);
                    $durationDays = $action === 'user_tip_lock_7' ? 7 : ($lockDays > 0 ? $lockDays : null);
                    $bannedUntil = $durationDays !== null
                        ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
                        : null;
                    $penaltyAction = $durationDays === null ? 'tip_post_lock_permanent' : 'tip_post_lock_temp';
                    $ok = $penaltyModel->create(
                        $targetUserId,
                        $adminId > 0 ? $adminId : null,
                        'tip',
                        $targetId,
                        $penaltyAction,
                        $actionReason !== '' ? $actionReason : 'Vi pham tieu chuan cong dong.',
                        $durationDays,
                        $bannedUntil
                    );
                } elseif ($action === 'user_tip_unlock' && $targetUserId > 0) {
                    /** @var UserPenaltyModel $penaltyModel */
                    $penaltyModel = new UserPenaltyModel();
                    $ok = $penaltyModel->deactivateActiveByUserAndActions($targetUserId, [
                        'tip_post_lock_temp',
                        'tip_post_lock_permanent',
                    ]);
                } elseif ($action === 'user_ban_account' && $targetUserId > 0) {
                    /** @var UserPenaltyModel $penaltyModel */
                    $penaltyModel = new UserPenaltyModel();
                    /** @var UserModel $userModel */
                    $userModel = new UserModel();
                    $admin = current_admin();
                    $adminId = (int) ($admin['id'] ?? 0);
                    $durationDays = $banDays > 0 ? $banDays : null;
                    $bannedUntil = $durationDays !== null
                        ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
                        : null;
                    $reason = $actionReason !== '' ? $actionReason : 'Vi pham tieu chuan cong dong.';

                    $banned = $userModel->banById($targetUserId, $adminId, $reason, $bannedUntil);
                    if ($banned) {
                        $ok = $penaltyModel->create(
                            $targetUserId,
                            $adminId > 0 ? $adminId : null,
                            'tip',
                            $targetId,
                            $durationDays === null ? 'ban_permanent' : 'ban_temp',
                            $reason,
                            $durationDays,
                            $bannedUntil
                        );
                    }
                } elseif ($action === 'user_unban_account' && $targetUserId > 0) {
                    /** @var UserPenaltyModel $penaltyModel */
                    $penaltyModel = new UserPenaltyModel();
                    /** @var UserModel $userModel */
                    $userModel = new UserModel();
                    $ok = $userModel->unbanById($targetUserId);
                    if ($ok) {
                        $penaltyModel->deactivateActiveByUserAndActions($targetUserId, [
                            'ban_temp',
                            'ban_permanent',
                        ]);
                    }
                }
                if ($ok && $reportId > 0) {
                    $tipModel->updateReportStatus($reportId, 'resolved');
                }
            } else {
                /** @var IngredientModel $ingredientModel */
                $ingredientModel = new IngredientModel();
                if ($targetUserId <= 0) {
                    $ingredient = $ingredientModel->findById($targetId, 'library');
                    if ($ingredient === false) {
                        $ingredient = $ingredientModel->findById($targetId, 'recipe');
                    }
                    $targetUserId = (int) (($ingredient['user_id'] ?? 0));
                }
                if ($action === 'content_hide') {
                    $ok = $ingredientModel->setStatus($targetId, 'rejected');
                } elseif ($action === 'content_unhide') {
                    $ok = $ingredientModel->setStatus($targetId, 'approved');
                } elseif ($action === 'content_delete') {
                    $ok = $ingredientModel->setStatus($targetId, 'rejected');
                } elseif ($action === 'content_restore') {
                    $ok = $ingredientModel->setStatus($targetId, 'approved');
                } elseif (($action === 'user_ingredient_lock_7' || $action === 'user_ingredient_lock') && $targetUserId > 0) {
                    /** @var UserPenaltyModel $penaltyModel */
                    $penaltyModel = new UserPenaltyModel();
                    $admin = current_admin();
                    $adminId = (int) ($admin['id'] ?? 0);
                    $durationDays = $action === 'user_ingredient_lock_7' ? 7 : ($lockDays > 0 ? $lockDays : null);
                    $bannedUntil = $durationDays !== null
                        ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
                        : null;
                    $penaltyAction = $durationDays === null ? 'ingredient_post_lock_permanent' : 'ingredient_post_lock_temp';
                    $ok = $penaltyModel->create(
                        $targetUserId,
                        $adminId > 0 ? $adminId : null,
                        'ingredient',
                        $targetId,
                        $penaltyAction,
                        $actionReason !== '' ? $actionReason : 'Vi pham tieu chuan cong dong.',
                        $durationDays,
                        $bannedUntil
                    );
                } elseif ($action === 'user_ingredient_unlock' && $targetUserId > 0) {
                    /** @var UserPenaltyModel $penaltyModel */
                    $penaltyModel = new UserPenaltyModel();
                    $ok = $penaltyModel->deactivateActiveByUserAndActions($targetUserId, [
                        'ingredient_post_lock_temp',
                        'ingredient_post_lock_permanent',
                    ]);
                } elseif ($action === 'user_ban_account' && $targetUserId > 0) {
                    /** @var UserPenaltyModel $penaltyModel */
                    $penaltyModel = new UserPenaltyModel();
                    /** @var UserModel $userModel */
                    $userModel = new UserModel();
                    $admin = current_admin();
                    $adminId = (int) ($admin['id'] ?? 0);
                    $durationDays = $banDays > 0 ? $banDays : null;
                    $bannedUntil = $durationDays !== null
                        ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
                        : null;
                    $reason = $actionReason !== '' ? $actionReason : 'Vi pham tieu chuan cong dong.';

                    $banned = $userModel->banById($targetUserId, $adminId, $reason, $bannedUntil);
                    if ($banned) {
                        $ok = $penaltyModel->create(
                            $targetUserId,
                            $adminId > 0 ? $adminId : null,
                            'ingredient',
                            $targetId,
                            $durationDays === null ? 'ban_permanent' : 'ban_temp',
                            $reason,
                            $durationDays,
                            $bannedUntil
                        );
                    }
                } elseif ($action === 'user_unban_account' && $targetUserId > 0) {
                    /** @var UserPenaltyModel $penaltyModel */
                    $penaltyModel = new UserPenaltyModel();
                    /** @var UserModel $userModel */
                    $userModel = new UserModel();
                    $ok = $userModel->unbanById($targetUserId);
                    if ($ok) {
                        $penaltyModel->deactivateActiveByUserAndActions($targetUserId, [
                            'ban_temp',
                            'ban_permanent',
                        ]);
                    }
                }
                if ($ok && $reportId > 0) {
                    $ingredientModel->updateReportStatus($reportId, 'resolved');
                }
            }
        } elseif ($kind === 'account' && $targetUserId > 0) {
            /** @var UserSafetyModel $userSafetyModel */
            $userSafetyModel = new UserSafetyModel();
            if ($action === 'user_ban_account') {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                /** @var UserModel $userModel */
                $userModel = new UserModel();
                $admin = current_admin();
                $adminId = (int) ($admin['id'] ?? 0);
                $durationDays = $banDays > 0 ? $banDays : null;
                $bannedUntil = $durationDays !== null
                    ? date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days'))
                    : null;
                $reason = $actionReason !== '' ? $actionReason : 'Vi pham tieu chuan cong dong.';

                $banned = $userModel->banById($targetUserId, $adminId, $reason, $bannedUntil);
                if ($banned) {
                    $ok = $penaltyModel->create(
                        $targetUserId,
                        $adminId > 0 ? $adminId : null,
                        'account',
                        $targetUserId,
                        $durationDays === null ? 'ban_permanent' : 'ban_temp',
                        $reason,
                        $durationDays,
                        $bannedUntil
                    );
                }
            } elseif ($action === 'user_unban_account') {
                /** @var UserPenaltyModel $penaltyModel */
                $penaltyModel = new UserPenaltyModel();
                /** @var UserModel $userModel */
                $userModel = new UserModel();
                $ok = $userModel->unbanById($targetUserId);
                if ($ok) {
                    $penaltyModel->deactivateActiveByUserAndActions($targetUserId, ['ban_temp', 'ban_permanent']);
                }
            }
            if ($ok && $reportId > 0) {
                $userSafetyModel->updateUserReportStatus($reportId, 'resolved');
            }
        }


        return $ok;
    }

    public function buildManageLogsData(array $query): array
    {
        require_once APPROOT . '/app/models/SystemLogModel.php';

        $filters = [
            'event_type' => trim((string) ($query['event_type'] ?? '')),
            'result' => trim((string) ($query['result'] ?? '')),
            'action_key' => trim((string) ($query['action_key'] ?? '')),
            'actor_id' => (int) ($query['actor_id'] ?? 0),
            'from' => trim((string) ($query['from'] ?? '')),
            'to' => trim((string) ($query['to'] ?? '')),
            'q' => trim((string) ($query['q'] ?? '')),
        ];

        if (!in_array($filters['event_type'], ['', 'auth', 'user_action', 'content_action', 'admin_action'], true)) {
            $filters['event_type'] = '';
        }
        if (!in_array($filters['result'], ['', 'success', 'failed', 'blocked'], true)) {
            $filters['result'] = '';
        }
        if ($filters['actor_id'] < 0) {
            $filters['actor_id'] = 0;
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $logModel = new SystemLogModel();
        $total = $logModel->countFiltered($filters);
        $rows = $logModel->listFiltered($perPage, $offset, $filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'rows' => $rows,
            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
        ];
    }

    public function buildManageMealPlansData(array $query): array
    {
        require_once APPROOT . '/app/models/MealPlanModel.php';

        $keyword = trim((string) ($query['q'] ?? ''));
        $userId = max(0, (int) ($query['user_id'] ?? 0));
        $fromDate = trim((string) ($query['from'] ?? ''));
        $toDate = trim((string) ($query['to'] ?? ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            $fromDate = '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $toDate = '';
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $mealPlanModel = new MealPlanModel();
        $total = $mealPlanModel->countForAdmin(
            $keyword !== '' ? $keyword : null,
            $userId > 0 ? $userId : null,
            $fromDate !== '' ? $fromDate : null,
            $toDate !== '' ? $toDate : null
        );

        $rows = $mealPlanModel->listForAdmin(
            $perPage,
            $offset,
            $keyword !== '' ? $keyword : null,
            $userId > 0 ? $userId : null,
            $fromDate !== '' ? $fromDate : null,
            $toDate !== '' ? $toDate : null
        );

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'rows' => $rows,
            'keyword' => $keyword,
            'userId' => $userId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
        ];
    }
    public function deleteMealPlanForAdmin(int $mealPlanId): bool
    {
        if ($mealPlanId <= 0) {
            return false;
        }

        require_once APPROOT . '/app/models/MealPlanModel.php';

        $mealPlanModel = new MealPlanModel();
        $ok = $mealPlanModel->deleteForAdmin($mealPlanId);
        if (!$ok) {
            return false;
        }

        $admin = current_admin();
        $adminId = (int) ($admin['id'] ?? 0);
        system_log_write(
            'admin_action',
            'admin.mealplan.delete',
            'success',
            null,
            'meal_plan',
            $mealPlanId,
            null,
            $adminId > 0 ? $adminId : null,
            (string) ($admin['role'] ?? 'admin')
        );

        return true;
    }
}


