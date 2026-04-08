<?php

declare(strict_types=1);

class MealPlanModel extends Model
{
    private bool $planSchemaEnsured = false;
    private bool $settingsEnsured = false;
    private bool $locksEnsured = false;
    private ?array $recipeColumns = null;

    private function ensureSettingsTable(): void
    {
        if ($this->settingsEnsured) {
            return;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS meal_plan_settings (
                    user_id INT NOT NULL PRIMARY KEY,
                    visibility ENUM("private","public","followers","friends","link") NOT NULL DEFAULT "private",
                    share_token VARCHAR(64) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT fk_meal_plan_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY uq_meal_plan_settings_token (share_token)
                )';

        $this->db->query($sql)->execute();
        $this->settingsEnsured = true;
    }

    private function getRecipeColumns(): array
    {
        if ($this->recipeColumns !== null) {
            return $this->recipeColumns;
        }

        $this->db->query('SHOW COLUMNS FROM recipes')->execute();
        $rows = $this->db->resultSet();
        $columns = [];
        foreach ($rows as $row) {
            if (isset($row['Field'])) {
                $columns[$row['Field']] = true;
            }
        }

        $this->recipeColumns = $columns;
        return $columns;
    }

    private function ensureLocksTables(): void
    {
        if ($this->locksEnsured) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS meal_plan_week_locks (
                user_id INT NOT NULL,
                week_start_date DATE NOT NULL,
                is_locked TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, week_start_date),
                CONSTRAINT fk_meal_plan_week_locks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )')->execute();

        $this->db->query('CREATE TABLE IF NOT EXISTS meal_plan_day_locks (
                user_id INT NOT NULL,
                lock_date DATE NOT NULL,
                is_locked TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, lock_date),
                CONSTRAINT fk_meal_plan_day_locks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )')->execute();

