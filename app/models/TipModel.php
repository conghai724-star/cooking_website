<?php

declare(strict_types=1);

class TipModel extends Model
{
    private ?array $columns = null;
    private bool $reportTableReady = false;
    private bool $saveTableReady = false;

    private function getColumns(): array
    {
        if ($this->columns !== null) {
            return $this->columns;
        }

        $this->db->query('SHOW COLUMNS FROM tips')->execute();
        $rows = $this->db->resultSet();
        $cols = [];
        foreach ($rows as $row) {
            if (isset($row['Field'])) {
                $cols[$row['Field']] = true;
            }
        }
        $this->columns = $cols;
        return $cols;
    }

    private function hasColumn(string $column): bool
    {
        return array_key_exists($column, $this->getColumns());
    }

    public function all(?string $status = null): array
    {
        $sql = 'SELECT * FROM tips';
        $conditions = [];
        if ($status !== null && $this->hasColumn('status')) {
            $conditions[] = 'status = :status';
        }
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY id DESC';

        $this->db->query($sql);
        if ($status !== null && $this->hasColumn('status')) {
            $this->db->bind(':status', $status);
        }
        $this->db->execute();
        return $this->db->resultSet();
    }

    public function countByStatus(?string $status = null, ?string $keyword = null): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM tips';
        $conditions = [];
        if ($status !== null && $this->hasColumn('status')) {
            $conditions[] = 'status = :status';
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(title LIKE :kw_title OR excerpt LIKE :kw_excerpt OR content LIKE :kw_content)';
        }
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $this->db->query($sql);
        if ($status !== null && $this->hasColumn('status')) {
            $this->db->bind(':status', $status);
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $this->db->bind(':kw_title', $like);
            $this->db->bind(':kw_excerpt', $like);
            $this->db->bind(':kw_content', $like);
        }
        $this->db->execute();
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function allPaged(?string $status, int $limit, int $offset, ?string $keyword = null): array
    {
        $sql = 'SELECT * FROM tips';
        $conditions = [];
        if ($status !== null && $this->hasColumn('status')) {
            $conditions[] = 'status = :status';
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(title LIKE :kw_title OR excerpt LIKE :kw_excerpt OR content LIKE :kw_content)';
        }
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY id DESC LIMIT :limit OFFSET :offset';

        $this->db->query($sql);
        if ($status !== null && $this->hasColumn('status')) {
            $this->db->bind(':status', $status);
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $this->db->bind(':kw_title', $like);
            $this->db->bind(':kw_excerpt', $like);
            $this->db->bind(':kw_content', $like);
        }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        $this->db->execute();
        return $this->db->resultSet();
    }

    public function findBySlug(string $slug): array|false
    {
        $sql = 'SELECT * FROM tips WHERE slug = :slug LIMIT 1';
        $this->db->query($sql)->bind(':slug', $slug)->execute();
        return $this->db->single();
    }

    public function findById(int $id): array|false
    {
        $sql = 'SELECT * FROM tips WHERE id = :id LIMIT 1';
        $this->db->query($sql)->bind(':id', $id)->execute();
        return $this->db->single();
    }

    public function byUser(int $userId): array
    {
        $sql = 'SELECT * FROM tips WHERE user_id = :user_id ORDER BY id DESC';
        $this->db->query($sql)
            ->bind(':user_id', $userId)
            ->execute();
        return $this->db->resultSet();
    }

    public function slugExists(string $slug): bool
    {
        $this->db->query('SELECT 1 FROM tips WHERE slug = :slug LIMIT 1')
            ->bind(':slug', $slug)
            ->execute();
        return (bool) $this->db->single();
    }

    public function create(
        int $userId,
        string $title,
        string $slug,
        ?string $excerpt,
        string $content,
        ?string $coverImage,
        string $authorName,
        string $status
    ): int|false {
        $columns = ['title', 'slug', 'excerpt', 'content', 'cover_image', 'author_name', 'status', 'created_at'];
        $values = [':title', ':slug', ':excerpt', ':content', ':cover_image', ':author_name', ':status', 'NOW()'];

        if ($this->hasColumn('user_id')) {
            array_unshift($columns, 'user_id');
            array_unshift($values, ':user_id');
        }

        $sql = 'INSERT INTO tips (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';

        $this->db->query($sql)
            ->bind(':title', $title)
            ->bind(':slug', $slug)
            ->bind(':excerpt', $excerpt)
            ->bind(':content', $content)
            ->bind(':cover_image', $coverImage)
            ->bind(':author_name', $authorName)
            ->bind(':status', $status);

        if ($this->hasColumn('user_id')) {
            $this->db->bind(':user_id', $userId);
        }

        $ok = $this->db->execute();
        if (!$ok) {
            return false;
        }
        return (int) $this->db->lastInsertId();
    }

    public function setStatus(int $id, string $status, ?string $reason = null): bool
    {
        if (!$this->hasColumn('status')) {
            return true;
        }

        $sets = ['status = :status'];
        if ($this->hasColumn('rejection_reason')) {
            $sets[] = 'rejection_reason = :reason';
        }

        $sql = 'UPDATE tips SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $this->db->query($sql)
            ->bind(':status', $status)
            ->bind(':id', $id);

        if ($this->hasColumn('rejection_reason')) {
            $this->db->bind(':reason', $reason);
        }

        return $this->db->execute();
    }

