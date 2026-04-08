<?php

declare(strict_types=1);

class UserSafetyModel extends Model
{
    private bool $tablesReady = false;

    private function ensureTables(): void
    {
        if ($this->tablesReady) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reporter_id INT NOT NULL,
                target_type VARCHAR(20) NOT NULL,
                target_id INT NOT NULL,
                reason TEXT NOT NULL,
                details TEXT NULL,
                status ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_reports_target (target_type, target_id),
                INDEX idx_reports_status (status)
            )")->execute();
        $this->db->query("SHOW COLUMNS FROM reports LIKE 'target_type'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE reports ADD COLUMN target_type VARCHAR(20) NULL AFTER reporter_id")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM reports LIKE 'target_id'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE reports ADD COLUMN target_id INT NULL AFTER target_type")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM reports LIKE 'details'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE reports ADD COLUMN details TEXT NULL AFTER reason")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM reports LIKE 'updated_at'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE reports ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at")
                ->execute();
        }
        $this->db->query("SHOW COLUMNS FROM reports LIKE 'recipe_id'")->execute();
        $recipeIdColumn = $this->db->single();
        if (is_array($recipeIdColumn)) {
            $this->db->query("SELECT CONSTRAINT_NAME
                              FROM information_schema.KEY_COLUMN_USAGE
                              WHERE TABLE_SCHEMA = DATABASE()
                                AND TABLE_NAME = 'reports'
                                AND COLUMN_NAME = 'recipe_id'
                                AND REFERENCED_TABLE_NAME = 'recipes'
                              LIMIT 1")
                ->execute();
            $fk = $this->db->single();
            $fkName = is_array($fk) ? (string) ($fk['CONSTRAINT_NAME'] ?? '') : '';
            if ($fkName !== '' && preg_match('/^[A-Za-z0-9_]+$/', $fkName) === 1) {
                $this->db->query("ALTER TABLE reports DROP FOREIGN KEY `{$fkName}`")->execute();
            }
            if ((string) ($recipeIdColumn['Null'] ?? '') === 'NO') {
                $this->db->query("ALTER TABLE reports MODIFY COLUMN recipe_id INT NULL")->execute();
            }
        }
        $this->db->query("SHOW COLUMNS FROM reports LIKE 'recipe_id'")->execute();
        if ($this->db->single()) {
            $this->db->query("UPDATE reports
                              SET target_type = COALESCE(NULLIF(target_type, ''), 'recipe'),
                                  target_id = COALESCE(target_id, recipe_id)
                              WHERE recipe_id IS NOT NULL")
                ->execute();
        }
        $this->db->query("SHOW INDEX FROM reports WHERE Key_name = 'uq_reports_once_target'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE reports
                              ADD UNIQUE KEY uq_reports_once_target (reporter_id, target_type, target_id)")
                ->execute();
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS user_blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                blocker_id INT NOT NULL,
                blocked_user_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_user_blocks_blocker FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_user_blocks_blocked_user FOREIGN KEY (blocked_user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY uq_user_blocks_once (blocker_id, blocked_user_id),
                INDEX idx_user_blocks_blocked_user (blocked_user_id)
            )")->execute();

        $this->tablesReady = true;
    }

    public function hasBlocked(int $blockerId, int $blockedUserId): bool
    {
        $this->ensureTables();
        $this->db->query('SELECT 1 FROM user_blocks WHERE blocker_id = :blocker_id AND blocked_user_id = :blocked_user_id LIMIT 1')
            ->bind(':blocker_id', $blockerId)
            ->bind(':blocked_user_id', $blockedUserId)
            ->execute();

        return (bool) $this->db->single();
    }

    public function isAnyBlockBetween(int $userA, int $userB): bool
    {
        if ($userA <= 0 || $userB <= 0) {
            return false;
        }
        return $this->hasBlocked($userA, $userB) || $this->hasBlocked($userB, $userA);
    }

    public function blockUser(int $blockerId, int $blockedUserId): bool
    {
        $this->ensureTables();
        if ($blockerId <= 0 || $blockedUserId <= 0 || $blockerId === $blockedUserId) {
            return false;
        }

        return $this->db
            ->query('INSERT IGNORE INTO user_blocks (blocker_id, blocked_user_id, created_at)
                     VALUES (:blocker_id, :blocked_user_id, NOW())')
            ->bind(':blocker_id', $blockerId)
            ->bind(':blocked_user_id', $blockedUserId)
            ->execute();
    }

    public function unblockUser(int $blockerId, int $blockedUserId): bool
    {
        $this->ensureTables();
        if ($blockerId <= 0 || $blockedUserId <= 0 || $blockerId === $blockedUserId) {
            return false;
        }

        $ok = $this->db
            ->query('DELETE FROM user_blocks WHERE blocker_id = :blocker_id AND blocked_user_id = :blocked_user_id')
            ->bind(':blocker_id', $blockerId)
            ->bind(':blocked_user_id', $blockedUserId)
            ->execute();

        return $ok && $this->db->rowCount() > 0;
    }

    public function reportUser(int $reporterId, int $reportedUserId, string $reason, ?string $details = null): bool
    {
        $this->ensureTables();
        if ($reporterId <= 0 || $reportedUserId <= 0 || $reporterId === $reportedUserId) {
            return false;
        }

        $reason = trim($reason);
        if ($reason === '') {
            return false;
        }
        if ($this->hasReportedUser($reporterId, $reportedUserId)) {
            return false;
        }

        return $this->db
            ->query("INSERT INTO reports (reporter_id, target_type, target_id, reason, details, status, created_at)
                     VALUES (:reporter_id, 'user', :reported_user_id, :reason, :details, :status, NOW())")
            ->bind(':reporter_id', $reporterId)
            ->bind(':reported_user_id', $reportedUserId)
            ->bind(':reason', mb_substr($reason, 0, 255))
            ->bind(':details', $details !== null && $details !== '' ? mb_substr(trim($details), 0, 1000) : null)
            ->bind(':status', 'pending')
            ->execute();
    }

    public function hasReportedUser(int $reporterId, int $reportedUserId): bool
    {
        $this->ensureTables();
        $this->db->query("SELECT 1 FROM reports
                          WHERE reporter_id = :reporter_id
                            AND target_type = 'user'
                            AND target_id = :reported_user_id
                          LIMIT 1")
            ->bind(':reporter_id', $reporterId)
            ->bind(':reported_user_id', $reportedUserId)
            ->execute();

        return (bool) $this->db->single();
    }

    public function allUserReportsForAdmin(?string $status = null): array
    {
        $this->ensureTables();
        $sql = "SELECT ur.id,
                       ur.reporter_id,
                       ur.target_id AS reported_user_id,
                       ur.reason,
                       ur.details,
                       ur.status,
                       ur.created_at,
                       reporter.name AS reporter_name,
                       reported.name AS reported_name,
                       reported.email AS reported_email
                FROM reports ur
                LEFT JOIN users reporter ON reporter.id = ur.reporter_id
                LEFT JOIN users reported ON reported.id = ur.target_id
                WHERE ur.target_type = 'user'";
        if ($status !== null && $status !== '') {
            $sql .= ' AND ur.status = :status';
        }
        $sql .= ' ORDER BY ur.id DESC';

        $query = $this->db->query($sql);
        if ($status !== null && $status !== '') {
            $query->bind(':status', $status);
        }
        $query->execute();
        return $query->resultSet();
    }

    public function updateUserReportStatus(int $reportId, string $status): bool
    {
        $this->ensureTables();
        if (!in_array($status, ['pending', 'reviewed', 'resolved'], true)) {
            return false;
        }
        return $this->db
            ->query("UPDATE reports SET status = :status WHERE id = :id AND target_type = 'user'")
            ->bind(':status', $status)
            ->bind(':id', $reportId)
            ->execute();
    }
}
