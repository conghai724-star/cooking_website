<?php

declare(strict_types=1);

class NotificationModel extends Model
{
    private bool $ready = false;

    private function ensureTable(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                action_url VARCHAR(255) NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_notifications_user_read (user_id, is_read)
            )")->execute();
        $this->db->query("SHOW COLUMNS FROM notifications LIKE 'action_url'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE notifications ADD COLUMN action_url VARCHAR(255) NULL AFTER message")
                ->execute();
        }

        $this->ready = true;
    }

    public function create(int $userId, string $type, string $message, ?string $actionUrl = null): bool
    {
        $this->ensureTable();
        if ($userId <= 0 || trim($type) === '' || trim($message) === '') {
            return false;
        }

        return $this->db
            ->query('INSERT INTO notifications (user_id, type, message, action_url, is_read, created_at)
                     VALUES (:user_id, :type, :message, :action_url, 0, NOW())')
            ->bind(':user_id', $userId)
            ->bind(':type', mb_substr($type, 0, 50))
            ->bind(':message', mb_substr($message, 0, 2000))
            ->bind(':action_url', $actionUrl !== null ? mb_substr($actionUrl, 0, 255) : null)
            ->execute();
    }

    public function createForAdmins(string $type, string $message, ?string $actionUrl = null): int
    {
        $this->ensureTable();

        $this->db->query("SELECT id FROM users
                          WHERE role IN ('super_admin', 'mod', 'support')
                            AND status = 'active'
                            AND deleted_at IS NULL")
            ->execute();
        $admins = $this->db->resultSet();
        if ($admins === []) {
            return 0;
        }

        $count = 0;
        foreach ($admins as $admin) {
            $adminId = (int) ($admin['id'] ?? 0);
            if ($adminId > 0 && $this->create($adminId, $type, $message, $actionUrl)) {
                $count++;
            }
        }

        return $count;
    }

    public function recentForUser(int $userId, int $limit = 8): array
    {
        $this->ensureTable();
        if ($userId <= 0) {
            return [];
        }

        $this->db->query('SELECT id, type, message, action_url, is_read, created_at
                          FROM notifications
                          WHERE user_id = :user_id
                          ORDER BY created_at DESC, id DESC
                          LIMIT :limit')
            ->bind(':user_id', $userId)
            ->bind(':limit', max(1, $limit), PDO::PARAM_INT)
            ->execute();
        return $this->db->resultSet();
    }

    public function unreadCountForUser(int $userId): int
    {
        $this->ensureTable();
        if ($userId <= 0) {
            return 0;
        }

        $this->db->query('SELECT COUNT(*) AS total
                          FROM notifications
                          WHERE user_id = :user_id
                            AND is_read = 0')
            ->bind(':user_id', $userId)
            ->execute();
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function findByIdForUser(int $notificationId, int $userId): ?array
    {
        $this->ensureTable();
        if ($notificationId <= 0 || $userId <= 0) {
            return null;
        }

        $this->db->query('SELECT id, user_id, type, message, action_url, is_read, created_at
                          FROM notifications
                          WHERE id = :id
                            AND user_id = :user_id
                          LIMIT 1')
            ->bind(':id', $notificationId)
            ->bind(':user_id', $userId)
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function markReadByIdForUser(int $notificationId, int $userId): bool
    {
        $this->ensureTable();
        if ($notificationId <= 0 || $userId <= 0) {
            return false;
        }

        return $this->db
            ->query('UPDATE notifications
                     SET is_read = 1
                     WHERE id = :id
                       AND user_id = :user_id')
            ->bind(':id', $notificationId)
            ->bind(':user_id', $userId)
            ->execute();
    }
}