        $this->locksEnsured = true;
    }

    private function ensurePlanSchema(): void
    {
        if ($this->planSchemaEnsured) {
            return;
        }

        $this->db->query("SHOW COLUMNS FROM meal_plans LIKE 'dish_role'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE meal_plans
                              ADD COLUMN dish_role ENUM('main','side','soup','dessert','drink','other')
                              NOT NULL DEFAULT 'main' AFTER meal_type")
                ->execute();
        }

        // FK columns must have their own indexes before dropping legacy unique index.
        $this->db->query('SHOW INDEX FROM meal_plans WHERE Key_name = :idx')
            ->bind(':idx', 'idx_meal_plans_user')
            ->execute();
        if (!$this->db->single()) {
            $this->db->query('ALTER TABLE meal_plans ADD INDEX idx_meal_plans_user (user_id)')
                ->execute();
        }

        $this->db->query('SHOW INDEX FROM meal_plans WHERE Key_name = :idx')
            ->bind(':idx', 'idx_meal_plans_recipe')
            ->execute();
        if (!$this->db->single()) {
            $this->db->query('ALTER TABLE meal_plans ADD INDEX idx_meal_plans_recipe (recipe_id)')
                ->execute();
        }

        $this->db->query('SHOW INDEX FROM meal_plans WHERE Key_name = :idx')
            ->bind(':idx', 'uq_meal_plan')
            ->execute();
        if ($this->db->single()) {
            try {
                $this->db->query('ALTER TABLE meal_plans DROP INDEX uq_meal_plan')->execute();
            } catch (Throwable $e) {
                // Keep backward-compatible schema if DB still binds FK to this index.
                $this->planSchemaEnsured = true;
                return;
            }
        }

        $this->db->query('SHOW INDEX FROM meal_plans WHERE Key_name = :idx')
            ->bind(':idx', 'uq_meal_plan_recipe')
            ->execute();
        if (!$this->db->single()) {
            $this->db->query('ALTER TABLE meal_plans ADD UNIQUE KEY uq_meal_plan_recipe (user_id, plan_date, meal_type, recipe_id)')
                ->execute();
        }

        $this->db->query('SHOW INDEX FROM meal_plans WHERE Key_name = :idx')
            ->bind(':idx', 'idx_meal_plan_slot')
            ->execute();
        if (!$this->db->single()) {
            $this->db->query('ALTER TABLE meal_plans ADD INDEX idx_meal_plan_slot (user_id, plan_date, meal_type, id)')
                ->execute();
        }

        $this->planSchemaEnsured = true;
    }

    public function getSettings(int $userId): array
    {
        $this->ensureSettingsTable();

        $this->db->query('SELECT user_id, visibility, share_token FROM meal_plan_settings WHERE user_id = :user_id LIMIT 1')
            ->bind(':user_id', $userId)
            ->execute();

        $row = $this->db->single();
        if ($row !== false) {
            return $row;
        }

        $token = bin2hex(random_bytes(16));
        $this->db->query('INSERT INTO meal_plan_settings (user_id, visibility, share_token) VALUES (:user_id, "private", :token)')
            ->bind(':user_id', $userId)
            ->bind(':token', $token)
            ->execute();

        return [
            'user_id' => $userId,
            'visibility' => 'private',
            'share_token' => $token,
        ];
    }

    public function updateVisibility(int $userId, string $visibility): bool
    {
        $this->ensureSettingsTable();
        $settings = $this->getSettings($userId);
        $token = (string) ($settings['share_token'] ?? '');
        if ($token === '') {
            $token = bin2hex(random_bytes(16));
        }

        return $this->db
            ->query('UPDATE meal_plan_settings SET visibility = :visibility, share_token = :token WHERE user_id = :user_id')
            ->bind(':visibility', $visibility)
            ->bind(':token', $token)
            ->bind(':user_id', $userId)
            ->execute();
    }

    public function regenerateToken(int $userId): string
    {
        $this->ensureSettingsTable();
        $this->getSettings($userId);

        $token = bin2hex(random_bytes(16));
        $this->db->query('UPDATE meal_plan_settings SET share_token = :token WHERE user_id = :user_id')
            ->bind(':token', $token)
            ->bind(':user_id', $userId)
            ->execute();

        return $token;
    }

    public function findUserIdByToken(string $token): ?int
    {
        $this->ensureSettingsTable();
        $this->db->query('SELECT user_id FROM meal_plan_settings WHERE share_token = :token LIMIT 1')
            ->bind(':token', $token)
            ->execute();

        $row = $this->db->single();
        if ($row === false) {
            return null;
        }

        return (int) ($row['user_id'] ?? 0) ?: null;
    }

    public function getWeeklyPlan(int $userId, string $startDate, string $endDate, bool $publicOnly = false): array
    {
        $this->ensurePlanSchema();
        $recipeColumns = $this->getRecipeColumns();
        $selects = ['mp.id AS meal_plan_id', 'mp.plan_date', 'mp.meal_type', 'mp.dish_role', 'mp.recipe_id', 'r.title', 'r.image'];
        if (isset($recipeColumns['status'])) {
            $selects[] = 'r.status';
        }
        if (isset($recipeColumns['user_state'])) {
            $selects[] = 'r.user_state';
        }

        $sql = 'SELECT ' . implode(', ', $selects) . '
                FROM meal_plans mp
                INNER JOIN recipes r ON r.id = mp.recipe_id
                WHERE mp.user_id = :user_id
                  AND mp.plan_date BETWEEN :start_date AND :end_date';

        if ($publicOnly) {
            if (isset($recipeColumns['status'])) {
                $sql .= ' AND r.status = "approved"';
            }
            if (isset($recipeColumns['user_state'])) {
                $sql .= ' AND (r.user_state IS NULL OR r.user_state = "published")';
            }
        }

        $sql .= ' ORDER BY mp.plan_date ASC, FIELD(mp.meal_type, "breakfast", "lunch", "dinner") ASC, mp.id ASC';

        $this->db->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':start_date', $startDate)
            ->bind(':end_date', $endDate)
            ->execute();

        return $this->db->resultSet();
    }

    public function assignMeal(int $userId, string $planDate, string $mealType, int $recipeId, string $dishRole = 'main'): bool
    {
        $this->ensurePlanSchema();
        return $this->db
            ->query('INSERT INTO meal_plans (user_id, plan_date, meal_type, dish_role, recipe_id, created_at)
                     VALUES (:user_id, :plan_date, :meal_type, :dish_role, :recipe_id, NOW())
                     ON DUPLICATE KEY UPDATE dish_role = VALUES(dish_role), id = id')
            ->bind(':user_id', $userId, PDO::PARAM_INT)
            ->bind(':plan_date', $planDate)
            ->bind(':meal_type', $mealType)
            ->bind(':dish_role', $dishRole)
            ->bind(':recipe_id', $recipeId, PDO::PARAM_INT)
            ->execute();
    }

    public function removeMeal(int $userId, string $planDate, string $mealType, ?int $mealPlanId = null): bool
    {
        $this->ensurePlanSchema();
        if ($mealPlanId !== null && $mealPlanId > 0) {
            return $this->db
                ->query('DELETE FROM meal_plans
                         WHERE id = :id
                           AND user_id = :user_id
                           AND plan_date = :plan_date
                           AND meal_type = :meal_type')
                ->bind(':id', $mealPlanId, PDO::PARAM_INT)
                ->bind(':user_id', $userId, PDO::PARAM_INT)
                ->bind(':plan_date', $planDate)
                ->bind(':meal_type', $mealType)
                ->execute();
        }

        return $this->db
            ->query('DELETE FROM meal_plans WHERE user_id = :user_id AND plan_date = :plan_date AND meal_type = :meal_type')
            ->bind(':user_id', $userId, PDO::PARAM_INT)
            ->bind(':plan_date', $planDate)
            ->bind(':meal_type', $mealType)
            ->execute();
    }

    public function isWeekLocked(int $userId, string $weekStartDate): bool
    {
        $this->ensureLocksTables();
        $this->db->query('SELECT is_locked FROM meal_plan_week_locks WHERE user_id = :user_id AND week_start_date = :week_start_date LIMIT 1')
            ->bind(':user_id', $userId, PDO::PARAM_INT)
            ->bind(':week_start_date', $weekStartDate)
            ->execute();

        $row = $this->db->single();
        return (int) ($row['is_locked'] ?? 0) === 1;
    }

    public function setWeekLock(int $userId, string $weekStartDate, bool $isLocked): bool
    {
        $this->ensureLocksTables();
        return $this->db
            ->query('INSERT INTO meal_plan_week_locks (user_id, week_start_date, is_locked)
                     VALUES (:user_id, :week_start_date, :is_locked)
                     ON DUPLICATE KEY UPDATE is_locked = VALUES(is_locked)')
            ->bind(':user_id', $userId, PDO::PARAM_INT)
            ->bind(':week_start_date', $weekStartDate)
            ->bind(':is_locked', $isLocked ? 1 : 0, PDO::PARAM_INT)
            ->execute();
    }

    public function getDayLocks(int $userId, string $startDate, string $endDate): array
    {
        $this->ensureLocksTables();
        $this->db->query('SELECT lock_date, is_locked
                          FROM meal_plan_day_locks
                          WHERE user_id = :user_id
                            AND lock_date BETWEEN :start_date AND :end_date')
            ->bind(':user_id', $userId, PDO::PARAM_INT)
            ->bind(':start_date', $startDate)
            ->bind(':end_date', $endDate)
            ->execute();

        $rows = $this->db->resultSet();
        $map = [];
        foreach ($rows as $row) {
            $date = (string) ($row['lock_date'] ?? '');
            if ($date === '') {
                continue;
            }
            $map[$date] = (int) ($row['is_locked'] ?? 0) === 1;
        }
        return $map;
    }

    public function setDayLock(int $userId, string $lockDate, bool $isLocked): bool
    {
        $this->ensureLocksTables();
        return $this->db
            ->query('INSERT INTO meal_plan_day_locks (user_id, lock_date, is_locked)
                     VALUES (:user_id, :lock_date, :is_locked)
                     ON DUPLICATE KEY UPDATE is_locked = VALUES(is_locked)')
            ->bind(':user_id', $userId, PDO::PARAM_INT)
            ->bind(':lock_date', $lockDate)
            ->bind(':is_locked', $isLocked ? 1 : 0, PDO::PARAM_INT)
            ->execute();
    }

    public function countForAdmin(?string $keyword = null, ?int $userId = null, ?string $fromDate = null, ?string $toDate = null): int
    {
        $this->ensurePlanSchema();

        $sql = 'SELECT COUNT(*) AS total
                FROM meal_plans mp
                INNER JOIN users u ON u.id = mp.user_id
                INNER JOIN recipes r ON r.id = mp.recipe_id
                WHERE 1=1';

        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= ' AND (
                        u.name LIKE :kw_name
                        OR u.email LIKE :kw_email
                        OR r.title LIKE :kw_title
                    )';
        }
        if ($userId !== null && $userId > 0) {
            $sql .= ' AND mp.user_id = :user_id';
        }
        if ($fromDate !== null && $fromDate !== '') {
            $sql .= ' AND mp.plan_date >= :from_date';
        }
        if ($toDate !== null && $toDate !== '') {
            $sql .= ' AND mp.plan_date <= :to_date';
        }

        $query = $this->db->query($sql);
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $query->bind(':kw_name', $like)
                ->bind(':kw_email', $like)
                ->bind(':kw_title', $like);
        }
        if ($userId !== null && $userId > 0) {
            $query->bind(':user_id', $userId, PDO::PARAM_INT);
        }
        if ($fromDate !== null && $fromDate !== '') {
            $query->bind(':from_date', $fromDate);
        }
        if ($toDate !== null && $toDate !== '') {
            $query->bind(':to_date', $toDate);
        }
        $query->execute();
        $row = $query->single();
        return (int) ($row['total'] ?? 0);
    }

    public function listForAdmin(
        int $limit,
        int $offset,
        ?string $keyword = null,
        ?int $userId = null,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $this->ensurePlanSchema();

        $sql = 'SELECT mp.id,
                       mp.user_id,
                       mp.plan_date,
                       mp.meal_type,
                       mp.dish_role,
                       mp.recipe_id,
                       mp.created_at,
                       u.name AS user_name,
                       u.email AS user_email,
                       r.title AS recipe_title
                FROM meal_plans mp
                INNER JOIN users u ON u.id = mp.user_id
                INNER JOIN recipes r ON r.id = mp.recipe_id
                WHERE 1=1';

        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= ' AND (
                        u.name LIKE :kw_name
                        OR u.email LIKE :kw_email
                        OR r.title LIKE :kw_title
                    )';
        }
        if ($userId !== null && $userId > 0) {
            $sql .= ' AND mp.user_id = :user_id';
        }
        if ($fromDate !== null && $fromDate !== '') {
            $sql .= ' AND mp.plan_date >= :from_date';
        }
        if ($toDate !== null && $toDate !== '') {
            $sql .= ' AND mp.plan_date <= :to_date';
        }

        $sql .= ' ORDER BY mp.plan_date DESC, mp.user_id ASC, FIELD(mp.meal_type, "breakfast", "lunch", "dinner"), mp.id DESC
                  LIMIT :limit OFFSET :offset';

        $query = $this->db->query($sql);
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $query->bind(':kw_name', $like)
                ->bind(':kw_email', $like)
                ->bind(':kw_title', $like);
        }
        if ($userId !== null && $userId > 0) {
            $query->bind(':user_id', $userId, PDO::PARAM_INT);
        }
        if ($fromDate !== null && $fromDate !== '') {
            $query->bind(':from_date', $fromDate);
        }
        if ($toDate !== null && $toDate !== '') {
            $query->bind(':to_date', $toDate);
        }

        $query->bind(':limit', $limit, PDO::PARAM_INT)
            ->bind(':offset', $offset, PDO::PARAM_INT)
            ->execute();

        return $query->resultSet();
    }

    public function deleteForAdmin(int $mealPlanId): bool
    {
        $this->ensurePlanSchema();
        return $this->db
            ->query('DELETE FROM meal_plans WHERE id = :id')
            ->bind(':id', $mealPlanId, PDO::PARAM_INT)
            ->execute();
    }
}
