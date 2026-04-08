<?php

declare(strict_types=1);

class NotificationAdminService
{
    public function buildManageNotificationsData(array $query): array
    {
        /** @var AdminNotificationCampaignModel $campaignModel */
        $campaignModel = $this->model('AdminNotificationCampaignModel');
        $campaigns = $campaignModel->recent(30);

        return [
            'campaigns' => $campaigns,
            'notice' => (string) ($query['notice'] ?? ''),
        ];
    }

    public function sendSystemNotification(array $post, array $admin): string
    {
        $title = trim((string) ($post['title'] ?? ''));
        $message = trim((string) ($post['message'] ?? ''));
        $actionUrlRaw = trim((string) ($post['action_url'] ?? ''));
        $scope = (string) ($post['scope'] ?? 'all');
        $role = trim((string) ($post['role'] ?? ''));
        $userListRaw = trim((string) ($post['user_list'] ?? ''));

        if ($title === '' || $message === '') {
            return 'invalid_payload';
        }

        if (!in_array($scope, ['all', 'role', 'users'], true)) {
            $scope = 'all';
        }

        if ($scope === 'role' && !in_array($role, ['user', 'mod', 'support', 'super_admin'], true)) {
            return 'invalid_scope';
        }

        $actionUrl = null;
        if ($actionUrlRaw !== '') {
            $actionUrl = str_starts_with($actionUrlRaw, '/') ? $actionUrlRaw : ('/' . ltrim($actionUrlRaw, '/'));
        }

        $targetIds = $this->resolveTargetUserIds($scope, $role, $userListRaw);
        if ($targetIds === []) {
            return 'no_recipients';
        }

        /** @var NotificationModel $notificationModel */
        $notificationModel = $this->model('NotificationModel');
        $sentCount = 0;
        foreach ($targetIds as $uid) {
            if ($notificationModel->create($uid, 'system_announcement', $title . ': ' . $message, $actionUrl)) {
                $sentCount++;
            }
        }

        $targetValue = null;
        if ($scope === 'role') {
            $targetValue = $role;
        } elseif ($scope === 'users') {
            $targetValue = $userListRaw;
        }

        /** @var AdminNotificationCampaignModel $campaignModel */
        $campaignModel = $this->model('AdminNotificationCampaignModel');
        $campaignModel->create(
            (int) ($admin['id'] ?? 0) ?: null,
            $title,
            $message,
            $actionUrl,
            $scope,
            $targetValue,
            $sentCount
        );

        $adminId = (int) ($admin['id'] ?? 0);
        system_log_write('admin_action', 'admin.notification.send', 'success', null, 'notification_campaign', null, [
            'scope' => $scope,
            'role' => $role !== '' ? $role : null,
            'recipient_count' => $sentCount,
            'title' => $title,
        ], $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));

        return 'sent';
    }

    private function resolveTargetUserIds(string $scope, string $role, string $userListRaw): array
    {
        $db = Database::getInstance();
        $targetIds = [];

        if ($scope === 'all') {
            $db->query("SELECT id FROM users WHERE status = 'active' AND deleted_at IS NULL")->execute();
            $targetIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $db->resultSet());
        } elseif ($scope === 'role') {
            $db->query("SELECT id
                        FROM users
                        WHERE status = 'active'
                          AND deleted_at IS NULL
                          AND role = :role")
                ->bind(':role', $role)
                ->execute();
            $targetIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $db->resultSet());
        } else {
            $emails = array_values(array_unique(array_filter(array_map('trim', explode(',', $userListRaw)))));
            if ($emails !== []) {
                $ph = [];
                foreach ($emails as $idx => $email) {
                    $ph[] = ':e' . $idx;
                }
                $sql = "SELECT id
                        FROM users
                        WHERE status = 'active'
                          AND deleted_at IS NULL
                          AND email IN (" . implode(',', $ph) . ')';
                $q = $db->query($sql);
                foreach ($emails as $idx => $email) {
                    $q->bind(':e' . $idx, $email);
                }
                $q->execute();
                $targetIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $q->resultSet());
            }
        }

        return array_values(array_filter(array_unique($targetIds), static fn(int $id): bool => $id > 0));
    }

    private function model(string $model): object
    {
        $modelPath = APPROOT . '/app/models/' . $model . '.php';
        if (!file_exists($modelPath)) {
            throw new RuntimeException('Không tìm thấy model: ' . $model);
        }

        require_once $modelPath;
        return new $model();
    }
}