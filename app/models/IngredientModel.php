<?php

declare(strict_types=1);

class IngredientModel extends Model
{
    private ?array $ingredientColumns = null;
    private bool $reportTableReady = false;

    private function getIngredientColumns(): array
    {
        if ($this->ingredientColumns !== null) {
            return $this->ingredientColumns;
        }

        $this->db->query('SHOW COLUMNS FROM ingredients')->execute();
        $rows = $this->db->resultSet();
        $columns = [];
        foreach ($rows as $row) {
            if (isset($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }

        $this->ingredientColumns = $columns;
        return $columns;
    }

    private function hasIngredientColumn(string $column): bool
    {
        return in_array($column, $this->getIngredientColumns(), true);
    }

    public function all(?string $status = null, string $source = 'library'): array
    {
        $sql = 'SELECT i.*, c.name AS category_name
                FROM ingredients i
                LEFT JOIN categories c ON c.id = i.category_id';

        $conditions = [];
        if ($this->hasIngredientColumn('source')) {
            $conditions[] = 'i.source = :source';
        }

        if ($status !== null && $this->hasIngredientColumn('status')) {
            $conditions[] = 'i.status = :status';
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY i.id DESC';

        $this->db->query($sql);
        if ($this->hasIngredientColumn('source')) {
            $this->db->bind(':source', $source);
        }
        if ($status !== null && $this->hasIngredientColumn('status')) {
            $this->db->bind(':status', $status);
        }
        $this->db->execute();

        return $this->db->resultSet();
    }

    public function countByStatus(?string $status = null, string $source = 'library', ?string $keyword = null): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM ingredients i';
        $conditions = [];
        if ($this->hasIngredientColumn('source')) {
            $conditions[] = 'i.source = :source';
        }
        if ($status !== null && $this->hasIngredientColumn('status')) {
            $conditions[] = 'i.status = :status';
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(i.name LIKE :kw_name OR i.description LIKE :kw_desc)';
        }
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $this->db->query($sql);
        if ($this->hasIngredientColumn('source')) {
            $this->db->bind(':source', $source);
        }
        if ($status !== null && $this->hasIngredientColumn('status')) {
            $this->db->bind(':status', $status);
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $this->db->bind(':kw_name', $like);
            $this->db->bind(':kw_desc', $like);
        }
        $this->db->execute();
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function allPaged(?string $status, string $source, int $limit, int $offset, ?string $keyword = null): array
    {
        $sql = 'SELECT i.*, c.name AS category_name
                FROM ingredients i
                LEFT JOIN categories c ON c.id = i.category_id';

        $conditions = [];
        if ($this->hasIngredientColumn('source')) {
            $conditions[] = 'i.source = :source';
        }
        if ($status !== null && $this->hasIngredientColumn('status')) {
            $conditions[] = 'i.status = :status';
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(i.name LIKE :kw_name OR i.description LIKE :kw_desc)';
        }
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY i.id DESC LIMIT :limit OFFSET :offset';

        $this->db->query($sql);
        if ($this->hasIngredientColumn('source')) {
            $this->db->bind(':source', $source);
        }
        if ($status !== null && $this->hasIngredientColumn('status')) {
            $this->db->bind(':status', $status);
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $this->db->bind(':kw_name', $like);
            $this->db->bind(':kw_desc', $like);
        }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        $this->db->execute();

        return $this->db->resultSet();
    }

    public function findById(int $id, string $source = 'library'): array|false
    {
        $sql = 'SELECT i.*, c.name AS category_name
                FROM ingredients i
                LEFT JOIN categories c ON c.id = i.category_id
                WHERE i.id = :id
                LIMIT 1';

        if ($this->hasIngredientColumn('source')) {
            $sql = str_replace('LIMIT 1', 'AND i.source = :source LIMIT 1', $sql);
        }

        $this->db->query($sql)->bind(':id', $id);
        if ($this->hasIngredientColumn('source')) {
            $this->db->bind(':source', $source);
        }
        $this->db->execute();

        return $this->db->single();
    }

    public function create(
        string $name,
        ?int $categoryId,
        ?string $description,
        ?string $usage,
        ?string $preparation,
        ?string $storage,
        string $status,
        string $source = 'library',
        ?string $image = null,
        ?int $userId = null
    ): int|false {
        $columns = ['name', 'category_id', 'image', 'description', 'preparation', 'storage', 'created_at'];
        $values = [':name', ':category_id', ':image', ':description', ':preparation', ':storage', 'NOW()'];

        if ($this->hasIngredientColumn('user_id')) {
            $columns[] = 'user_id';
            $values[] = ':user_id';
        }

        if ($this->hasIngredientColumn('usage')) {
            $columns[] = 'usage';
            $values[] = ':usage';
        }
        if ($this->hasIngredientColumn('status')) {
            $columns[] = 'status';
            $values[] = ':status';
        }
        if ($this->hasIngredientColumn('source')) {
            $columns[] = 'source';
            $values[] = ':source';
        }

        $sql = 'INSERT INTO ingredients (' . implode(', ', $columns) . ')
                VALUES (' . implode(', ', $values) . ')';

        $this->db->query($sql)
            ->bind(':name', $name)
            ->bind(':category_id', $categoryId)
            ->bind(':image', $image)
            ->bind(':description', $description)
            ->bind(':preparation', $preparation)
            ->bind(':storage', $storage);

        if ($this->hasIngredientColumn('usage')) {
            $this->db->bind(':usage', $usage);
        }
        if ($this->hasIngredientColumn('status')) {
            $this->db->bind(':status', $status);
        }
        if ($this->hasIngredientColumn('source')) {
            $this->db->bind(':source', $source);
        }
        if ($this->hasIngredientColumn('user_id')) {
            $this->db->bind(':user_id', $userId);
        }

        $ok = $this->db->execute();

        if (!$ok) {
            return false;
        }

        return (int) $this->db->lastInsertId();
    }

    public function update(
        int $id,
        string $name,
        ?int $categoryId,
        ?string $description,
        ?string $usage,
        ?string $preparation,
        ?string $storage,
        ?string $image = null
    ): bool {
        $sets = [
            'name = :name',
            'category_id = :category_id',
            'image = :image',
            'description = :description',
            'preparation = :preparation',
            'storage = :storage',
        ];

        if ($this->hasIngredientColumn('usage')) {
            $sets[] = 'usage = :usage';
        }

        $sql = 'UPDATE ingredients SET ' . implode(', ', $sets) . ' WHERE id = :id';

        $this->db->query($sql)
            ->bind(':name', $name)
            ->bind(':category_id', $categoryId)
            ->bind(':image', $image)
            ->bind(':description', $description)
            ->bind(':preparation', $preparation)
            ->bind(':storage', $storage)
            ->bind(':id', $id);

        if ($this->hasIngredientColumn('usage')) {
            $this->db->bind(':usage', $usage);
        }

        return $this->db->execute();
    }

    public function byUser(int $userId): array
    {
        if (!$this->hasIngredientColumn('user_id')) {
            return [];
        }

        $this->db->query('SELECT i.*, c.name AS category_name
                          FROM ingredients i
                          LEFT JOIN categories c ON c.id = i.category_id
                          WHERE i.user_id = :user_id
                          ORDER BY i.id DESC')
            ->bind(':user_id', $userId)
            ->execute();

        return $this->db->resultSet();
    }

    public function resubmit(int $ingredientId, int $userId): bool
    {
        if (!$this->hasIngredientColumn('user_id')) {
            return false;
        }

        $sets = ['status = "pending"'];
        if ($this->hasIngredientColumn('rejection_reason')) {
            $sets[] = 'rejection_reason = NULL';
        }

        $sql = 'UPDATE ingredients SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :user_id';
        return $this->db
            ->query($sql)
            ->bind(':id', $ingredientId)
            ->bind(':user_id', $userId)
            ->execute();
    }

    public function delete(int $id): bool
    {
        return $this->db
            ->query('DELETE FROM ingredients WHERE id = :id')
            ->bind(':id', $id)
            ->execute();
    }

    public function setStatus(int $id, string $status, ?string $reason = null): bool
    {
        if (!$this->hasIngredientColumn('status')) {
            return true;
        }

        $sets = ['status = :status'];
        if ($this->hasIngredientColumn('rejection_reason')) {
            $sets[] = 'rejection_reason = :reason';
        }

        $sql = 'UPDATE ingredients SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $this->db->query($sql)
            ->bind(':status', $status)
            ->bind(':id', $id);

        if ($this->hasIngredientColumn('rejection_reason')) {
            $this->db->bind(':reason', $reason);
        }

        return $this->db->execute();
    }

    public function getNutrition(int $ingredientId): array|false
    {
        $this->db->query('SELECT calories, protein, fat, carb
                          FROM ingredient_nutrition
                          WHERE ingredient_id = :ingredient_id
                          LIMIT 1')
            ->bind(':ingredient_id', $ingredientId)
            ->execute();

        return $this->db->single();
    }

    public function upsertNutrition(
        int $ingredientId,
        ?float $calories,
        ?float $protein,
        ?float $fat,
        ?float $carb
    ): bool {
        $sql = 'INSERT INTO ingredient_nutrition (ingredient_id, calories, protein, fat, carb)
                VALUES (:ingredient_id, :calories, :protein, :fat, :carb)
                ON DUPLICATE KEY UPDATE
                    calories = VALUES(calories),
                    protein = VALUES(protein),
                    fat = VALUES(fat),
                    carb = VALUES(carb)';

        return $this->db
            ->query($sql)
            ->bind(':ingredient_id', $ingredientId)
            ->bind(':calories', $calories)
            ->bind(':protein', $protein)
            ->bind(':fat', $fat)
            ->bind(':carb', $carb)
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

    public function saveReport(int $reporterId, int $ingredientId, string $reason): bool
    {
        $this->ensureReportTable();
        $this->db->query("SELECT 1 FROM reports
                          WHERE reporter_id = :reporter_id
                            AND target_type = 'ingredient'
                            AND target_id = :ingredient_id
                          LIMIT 1")
            ->bind(':reporter_id', $reporterId)
            ->bind(':ingredient_id', $ingredientId)
            ->execute();
        if ($this->db->single()) {
            return false;
        }
        return $this->db
            ->query("INSERT INTO reports (reporter_id, target_type, target_id, reason, status, created_at)
                     VALUES (:reporter_id, 'ingredient', :ingredient_id, :reason, 'pending', NOW())")
            ->bind(':reporter_id', $reporterId)
            ->bind(':ingredient_id', $ingredientId)
            ->bind(':reason', $reason)
            ->execute();
    }

    public function allReportsForAdmin(?string $status = null): array
    {
        $this->ensureReportTable();
        $sql = "SELECT ir.id,
                       ir.target_id AS ingredient_id,
                       ir.reason,
                       ir.status,
                       ir.created_at,
                       u.name AS reporter_name,
                       i.name AS ingredient_name,
                       i.status AS content_status,
                       i.user_id AS target_user_id
                FROM reports ir
                LEFT JOIN users u ON u.id = ir.reporter_id
                LEFT JOIN ingredients i ON i.id = ir.target_id
                WHERE ir.target_type = 'ingredient'";
        if ($status !== null && $status !== '') {
            $sql .= ' AND ir.status = :status';
        }
        $sql .= ' ORDER BY ir.id DESC';
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
            ->query("UPDATE reports SET status = :status WHERE id = :id AND target_type = 'ingredient'")
            ->bind(':status', $status)
            ->bind(':id', $reportId)
            ->execute();
    }

    public function isSaved(int $userId, int $ingredientId): bool
    {
        $this->db->query('SELECT 1 FROM saved_items WHERE user_id = :user_id AND item_id = :ingredient_id AND item_type = "ingredient" LIMIT 1')
            ->bind(':user_id', $userId)
            ->bind(':ingredient_id', $ingredientId)
            ->execute();
        return (bool) $this->db->single();
    }

    public function toggleSave(int $userId, int $ingredientId): bool
    {
        if ($this->isSaved($userId, $ingredientId)) {
            return $this->db
                ->query('DELETE FROM saved_items WHERE user_id = :user_id AND item_id = :ingredient_id AND item_type = "ingredient"')
                ->bind(':user_id', $userId)
                ->bind(':ingredient_id', $ingredientId)
                ->execute();
        }

        return $this->db
            ->query('INSERT INTO saved_items (user_id, item_id, item_type, created_at) VALUES (:user_id, :ingredient_id, "ingredient", NOW())')
            ->bind(':user_id', $userId)
            ->bind(':ingredient_id', $ingredientId)
            ->execute();
    }

    public function savedByUser(int $userId): array
    {
        $this->db->query('SELECT i.*, c.name AS category_name
                          FROM saved_items s
                          INNER JOIN ingredients i ON i.id = s.item_id
                          LEFT JOIN categories c ON c.id = i.category_id
                          WHERE s.user_id = :user_id
                            AND s.item_type = "ingredient"
                          ORDER BY s.created_at DESC')
            ->bind(':user_id', $userId)
            ->execute();
        return $this->db->resultSet();
    }

    public function findLowCalorieIngredientIds(int $maxCaloriesPer100g = 120, int $limit = 24): array
    {
        $maxCaloriesPer100g = max(10, min(400, $maxCaloriesPer100g));
        $limit = max(1, min(100, $limit));

        $sql = 'SELECT i.id
                FROM ingredient_nutrition n
                INNER JOIN ingredients i ON i.id = n.ingredient_id
                LEFT JOIN recipe_ingredients ri ON ri.ingredient_id = i.id
                WHERE n.calories IS NOT NULL
                  AND n.calories > 0
                  AND n.calories <= :max_calories';

        if ($this->hasIngredientColumn('status')) {
            $sql .= ' AND i.status = "approved"';
        }
        if ($this->hasIngredientColumn('source')) {
            $sql .= ' AND (i.source = "library" OR i.source = "recipe")';
        }

        $sql .= ' GROUP BY i.id, n.calories
                  ORDER BY COUNT(ri.recipe_id) DESC, n.calories ASC, i.id DESC
                  LIMIT :limit';

        $this->db->query($sql)
            ->bind(':max_calories', $maxCaloriesPer100g)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->execute();

        $rows = $this->db->resultSet();
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
