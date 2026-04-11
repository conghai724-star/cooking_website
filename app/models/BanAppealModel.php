<?php

declare(strict_types=1);

final class BanAppealModel extends Model
{
    private bool $ready = false;

    private function ensureTable(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS ban_appeals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                target_type ENUM('user_ban', 'user_penalty') NOT NULL,
                target_id INT NOT NULL,
                appeal_reason TEXT NOT NULL,
                evidence_text TEXT NULL,
                status ENUM('pending', 'reviewing', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                admin_note TEXT NULL,
                reviewed_by INT NULL,
                reviewed_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_ban_appeals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_ban_appeals_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_ban_appeals_user_target_status (user_id, target_type, target_id, status),
                INDEX idx_ban_appeals_status_created (status, created_at),
                INDEX idx_ban_appeals_target (target_type, target_id)
            )")->execute();

        $this->ready = true;
    }

    public function create(
        int $userId,
        string $targetType,
        int $targetId,
        string $appealReason,
        ?string $evidenceText = null
    ): bool {
        $this->ensureTable();

        return $this->db
            ->query('INSERT INTO ban_appeals
                    (user_id, target_type, target_id, appeal_reason, evidence_text, status, created_at, updated_at)
                    VALUES
                    (:user_id, :target_type, :target_id, :appeal_reason, :evidence_text, :status, NOW(), NOW())')
            ->bind(':user_id', $userId)
            ->bind(':target_type', $targetType)
            ->bind(':target_id', $targetId)
            ->bind(':appeal_reason', $appealReason)
            ->bind(':evidence_text', $evidenceText)
            ->bind(':status', 'pending')
            ->execute();
    }

    public function hasPendingAppeal(int $userId, string $targetType, int $targetId): bool
    {
        $this->ensureTable();
        $this->db->query('SELECT 1
                          FROM ban_appeals
                          WHERE user_id = :user_id
                            AND target_type = :target_type
                            AND target_id = :target_id
                            AND status IN (\'pending\', \'reviewing\')
                          LIMIT 1')
            ->bind(':user_id', $userId)
            ->bind(':target_type', $targetType)
            ->bind(':target_id', $targetId)
            ->execute();
        return (bool) $this->db->single();
    }

    public function listByUser(int $userId): array
    {
        $this->ensureTable();
        $this->db->query("SELECT ba.*,
                                 reviewer.name AS reviewer_name
                          FROM ban_appeals ba
                          LEFT JOIN users reviewer ON reviewer.id = ba.reviewed_by
                          WHERE ba.user_id = :user_id
                          ORDER BY ba.created_at DESC, ba.id DESC")
            ->bind(':user_id', $userId)
            ->execute();
        return $this->db->resultSet();
    }

    public function listForAdmin(?string $status = null, ?string $keyword = null): array
    {
        $this->ensureTable();
        $sql = "SELECT ba.*,
                       u.name AS user_name,
                       u.email AS user_email,
                       reviewer.name AS reviewer_name
                FROM ban_appeals ba
                INNER JOIN users u ON u.id = ba.user_id
                LEFT JOIN users reviewer ON reviewer.id = ba.reviewed_by
                WHERE 1 = 1";

        if ($status !== null && $status !== '') {
            $sql .= ' AND ba.status = :status';
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= ' AND (u.name LIKE :kw_name OR u.email LIKE :kw_email OR ba.appeal_reason LIKE :kw_reason)';
        }
        $sql .= ' ORDER BY ba.created_at DESC, ba.id DESC';

        $query = $this->db->query($sql);
        if ($status !== null && $status !== '') {
            $query->bind(':status', $status);
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $query->bind(':kw_name', $like)
                ->bind(':kw_email', $like)
                ->bind(':kw_reason', $like);
        }
        $query->execute();
        return $query->resultSet();
    }

    public function countForAdmin(?string $status = null, ?string $keyword = null): int
    {
        $this->ensureTable();

        $sql = 'SELECT COUNT(*) AS total
                FROM ban_appeals ba
                INNER JOIN users u ON u.id = ba.user_id
                WHERE 1 = 1';

        if ($status !== null && $status !== '') {
            $sql .= ' AND ba.status = :status';
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= ' AND (u.name LIKE :kw_name OR u.email LIKE :kw_email OR ba.appeal_reason LIKE :kw_reason)';
        }

        $query = $this->db->query($sql);
        if ($status !== null && $status !== '') {
            $query->bind(':status', $status);
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $query->bind(':kw_name', $like)
                ->bind(':kw_email', $like)
                ->bind(':kw_reason', $like);
        }
        $query->execute();
        $row = $query->single();
        return (int) ($row['total'] ?? 0);
    }

    public function listForAdminPaged(?string $status = null, ?string $keyword = null, int $limit = 20, int $offset = 0): array
    {
        $this->ensureTable();
        $sql = "SELECT ba.*,
                       u.name AS user_name,
                       u.email AS user_email,
                       reviewer.name AS reviewer_name
                FROM ban_appeals ba
                INNER JOIN users u ON u.id = ba.user_id
                LEFT JOIN users reviewer ON reviewer.id = ba.reviewed_by
                WHERE 1 = 1";

        if ($status !== null && $status !== '') {
            $sql .= ' AND ba.status = :status';
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= ' AND (u.name LIKE :kw_name OR u.email LIKE :kw_email OR ba.appeal_reason LIKE :kw_reason)';
        }
        $sql .= ' ORDER BY ba.created_at DESC, ba.id DESC LIMIT :limit OFFSET :offset';

        $query = $this->db->query($sql);
        if ($status !== null && $status !== '') {
            $query->bind(':status', $status);
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $query->bind(':kw_name', $like)
                ->bind(':kw_email', $like)
                ->bind(':kw_reason', $like);
        }
        $query->bind(':limit', $limit, PDO::PARAM_INT);
        $query->bind(':offset', $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->resultSet();
    }

    public function findById(int $id): array|false
    {
        $this->ensureTable();
        $this->db->query('SELECT * FROM ban_appeals WHERE id = :id LIMIT 1')
            ->bind(':id', $id)
            ->execute();
        return $this->db->single();
    }

    public function setStatus(int $id, string $status, int $adminId, ?string $adminNote = null): bool
    {
        $this->ensureTable();
        return $this->db
            ->query('UPDATE ban_appeals
                     SET status = :status,
                         admin_note = :admin_note,
                         reviewed_by = :reviewed_by,
                         reviewed_at = NOW(),
                         updated_at = NOW()
                     WHERE id = :id')
            ->bind(':status', $status)
            ->bind(':admin_note', $adminNote)
            ->bind(':reviewed_by', $adminId > 0 ? $adminId : null)
            ->bind(':id', $id)
            ->execute();
    }
}