    public function delete(int $id): bool
    {
        return $this->db
            ->query('DELETE FROM tips WHERE id = :id')
            ->bind(':id', $id)
            ->execute();
    }

    public function resubmit(int $id, int $userId): bool
    {
        $sets = ['status = "pending"'];
        if ($this->hasColumn('rejection_reason')) {
            $sets[] = 'rejection_reason = NULL';
        }
        $sql = 'UPDATE tips SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :user_id';
        return $this->db
            ->query($sql)
            ->bind(':id', $id)
            ->bind(':user_id', $userId)
            ->execute();
    }

    private function ensureReportTable(): void
    {
        if ($this->reportTableReady) {
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
        // Backfill old recipe reports.
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
        $this->reportTableReady = true;
    }

    public function saveReport(int $reporterId, int $tipId, string $reason): bool
    {
        $this->ensureReportTable();
        $this->db->query("SELECT 1 FROM reports
                          WHERE reporter_id = :reporter_id
                            AND target_type = 'tip'
                            AND target_id = :tip_id
                          LIMIT 1")
            ->bind(':reporter_id', $reporterId)
            ->bind(':tip_id', $tipId)
            ->execute();
        if ($this->db->single()) {
            return false;
        }
        return $this->db
            ->query("INSERT INTO reports (reporter_id, target_type, target_id, reason, status, created_at)
                     VALUES (:reporter_id, 'tip', :tip_id, :reason, 'pending', NOW())")
            ->bind(':reporter_id', $reporterId)
            ->bind(':tip_id', $tipId)
            ->bind(':reason', $reason)
            ->execute();
    }

    public function allReportsForAdmin(?string $status = null): array
    {
        $this->ensureReportTable();
        $sql = "SELECT tr.id,
                       tr.target_id AS tip_id,
                       tr.reason,
                       tr.status,
                       tr.created_at,
                       u.name AS reporter_name,
                       t.title AS tip_title,
                       t.slug AS tip_slug,
                       t.status AS content_status,
                       t.user_id AS target_user_id
                FROM reports tr
                LEFT JOIN users u ON u.id = tr.reporter_id
                LEFT JOIN tips t ON t.id = tr.target_id
                WHERE tr.target_type = 'tip'";
        if ($status !== null && $status !== '') {
            $sql .= ' AND tr.status = :status';
        }
        $sql .= ' ORDER BY tr.id DESC';
        $query = $this->db->query($sql);
        if ($status !== null && $status !== '') {
            $query->bind(':status', $status);
        }
        $query->execute();
        return $query->resultSet();
    }

    public function updateReportStatus(int $reportId, string $status): bool
    {
        $this->ensureReportTable();
        if (!in_array($status, ['pending', 'reviewed', 'resolved'], true)) {
            return false;
        }
        return $this->db
            ->query("UPDATE reports SET status = :status WHERE id = :id AND target_type = 'tip'")
            ->bind(':status', $status)
            ->bind(':id', $reportId)
            ->execute();
    }

    private function ensureSaveTable(): void
    {
        if ($this->saveTableReady) {
            return;
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS tip_saves (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tip_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_tip_saves_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_tip_saves_tip FOREIGN KEY (tip_id) REFERENCES tips(id) ON DELETE CASCADE,
                UNIQUE KEY uq_tip_saves_once (user_id, tip_id),
                INDEX idx_tip_saves_tip (tip_id)
            )")->execute();
        $this->saveTableReady = true;
    }

    public function isSaved(int $userId, int $tipId): bool
    {
        $this->ensureSaveTable();
        $this->db->query('SELECT 1 FROM tip_saves WHERE user_id = :user_id AND tip_id = :tip_id LIMIT 1')
            ->bind(':user_id', $userId)
            ->bind(':tip_id', $tipId)
            ->execute();
        return (bool) $this->db->single();
    }

    public function toggleSave(int $userId, int $tipId): bool
    {
        $this->ensureSaveTable();
        if ($this->isSaved($userId, $tipId)) {
            return $this->db
                ->query('DELETE FROM tip_saves WHERE user_id = :user_id AND tip_id = :tip_id')
                ->bind(':user_id', $userId)
                ->bind(':tip_id', $tipId)
                ->execute();
        }

        return $this->db
            ->query('INSERT INTO tip_saves (user_id, tip_id, created_at) VALUES (:user_id, :tip_id, NOW())')
            ->bind(':user_id', $userId)
            ->bind(':tip_id', $tipId)
            ->execute();
    }

    public function savedByUser(int $userId): array
    {
        $this->ensureSaveTable();
        $this->db->query('SELECT t.*
                          FROM tip_saves ts
                          INNER JOIN tips t ON t.id = ts.tip_id
                          WHERE ts.user_id = :user_id
                          ORDER BY ts.created_at DESC')
            ->bind(':user_id', $userId)
            ->execute();
        return $this->db->resultSet();
    }
}
