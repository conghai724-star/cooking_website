<?php

declare(strict_types=1);

class PostModel extends Model
{
    private bool $schemaReady = false;
    private bool $reportTableReady = false;

    private function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                image VARCHAR(255) NULL,
                status ENUM(\'approved\', \'hidden\', \'deleted\') NOT NULL DEFAULT \'approved\',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_posts_status_created (status, created_at),
                INDEX idx_posts_user (user_id)
            )')->execute();

        $this->db->query("SHOW COLUMNS FROM posts LIKE 'deleted_at'")->execute();
        if (!$this->db->single()) {
            $this->db->query('ALTER TABLE posts ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at')->execute();
        }

        $this->schemaReady = true;
    }

    private function ensureReportTable(): void
    {
        if ($this->reportTableReady) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reporter_id INT NOT NULL,
                target_type VARCHAR(20) NOT NULL,
                target_id INT NOT NULL,
                reason TEXT NOT NULL,
                details TEXT NULL,
                status ENUM(\'pending\', \'reviewed\', \'resolved\') NOT NULL DEFAULT \'pending\',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_reports_target (target_type, target_id),
                INDEX idx_reports_reporter (reporter_id)
            )')->execute();

        $this->db->query("SHOW INDEX FROM reports WHERE Key_name = 'uq_reports_once_target'")->execute();
        if (!$this->db->single()) {
            $this->db->query("DELETE c1 FROM reports c1
                              INNER JOIN reports c2
                                ON c1.reporter_id = c2.reporter_id
                               AND c1.target_type = c2.target_type
                               AND c1.target_id = c2.target_id
                               AND c1.id > c2.id")
                ->execute();
            $this->db->query("ALTER TABLE reports
                              ADD UNIQUE KEY uq_reports_once_target (reporter_id, target_type, target_id)")
                ->execute();
        }

        $this->reportTableReady = true;
    }

    public function countApproved(?string $keyword = null): int
    {
        $this->ensureSchema();

        $sql = "SELECT COUNT(*) AS total
                FROM posts p
                WHERE p.status = 'approved'
                  AND p.deleted_at IS NULL";

        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= ' AND (p.title LIKE :kw_title OR p.content LIKE :kw_content)';
        }

        $this->db->query($sql);
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $this->db->bind(':kw_title', $like);
            $this->db->bind(':kw_content', $like);
        }
        $this->db->execute();
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function allApprovedPaged(int $limit, int $offset, ?string $keyword = null): array
    {
        $this->ensureSchema();

        $sql = "SELECT p.*,
                       u.name AS author_name,
                       (
                         SELECT COUNT(*)
                         FROM comments c
                         WHERE c.content_type = 'post'
                           AND c.content_id = p.id
                           AND c.status = 'active'
                       ) AS comment_count
                FROM posts p
                LEFT JOIN users u ON u.id = p.user_id
                WHERE p.status = 'approved'
                  AND p.deleted_at IS NULL";

        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= ' AND (p.title LIKE :kw_title OR p.content LIKE :kw_content)';
        }

        $sql .= ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';

        $this->db->query($sql)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->bind(':offset', $offset, PDO::PARAM_INT);
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $this->db->bind(':kw_title', $like);
            $this->db->bind(':kw_content', $like);
        }
        $this->db->execute();
        return $this->db->resultSet();
    }

    public function findById(int $postId): array|false
    {
        $this->ensureSchema();
        $sql = "SELECT p.*,
                       u.name AS author_name
                FROM posts p
                LEFT JOIN users u ON u.id = p.user_id
                WHERE p.id = :id
                LIMIT 1";

        $this->db->query($sql)->bind(':id', $postId)->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : false;
    }

    public function create(int $userId, string $title, string $content, ?string $image = null): int|false
    {
        $this->ensureSchema();
        $ok = $this->db
            ->query("INSERT INTO posts (user_id, title, content, image, status, created_at)
                     VALUES (:user_id, :title, :content, :image, 'approved', NOW())")
            ->bind(':user_id', $userId)
            ->bind(':title', $title)
            ->bind(':content', $content)
            ->bind(':image', $image)
            ->execute();

        if (!$ok) {
            return false;
        }

        return (int) $this->db->lastInsertId();
    }

    public function updateByOwner(int $postId, int $userId, string $title, string $content, ?string $image = null): bool
    {
        $this->ensureSchema();

        return $this->db
            ->query("UPDATE posts
                     SET title = :title,
                         content = :content,
                         image = :image,
                         updated_at = NOW()
                     WHERE id = :id
                       AND user_id = :user_id
                       AND deleted_at IS NULL")
            ->bind(':title', $title)
            ->bind(':content', $content)
            ->bind(':image', $image)
            ->bind(':id', $postId)
            ->bind(':user_id', $userId)
            ->execute();
    }

    public function deleteByOwner(int $postId, int $userId): bool
    {
        $this->ensureSchema();

        return $this->db
            ->query("UPDATE posts
                     SET status = 'deleted',
                         deleted_at = NOW()
                     WHERE id = :id
                       AND user_id = :user_id
                       AND deleted_at IS NULL")
            ->bind(':id', $postId)
            ->bind(':user_id', $userId)
            ->execute();
    }

    public function hasReported(int $reporterId, int $postId): bool
    {
        $this->ensureReportTable();

        $this->db->query("SELECT 1
                          FROM reports
                          WHERE reporter_id = :reporter_id
                            AND target_type = 'post'
                            AND target_id = :post_id
                          LIMIT 1")
            ->bind(':reporter_id', $reporterId)
            ->bind(':post_id', $postId)
            ->execute();

        return (bool) $this->db->single();
    }

    public function saveReport(int $reporterId, int $postId, string $reason, ?string $details = null): bool
    {
        $this->ensureReportTable();

        try {
            return $this->db
                ->query("INSERT INTO reports (reporter_id, target_type, target_id, reason, details, status, created_at)
                         VALUES (:reporter_id, 'post', :post_id, :reason, :details, 'pending', NOW())")
                ->bind(':reporter_id', $reporterId)
                ->bind(':post_id', $postId)
                ->bind(':reason', $reason)
                ->bind(':details', $details)
                ->execute();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }
}
