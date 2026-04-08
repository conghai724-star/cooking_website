<?php

declare(strict_types=1);

class UserPenaltyModel extends Model
{
    private bool $ready = false;

    private function ensureTable(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS user_penalties (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                admin_id INT NULL,
                source_type ENUM(\'comment\', \'recipe\', \'tip\', \'ingredient\', \'account\') NOT NULL DEFAULT \'account\',
                source_id INT NULL,
                action ENUM(\'warn\', \'comment_lock_temp\', \'comment_lock_permanent\', \'recipe_post_lock_temp\', \'recipe_post_lock_permanent\', \'tip_post_lock_temp\', \'tip_post_lock_permanent\', \'ingredient_post_lock_temp\', \'ingredient_post_lock_permanent\', \'follow_lock_temp\', \'follow_lock_permanent\', \'ban_temp\', \'ban_permanent\') NOT NULL,
                reason TEXT NULL,
                duration_days INT NULL,
                banned_until DATETIME NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_user_penalties_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_user_penalties_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_user_penalties_user (user_id),
                INDEX idx_user_penalties_action (action),
                INDEX idx_user_penalties_created_at (created_at)
            )')->execute();

        // Ensure enum includes latest actions for existing tables.
        $this->db->query("ALTER TABLE user_penalties
                          MODIFY action ENUM('warn','comment_lock_temp','comment_lock_permanent','recipe_post_lock_temp','recipe_post_lock_permanent','tip_post_lock_temp','tip_post_lock_permanent','ingredient_post_lock_temp','ingredient_post_lock_permanent','follow_lock_temp','follow_lock_permanent','ban_temp','ban_permanent') NOT NULL")
            ->execute();
        $this->db->query("ALTER TABLE user_penalties
                          ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1")
            ->execute();

        $this->ready = true;
    }

    public function create(
        int $userId,
        ?int $adminId,
        string $sourceType,
        ?int $sourceId,
        string $action,
        ?string $reason = null,
        ?int $durationDays = null,
        ?string $bannedUntil = null
    ): bool {
        $this->ensureTable();
        return $this->db
            ->query('INSERT INTO user_penalties
                     (user_id, admin_id, source_type, source_id, action, reason, duration_days, banned_until, created_at)
                     VALUES
                     (:user_id, :admin_id, :source_type, :source_id, :action, :reason, :duration_days, :banned_until, NOW())')
            ->bind(':user_id', $userId)
            ->bind(':admin_id', $adminId)
            ->bind(':source_type', $sourceType)
            ->bind(':source_id', $sourceId)
            ->bind(':action', $action)
            ->bind(':reason', $reason)
            ->bind(':duration_days', $durationDays)
            ->bind(':banned_until', $bannedUntil)
            ->execute();
    }

    public function getActiveCommentLock(int $userId): ?array
    {
        $this->ensureTable();
        $this->expireTemporaryPenalties();
        $this->db->query("SELECT action, reason, banned_until
                          FROM user_penalties
                          WHERE user_id = :user_id
                            AND is_active = 1
                            AND action IN ('comment_lock_temp', 'comment_lock_permanent')
                            AND (
                                action = 'comment_lock_permanent'
                                OR (banned_until IS NOT NULL AND banned_until > NOW())
                            )
                          ORDER BY created_at DESC
                          LIMIT 1")
            ->bind(':user_id', $userId)
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function getActiveRecipePostLock(int $userId): ?array
    {
        $this->ensureTable();
        $this->expireTemporaryPenalties();
        $this->db->query("SELECT action, reason, banned_until
                          FROM user_penalties
                          WHERE user_id = :user_id
                            AND is_active = 1
                            AND action IN ('recipe_post_lock_temp', 'recipe_post_lock_permanent')
                            AND (
                                action = 'recipe_post_lock_permanent'
                                OR (banned_until IS NOT NULL AND banned_until > NOW())
                            )
                          ORDER BY created_at DESC
                          LIMIT 1")
            ->bind(':user_id', $userId)
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function getActiveTipPostLock(int $userId): ?array
    {
        $this->ensureTable();
        $this->expireTemporaryPenalties();
        $this->db->query("SELECT action, reason, banned_until
                          FROM user_penalties
                          WHERE user_id = :user_id
                            AND is_active = 1
                            AND action IN ('tip_post_lock_temp', 'tip_post_lock_permanent')
                            AND (
                                action = 'tip_post_lock_permanent'
                                OR (banned_until IS NOT NULL AND banned_until > NOW())
                            )
                          ORDER BY created_at DESC
                          LIMIT 1")
            ->bind(':user_id', $userId)
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function getActiveIngredientPostLock(int $userId): ?array
    {
        $this->ensureTable();
        $this->expireTemporaryPenalties();
        $this->db->query("SELECT action, reason, banned_until
                          FROM user_penalties
                          WHERE user_id = :user_id
                            AND is_active = 1
                            AND action IN ('ingredient_post_lock_temp', 'ingredient_post_lock_permanent')
                            AND (
                                action = 'ingredient_post_lock_permanent'
                                OR (banned_until IS NOT NULL AND banned_until > NOW())
                            )
                          ORDER BY created_at DESC
                          LIMIT 1")
            ->bind(':user_id', $userId)
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function getActiveFollowLock(int $userId): ?array
    {
        $this->ensureTable();
        $this->expireTemporaryPenalties();
        $this->db->query("SELECT action, reason, banned_until
                          FROM user_penalties
                          WHERE user_id = :user_id
                            AND is_active = 1
                            AND action IN ('follow_lock_temp', 'follow_lock_permanent')
                            AND (
                                action = 'follow_lock_permanent'
                                OR (banned_until IS NOT NULL AND banned_until > NOW())
                            )
                          ORDER BY created_at DESC
                          LIMIT 1")
            ->bind(':user_id', $userId)
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function mapActiveFollowLocks(array $userIds): array
    {
        $this->ensureTable();
        $this->expireTemporaryPenalties();

        $userIds = array_values(array_unique(array_map(static fn($id): int => (int) $id, $userIds)));
        $userIds = array_values(array_filter($userIds, static fn(int $id): bool => $id > 0));
        if ($userIds === []) {
            return [];
        }

        $placeholders = [];
        foreach ($userIds as $i => $id) {
            $placeholders[] = ':uid_' . $i;
        }

        $sql = "SELECT up.user_id, up.action, up.reason, up.banned_until
                FROM user_penalties up
                INNER JOIN (
                    SELECT user_id, MAX(id) AS max_id
                    FROM user_penalties
                    WHERE is_active = 1
                      AND action IN ('follow_lock_temp', 'follow_lock_permanent')
                      AND (
                          action = 'follow_lock_permanent'
                          OR (banned_until IS NOT NULL AND banned_until > NOW())
                      )
                      AND user_id IN (" . implode(',', $placeholders) . ")
                    GROUP BY user_id
                ) latest ON latest.max_id = up.id";
        $query = $this->db->query($sql);
        foreach ($userIds as $i => $id) {
            $query->bind(':uid_' . $i, $id);
        }
        $query->execute();

        $map = [];
        foreach ($query->resultSet() as $row) {
            $uid = (int) ($row['user_id'] ?? 0);
            if ($uid > 0) {
                $map[$uid] = $row;
            }
        }
        return $map;
    }

    public function deactivateById(int $id): bool
    {
        $this->ensureTable();
        return $this->db
            ->query('UPDATE user_penalties SET is_active = 0 WHERE id = :id')
            ->bind(':id', $id)
            ->execute();
    }

    public function deactivateActiveByUserAndActions(int $userId, array $actions): bool
    {
        $this->ensureTable();
        if ($userId <= 0 || $actions === []) {
            return false;
        }

        $allowed = [
            'warn',
            'comment_lock_temp',
            'comment_lock_permanent',
            'recipe_post_lock_temp',
            'recipe_post_lock_permanent',
            'tip_post_lock_temp',
            'tip_post_lock_permanent',
            'ingredient_post_lock_temp',
            'ingredient_post_lock_permanent',
            'follow_lock_temp',
            'follow_lock_permanent',
            'ban_temp',
            'ban_permanent',
        ];
        $actions = array_values(array_filter($actions, static fn(string $a): bool => in_array($a, $allowed, true)));
        if ($actions === []) {
            return false;
        }

        $placeholders = [];
        foreach ($actions as $idx => $action) {
            $placeholders[] = ':a' . $idx;
        }

        $sql = 'UPDATE user_penalties
                SET is_active = 0
                WHERE user_id = :user_id
                  AND is_active = 1
                  AND action IN (' . implode(',', $placeholders) . ')';
        $query = $this->db->query($sql)->bind(':user_id', $userId);
        foreach ($actions as $idx => $action) {
            $query->bind(':a' . $idx, $action);
        }
        return $query->execute();
    }

    public function listActiveBans(?string $keyword = null): array
    {
        $this->ensureTable();
        $this->expireTemporaryPenalties();

        $sql = "SELECT up.id,
                       up.user_id,
                       u.name AS user_name,
                       u.email AS user_email,
                       up.reason,
                       up.action AS ban_type,
                       up.created_at AS started_at,
                       up.banned_until AS expires_at,
                       CASE
                           WHEN up.is_active = 0 THEN 'released'
                           WHEN up.banned_until IS NULL THEN 'active'
                           WHEN up.banned_until > NOW() THEN 'active'
                           ELSE 'expired'
                       END AS status,
                       'penalty' AS source
                FROM user_penalties up
                INNER JOIN users u ON u.id = up.user_id
                WHERE up.is_active = 1
                  AND up.action IN ('comment_lock_temp', 'comment_lock_permanent', 'recipe_post_lock_temp', 'recipe_post_lock_permanent', 'tip_post_lock_temp', 'tip_post_lock_permanent', 'ingredient_post_lock_temp', 'ingredient_post_lock_permanent', 'follow_lock_temp', 'follow_lock_permanent', 'ban_temp', 'ban_permanent')";

        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= " AND (u.name LIKE :kw_name OR u.email LIKE :kw_email OR up.reason LIKE :kw_reason)";
        }
        $sql .= ' ORDER BY up.created_at DESC, up.id DESC';

        $query = $this->db->query($sql);
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $query->bind(':kw_name', $like)
                ->bind(':kw_email', $like)
                ->bind(':kw_reason', $like);
        }
        $query->execute();
        return $query->resultSet();
    }

    public function listActiveAppealableByUserId(int $userId): array
    {
        $this->ensureTable();
        $this->expireTemporaryPenalties();
        if ($userId <= 0) {
            return [];
        }

        $this->db->query("SELECT id, action, reason, banned_until, created_at
                          FROM user_penalties
                          WHERE user_id = :user_id
                            AND is_active = 1
                            AND action IN (
                                'comment_lock_temp', 'comment_lock_permanent',
                                'recipe_post_lock_temp', 'recipe_post_lock_permanent',
                                'tip_post_lock_temp', 'tip_post_lock_permanent',
                                'ingredient_post_lock_temp', 'ingredient_post_lock_permanent',
                                'follow_lock_temp', 'follow_lock_permanent',
                                'ban_temp', 'ban_permanent'
                            )
                          ORDER BY created_at DESC, id DESC")
            ->bind(':user_id', $userId)
            ->execute();
        return $this->db->resultSet();
    }

    private function expireTemporaryPenalties(): void
    {
        $this->db->query("UPDATE user_penalties
                          SET is_active = 0
                          WHERE is_active = 1
                            AND action IN ('comment_lock_temp', 'recipe_post_lock_temp', 'tip_post_lock_temp', 'ingredient_post_lock_temp', 'follow_lock_temp', 'ban_temp')
                            AND banned_until IS NOT NULL
                            AND banned_until <= NOW()")
            ->execute();
    }
}
