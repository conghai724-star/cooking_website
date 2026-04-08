<?php

declare(strict_types=1);

class CommentModel extends Model
{
    private bool $schemaReady = false;
    private bool $reportTableReady = false;

    private function tableExists(string $table): bool
    {
        $this->db->query('SELECT 1
                          FROM information_schema.tables
                          WHERE table_schema = DATABASE()
                            AND table_name = :table
                          LIMIT 1')
            ->bind(':table', $table)
            ->execute();
        return (bool) $this->db->single();
    }

    private function ensureUnifiedSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $this->db->query('SHOW COLUMNS FROM comments')->execute();
        $columns = [];
        foreach ($this->db->resultSet() as $row) {
            $field = (string) ($row['Field'] ?? '');
            if ($field !== '') {
                $columns[$field] = true;
            }
        }

        if (!isset($columns['content_type'])) {
            $this->db->query("ALTER TABLE comments
                              ADD COLUMN content_type ENUM('recipe','tip','ingredient') NOT NULL DEFAULT 'recipe' AFTER recipe_id")
                ->execute();
        }

        if (!isset($columns['content_id'])) {
            $this->db->query('ALTER TABLE comments ADD COLUMN content_id INT NULL AFTER content_type')
                ->execute();
        }

        if (isset($columns['recipe_id'])) {
            // Cho phép comment không thuộc recipe.
            $this->db->query('ALTER TABLE comments MODIFY COLUMN recipe_id INT NULL')->execute();
            // Backfill cho dữ liệu cũ.
            $this->db->query("UPDATE comments
                              SET content_type = 'recipe'
                              WHERE content_type IS NULL OR content_type = ''")
                ->execute();
            $this->db->query("UPDATE comments
                              SET content_id = recipe_id
                              WHERE content_id IS NULL AND recipe_id IS NOT NULL")
                ->execute();
        }

        if (!isset($columns['like_count'])) {
            $this->db->query('ALTER TABLE comments ADD COLUMN like_count INT NOT NULL DEFAULT 0 AFTER content')
                ->execute();
        }
        if (!isset($columns['reply_count'])) {
            $this->db->query('ALTER TABLE comments ADD COLUMN reply_count INT NOT NULL DEFAULT 0 AFTER like_count')
                ->execute();
        }
        if (!isset($columns['is_edited'])) {
            $this->db->query('ALTER TABLE comments ADD COLUMN is_edited TINYINT(1) NOT NULL DEFAULT 0 AFTER status')
                ->execute();
        }
        if (!isset($columns['edited_at'])) {
            $this->db->query('ALTER TABLE comments ADD COLUMN edited_at DATETIME NULL AFTER is_edited')
                ->execute();
        }
        if (!isset($columns['deleted_at'])) {
            $this->db->query('ALTER TABLE comments ADD COLUMN deleted_at DATETIME NULL AFTER edited_at')
                ->execute();
        }
        if (!isset($columns['deleted_by'])) {
            $this->db->query('ALTER TABLE comments ADD COLUMN deleted_by INT NULL AFTER deleted_at')
                ->execute();
        }
        if (!isset($columns['delete_reason'])) {
            $this->db->query('ALTER TABLE comments ADD COLUMN delete_reason VARCHAR(255) NULL AFTER deleted_by')
                ->execute();
        }
        if (!isset($columns['updated_at'])) {
            $this->db->query('ALTER TABLE comments ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at')
                ->execute();
        }

        // Chuẩn hóa trạng thái về active/hidden/deleted.
        $this->db->query("UPDATE comments SET status = 'active' WHERE status = 'visible'")->execute();
        $this->db->query("ALTER TABLE comments MODIFY status ENUM('active','hidden','deleted') NOT NULL DEFAULT 'active'")->execute();

        $this->db->query('SHOW INDEX FROM comments WHERE Key_name = :idx')
            ->bind(':idx', 'idx_comments_content')
            ->execute();
        if (!$this->db->single()) {
            $this->db->query('ALTER TABLE comments ADD INDEX idx_comments_content (content_type, content_id)')
                ->execute();
        }
        $this->db->query('SHOW INDEX FROM comments WHERE Key_name = :idx')
            ->bind(':idx', 'idx_comments_parent')
            ->execute();
        if (!$this->db->single()) {
            $this->db->query('ALTER TABLE comments ADD INDEX idx_comments_parent (parent_id)')
                ->execute();
        }

        // FK deleted_by (best effort)
        try {
            $this->db->query('ALTER TABLE comments ADD CONSTRAINT fk_comments_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL')
                ->execute();
        } catch (Throwable $e) {
            // Ignore if already exists.
        }

        // Migrate dữ liệu cũ (nếu còn bảng tách riêng).
        if ($this->tableExists('tip_comments')) {
            $this->db->query("INSERT INTO comments (user_id, recipe_id, parent_id, content, status, created_at, content_type, content_id)
                              SELECT tc.user_id, NULL, NULL, tc.content, tc.status, tc.created_at, 'tip', tc.tip_id
                              FROM tip_comments tc
                              LEFT JOIN comments c
                                     ON c.content_type = 'tip'
                                    AND c.content_id = tc.tip_id
                                    AND c.user_id = tc.user_id
                                    AND c.content = tc.content
                                    AND c.created_at = tc.created_at
                              WHERE c.id IS NULL")
                ->execute();
        }

        if ($this->tableExists('ingredient_comments')) {
            $this->db->query("INSERT INTO comments (user_id, recipe_id, parent_id, content, status, created_at, content_type, content_id)
                              SELECT ic.user_id, NULL, NULL, ic.content, ic.status, ic.created_at, 'ingredient', ic.ingredient_id
                              FROM ingredient_comments ic
                              LEFT JOIN comments c
                                     ON c.content_type = 'ingredient'
                                    AND c.content_id = ic.ingredient_id
                                    AND c.user_id = ic.user_id
                                    AND c.content = ic.content
                                    AND c.created_at = ic.created_at
                              WHERE c.id IS NULL")
                ->execute();
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

    public function findById(int $commentId): ?array
    {
        $this->ensureUnifiedSchema();
        $sql = 'SELECT c.*, u.name
                FROM comments c
                LEFT JOIN users u ON u.id = c.user_id
                WHERE c.id = :comment_id
                LIMIT 1';

        $this->db->query($sql)
            ->bind(':comment_id', $commentId)
            ->execute();

        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function allForAdmin(?string $status = null, ?string $keyword = null, bool $reportedOnly = false): array
    {
        $this->ensureUnifiedSchema();
        $this->ensureReportTable();

        $statusValue = null;
        if ($status !== null) {
            $normalized = $this->normalizeStatus($status);
            if (in_array($normalized, ['active', 'hidden', 'deleted'], true)) {
                $statusValue = $normalized;
            }
        }

        $sql = "SELECT c.content_type,
                       c.id,
                       c.user_id,
                       c.content,
                       c.status,
                       c.created_at,
                       u.name AS author_name,
                       c.content_id AS target_id,
                       COALESCE(r.title, t.title, i.name) AS target_title,
                       (SELECT COUNT(*) FROM reports cr WHERE cr.target_type = 'comment' AND cr.target_id = c.id) AS report_count
                FROM comments c
                LEFT JOIN users u ON u.id = c.user_id
                LEFT JOIN recipes r ON c.content_type = 'recipe' AND r.id = c.content_id
                LEFT JOIN tips t ON c.content_type = 'tip' AND t.id = c.content_id
                LEFT JOIN ingredients i ON c.content_type = 'ingredient' AND i.id = c.content_id
                WHERE (:status_null IS NULL OR c.status = :status_eq)
                ORDER BY c.created_at DESC, c.id DESC";

        $this->db->query($sql)
            ->bind(':status_null', $statusValue)
            ->bind(':status_eq', $statusValue)
            ->execute();
        $rows = $this->db->resultSet();

        if ($reportedOnly) {
            $rows = array_values(array_filter($rows, static fn(array $r): bool => (int) ($r['report_count'] ?? 0) > 0));
        }

        if ($keyword !== null && $keyword !== '') {
            $kw = function_exists('mb_strtolower') ? mb_strtolower($keyword, 'UTF-8') : strtolower($keyword);
            $rows = array_values(array_filter($rows, static function (array $r) use ($kw): bool {
                $haystack = (string) (($r['content'] ?? '') . ' ' . ($r['author_name'] ?? '') . ' ' . ($r['target_title'] ?? ''));
                $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
                return str_contains($haystack, $kw);
            }));
        }

        return $rows;
    }

    public function setStatus(int $commentId, string $status): bool
    {
        $this->ensureUnifiedSchema();
        $status = $this->normalizeStatus($status);
        if (!in_array($status, ['active', 'hidden', 'deleted'], true)) {
            return false;
        }

        return $this->db
            ->query('UPDATE comments SET status = :status WHERE id = :id')
            ->bind(':status', $status)
            ->bind(':id', $commentId)
            ->execute();
    }

    public function deleteById(int $commentId): bool
    {
        $this->ensureUnifiedSchema();
        return $this->db
            ->query('DELETE FROM comments WHERE id = :id')
            ->bind(':id', $commentId)
            ->execute();
    }

    public function setStatusByType(int $commentId, string $contentType, string $status): bool
    {
        $this->ensureUnifiedSchema();
        $status = $this->normalizeStatus($status);
        if (!in_array($status, ['active', 'hidden', 'deleted'], true)) {
            return false;
        }
        if (!in_array($contentType, ['recipe', 'tip', 'ingredient'], true)) {
            return false;
        }
        return $this->db
            ->query('UPDATE comments SET status = :status WHERE id = :id AND content_type = :content_type')
            ->bind(':status', $status)
            ->bind(':id', $commentId)
            ->bind(':content_type', $contentType)
            ->execute();
    }

    public function deleteByType(int $commentId, string $contentType): bool
    {
        // Backward-compatible alias: "delete" in admin means soft-delete.
        return $this->softDeleteByType($commentId, $contentType, null, null);
    }

    public function softDeleteByType(int $commentId, string $contentType, ?int $deletedBy = null, ?string $reason = null): bool
    {
        $this->ensureUnifiedSchema();
        if (!in_array($contentType, ['recipe', 'tip', 'ingredient'], true)) {
            return false;
        }
        return $this->db
            ->query("UPDATE comments
                     SET status = 'deleted',
                         deleted_at = NOW(),
                         deleted_by = :deleted_by,
                         delete_reason = :delete_reason
                     WHERE id = :id
                       AND content_type = :content_type")
            ->bind(':deleted_by', $deletedBy)
            ->bind(':delete_reason', $reason)
            ->bind(':id', $commentId)
            ->bind(':content_type', $contentType)
            ->execute();
    }

    public function restoreByType(int $commentId, string $contentType): bool
    {
        $this->ensureUnifiedSchema();
        if (!in_array($contentType, ['recipe', 'tip', 'ingredient'], true)) {
            return false;
        }
        return $this->db
            ->query("UPDATE comments
                     SET status = 'active',
                         deleted_at = NULL,
                         deleted_by = NULL,
                         delete_reason = NULL
                     WHERE id = :id
                       AND content_type = :content_type")
            ->bind(':id', $commentId)
            ->bind(':content_type', $contentType)
            ->execute();
    }

    public function byRecipe(int $recipeId): array
    {
        $this->ensureUnifiedSchema();
        $sql = 'SELECT c.*, u.name
                FROM comments c
                LEFT JOIN users u ON u.id = c.user_id
                WHERE c.content_type = :content_type
                  AND c.content_id = :content_id
                  AND c.status = :status
                ORDER BY c.id DESC';

        $this->db->query($sql)
            ->bind(':content_type', 'recipe')
            ->bind(':content_id', $recipeId)
            ->bind(':status', 'active')
            ->execute();

        return $this->db->resultSet();
    }

    public function byTip(int $tipId): array
    {
        $this->ensureUnifiedSchema();
        $sql = 'SELECT c.*, u.name
                FROM comments c
                LEFT JOIN users u ON u.id = c.user_id
                WHERE c.content_type = :content_type
                  AND c.content_id = :content_id
                  AND c.status = :status
                ORDER BY c.id DESC';

        $this->db->query($sql)
            ->bind(':content_type', 'tip')
            ->bind(':content_id', $tipId)
            ->bind(':status', 'active')
            ->execute();

        return $this->db->resultSet();
    }

    public function byIngredient(int $ingredientId): array
    {
        $this->ensureUnifiedSchema();
        $sql = 'SELECT c.*, u.name
                FROM comments c
                LEFT JOIN users u ON u.id = c.user_id
                WHERE c.content_type = :content_type
                  AND c.content_id = :content_id
                  AND c.status = :status
                ORDER BY c.id DESC';

        $this->db->query($sql)
            ->bind(':content_type', 'ingredient')
            ->bind(':content_id', $ingredientId)
            ->bind(':status', 'active')
            ->execute();

        return $this->db->resultSet();
    }

    public function create(int $userId, int $recipeId, string $content): bool
    {
        $this->ensureUnifiedSchema();
        $sql = 'INSERT INTO comments (user_id, recipe_id, content_type, content_id, content, created_at)
                VALUES (:user_id, :recipe_id, :content_type, :content_id, :content, NOW())';

        return $this->db
            ->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':recipe_id', $recipeId)
            ->bind(':content_type', 'recipe')
            ->bind(':content_id', $recipeId)
            ->bind(':content', $content)
            ->execute();
    }

    public function createReply(int $userId, int $recipeId, int $parentId, string $content): bool
    {
        $this->ensureUnifiedSchema();
        $sql = 'INSERT INTO comments (user_id, recipe_id, content_type, content_id, parent_id, content, created_at)
                VALUES (:user_id, :recipe_id, :content_type, :content_id, :parent_id, :content, NOW())';

        return $this->db
            ->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':recipe_id', $recipeId)
            ->bind(':content_type', 'recipe')
            ->bind(':content_id', $recipeId)
            ->bind(':parent_id', $parentId)
            ->bind(':content', $content)
            ->execute();
    }

    public function createTip(int $userId, int $tipId, string $content, ?int $parentId = null): bool
    {
        $this->ensureUnifiedSchema();
        $sql = 'INSERT INTO comments (user_id, recipe_id, content_type, content_id, parent_id, content, created_at)
                VALUES (:user_id, NULL, :content_type, :content_id, :parent_id, :content, NOW())';

        return $this->db
            ->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':content_type', 'tip')
            ->bind(':content_id', $tipId)
            ->bind(':parent_id', $parentId)
            ->bind(':content', $content)
            ->execute();
    }

    public function createIngredient(int $userId, int $ingredientId, string $content): bool
    {
        $this->ensureUnifiedSchema();
        $sql = 'INSERT INTO comments (user_id, recipe_id, content_type, content_id, parent_id, content, created_at)
                VALUES (:user_id, NULL, :content_type, :content_id, NULL, :content, NOW())';

        return $this->db
            ->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':content_type', 'ingredient')
            ->bind(':content_id', $ingredientId)
            ->bind(':content', $content)
            ->execute();
    }

    public function findTipCommentById(int $commentId): ?array
    {
        $this->ensureUnifiedSchema();
        $this->db->query('SELECT *, content_id AS tip_id FROM comments WHERE id = :id AND content_type = :content_type LIMIT 1')
            ->bind(':id', $commentId)
            ->bind(':content_type', 'tip')
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function findIngredientCommentById(int $commentId): ?array
    {
        $this->ensureUnifiedSchema();
        $this->db->query('SELECT *, content_id AS ingredient_id FROM comments WHERE id = :id AND content_type = :content_type LIMIT 1')
            ->bind(':id', $commentId)
            ->bind(':content_type', 'ingredient')
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function hasReported(int $reporterId, int $commentId): bool
    {
        $this->ensureUnifiedSchema();
        $this->ensureReportTable();
        $this->db->query("SELECT 1 FROM reports
                          WHERE reporter_id = :reporter_id
                            AND target_type = 'comment'
                            AND target_id = :comment_id
                          LIMIT 1")
            ->bind(':reporter_id', $reporterId)
            ->bind(':comment_id', $commentId)
            ->execute();

        return (bool) $this->db->single();
    }

    public function hasReportedTip(int $reporterId, int $commentId): bool
    {
        return $this->hasReported($reporterId, $commentId);
    }

    public function hasReportedIngredient(int $reporterId, int $commentId): bool
    {
        return $this->hasReported($reporterId, $commentId);
    }

    public function report(int $reporterId, int $commentId, string $reason): bool
    {
        $this->ensureUnifiedSchema();
        $this->ensureReportTable();
        $sql = "INSERT INTO reports (reporter_id, target_type, target_id, reason, status, created_at)
                VALUES (:reporter_id, 'comment', :comment_id, :reason, :status, NOW())";

        try {
            return $this->db
                ->query($sql)
                ->bind(':reporter_id', $reporterId)
                ->bind(':comment_id', $commentId)
                ->bind(':reason', $reason)
                ->bind(':status', 'pending')
                ->execute();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    public function reportTip(int $reporterId, int $commentId, string $reason): bool
    {
        return $this->report($reporterId, $commentId, $reason);
    }

    public function reportIngredient(int $reporterId, int $commentId, string $reason): bool
    {
        return $this->report($reporterId, $commentId, $reason);
    }

    public function allReportsForAdmin(?string $status = null): array
    {
        $this->ensureUnifiedSchema();
        $this->ensureReportTable();

        $statusValue = null;
        if (in_array((string) $status, ['pending', 'reviewed', 'resolved'], true)) {
            $statusValue = (string) $status;
        }

        $sql = "SELECT c.content_type,
                       cr.id,
                       cr.target_id AS comment_id,
                       cr.reporter_id,
                       cr.reason,
                       cr.status,
                       cr.created_at,
                       u.name AS reporter_name,
                       c.content AS comment_content,
                       c.status AS comment_status,
                       c.user_id AS target_user_id,
                       c.content_id AS target_id,
                       COALESCE(r.title, t.title, i.name) AS target_title,
                       t.slug AS target_slug
                FROM reports cr
                LEFT JOIN comments c ON c.id = cr.target_id
                LEFT JOIN users u ON u.id = cr.reporter_id
                LEFT JOIN recipes r ON c.content_type = 'recipe' AND r.id = c.content_id
                LEFT JOIN tips t ON c.content_type = 'tip' AND t.id = c.content_id
                LEFT JOIN ingredients i ON c.content_type = 'ingredient' AND i.id = c.content_id
                WHERE cr.target_type = 'comment'
                  AND (:status_null IS NULL OR cr.status = :status_eq)
                ORDER BY cr.created_at DESC, cr.id DESC";

        $this->db->query($sql)
            ->bind(':status_null', $statusValue)
            ->bind(':status_eq', $statusValue)
            ->execute();
        return $this->db->resultSet();
    }

    public function updateReportStatus(int $reportId, string $contentType, string $status): bool
    {
        $this->ensureReportTable();
        if (!in_array($status, ['pending', 'reviewed', 'resolved'], true)) {
            return false;
        }

        return $this->db
            ->query("UPDATE reports SET status = :status WHERE id = :id AND target_type = 'comment'")
            ->bind(':status', $status)
            ->bind(':id', $reportId)
            ->execute();
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'visible' => 'active',
            default => $status,
        };
    }
}
