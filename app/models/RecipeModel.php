<?php

declare(strict_types=1);

class RecipeModel extends Model
{
    private ?array $recipeColumns = null;
    private ?array $ingredientColumns = null;
    private bool $reportConstraintsReady = false;

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

    private function publicRecipeConditions(string $alias = 'r'): array
    {
        $conditions = [];
        $columns = $this->getRecipeColumns();
        if (array_key_exists('deleted_at', $columns)) {
            $conditions[] = $alias . '.deleted_at IS NULL';
        }
        if (array_key_exists('status', $columns)) {
            $conditions[] = $alias . '.status = "approved"';
        }
        if (array_key_exists('user_state', $columns)) {
            $conditions[] = '(' . $alias . '.user_state IS NULL OR ' . $alias . '.user_state = "published")';
        }
        return $conditions;
    }

    public function all(?string $status = null): array
    {
        $sql = 'SELECT r.*, u.name AS author_name, c.name AS category_name
                FROM recipes r
                LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN categories c ON c.id = r.category_id';
        $conditions = [];
        if (array_key_exists('deleted_at', $this->getRecipeColumns())) {
            $conditions[] = 'r.deleted_at IS NULL';
        }

        if ($status !== null && array_key_exists('status', $this->getRecipeColumns())) {
            $conditions[] = 'r.status = :status';
            if (array_key_exists('user_state', $this->getRecipeColumns()) && $status === 'approved') {
                $conditions[] = '(r.user_state IS NULL OR r.user_state = "published")';
            }
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY r.id DESC';

        $this->db->query($sql);
        if ($status !== null && array_key_exists('status', $this->getRecipeColumns())) {
            $this->db->bind(':status', $status);
        }
        $this->db->execute();
        return $this->db->resultSet();
    }

    public function countApproved(?string $keyword = null): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM recipes r';
        $conditions = [];
        if (array_key_exists('deleted_at', $this->getRecipeColumns())) {
            $conditions[] = 'r.deleted_at IS NULL';
        }
        if (array_key_exists('status', $this->getRecipeColumns())) {
            $conditions[] = 'r.status = "approved"';
        }
        if (array_key_exists('user_state', $this->getRecipeColumns())) {
            $conditions[] = '(r.user_state IS NULL OR r.user_state = "published")';
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(r.title LIKE :keyword_title OR r.description LIKE :keyword_desc)';
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $this->db->query($sql);
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $this->db->bind(':keyword_title', $like);
            $this->db->bind(':keyword_desc', $like);
        }
        $this->db->execute();
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function allApprovedPaged(int $limit, int $offset, ?string $keyword = null): array
    {
        $sql = 'SELECT r.*, u.name AS author_name, c.name AS category_name
                FROM recipes r
                LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN categories c ON c.id = r.category_id';

        $conditions = [];
        if (array_key_exists('deleted_at', $this->getRecipeColumns())) {
            $conditions[] = 'r.deleted_at IS NULL';
        }
        if (array_key_exists('status', $this->getRecipeColumns())) {
            $conditions[] = 'r.status = "approved"';
        }
        if (array_key_exists('user_state', $this->getRecipeColumns())) {
            $conditions[] = '(r.user_state IS NULL OR r.user_state = "published")';
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(r.title LIKE :keyword_title OR r.description LIKE :keyword_desc)';
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY r.id DESC LIMIT :limit OFFSET :offset';

        $this->db->query($sql)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->bind(':offset', $offset, PDO::PARAM_INT);
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $this->db->bind(':keyword_title', $like);
            $this->db->bind(':keyword_desc', $like);
        }
        $this->db->execute();

        return $this->db->resultSet();
    }

    public function countPlannerBank(?string $keyword = null, ?string $difficulty = null, ?int $maxCookingTime = null): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM recipes r';
        $conditions = $this->publicRecipeConditions('r');

        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(r.title LIKE :keyword_title OR r.description LIKE :keyword_desc)';
        }
        if ($difficulty !== null && in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $conditions[] = 'r.difficulty = :difficulty';
        }
        if ($maxCookingTime !== null && $maxCookingTime > 0) {
            $conditions[] = 'r.cooking_time IS NOT NULL AND r.cooking_time <= :max_time';
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $this->db->query($sql);
        if ($keyword !== null && trim($keyword) !== '') {
            $keywordLike = '%' . trim($keyword) . '%';
            $this->db->bind(':keyword_title', $keywordLike);
            $this->db->bind(':keyword_desc', $keywordLike);
        }
        if ($difficulty !== null && in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $this->db->bind(':difficulty', $difficulty);
        }
        if ($maxCookingTime !== null && $maxCookingTime > 0) {
            $this->db->bind(':max_time', $maxCookingTime, PDO::PARAM_INT);
        }
        $this->db->execute();
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function plannerBankPaged(
        int $limit,
        int $offset,
        ?string $keyword = null,
        ?string $difficulty = null,
        ?int $maxCookingTime = null
    ): array {
        $sql = 'SELECT r.id, r.title, r.description, r.image, r.cooking_time, r.difficulty
                FROM recipes r';
        $conditions = $this->publicRecipeConditions('r');

        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(r.title LIKE :keyword_title OR r.description LIKE :keyword_desc)';
        }
        if ($difficulty !== null && in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $conditions[] = 'r.difficulty = :difficulty';
        }
        if ($maxCookingTime !== null && $maxCookingTime > 0) {
            $conditions[] = 'r.cooking_time IS NOT NULL AND r.cooking_time <= :max_time';
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY r.id DESC LIMIT :limit OFFSET :offset';

        $this->db->query($sql)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->bind(':offset', $offset, PDO::PARAM_INT);

        if ($keyword !== null && trim($keyword) !== '') {
            $keywordLike = '%' . trim($keyword) . '%';
            $this->db->bind(':keyword_title', $keywordLike);
            $this->db->bind(':keyword_desc', $keywordLike);
        }
        if ($difficulty !== null && in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $this->db->bind(':difficulty', $difficulty);
        }
        if ($maxCookingTime !== null && $maxCookingTime > 0) {
            $this->db->bind(':max_time', $maxCookingTime, PDO::PARAM_INT);
        }

        $this->db->execute();
        return $this->db->resultSet();
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

    public function findById(int $id): array|false
    {
        $sql = 'SELECT r.*, u.name AS author_name, c.name AS category_name
                FROM recipes r
                LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN categories c ON c.id = r.category_id
                WHERE r.id = :id';
        if (array_key_exists('deleted_at', $this->getRecipeColumns())) {
            $sql .= ' AND r.deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';

        $this->db->query($sql)->bind(':id', $id)->execute();
        return $this->db->single();
    }

    public function findOwnerIdAnyStatus(int $id): ?int
    {
        $this->db->query('SELECT user_id FROM recipes WHERE id = :id LIMIT 1')
            ->bind(':id', $id)
            ->execute();
        $row = $this->db->single();
        if (!is_array($row)) {
            return null;
        }
        $userId = (int) ($row['user_id'] ?? 0);
        return $userId > 0 ? $userId : null;
    }

    public function create(
        int $userId,
        string $title,
        string $description,
        ?int $categoryId,
        ?string $image = null,
        ?int $cookingTime = null,
        string $difficulty = 'easy',
        string $status = 'pending',
        ?string $userState = null
    ): int|false
    {
        $sql = 'INSERT INTO recipes (user_id, category_id, title, description, image, cooking_time, difficulty, created_at';
        $values = 'VALUES (:user_id, :category_id, :title, :description, :image, :cooking_time, :difficulty, NOW()';

        if (array_key_exists('status', $this->getRecipeColumns())) {
            $sql .= ', status';
            $values .= ', :status';
        }
        if ($userState !== null && array_key_exists('user_state', $this->getRecipeColumns())) {
            $sql .= ', user_state';
            $values .= ', :user_state';
        }

        $sql .= ') ' . $values . ')';

        $this->db->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':category_id', $categoryId)
            ->bind(':title', $title)
            ->bind(':description', $description)
            ->bind(':image', $image)
            ->bind(':cooking_time', $cookingTime)
            ->bind(':difficulty', $difficulty);

        if (array_key_exists('status', $this->getRecipeColumns())) {
            $this->db->bind(':status', $status);
        }
        if ($userState !== null && array_key_exists('user_state', $this->getRecipeColumns())) {
            $this->db->bind(':user_state', $userState);
        }

        $ok = $this->db->execute();

        if (!$ok) {
            return false;
        }

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $title, string $description, ?int $categoryId): bool
    {
        return $this->db
            ->query('UPDATE recipes SET title = :title, description = :description, category_id = :category_id WHERE id = :id')
            ->bind(':title', $title)
            ->bind(':description', $description)
            ->bind(':category_id', $categoryId)
            ->bind(':id', $id)
            ->execute();
    }

    public function updateDetailed(
        int $id,
        string $title,
        string $description,
        ?int $categoryId,
        ?int $cookingTime,
        string $difficulty,
        ?string $image
    ): bool {
        return $this->db
            ->query('UPDATE recipes
                     SET title = :title,
                         description = :description,
                         category_id = :category_id,
                         cooking_time = :cooking_time,
                         difficulty = :difficulty,
                         image = :image
                     WHERE id = :id')
            ->bind(':title', $title)
            ->bind(':description', $description)
            ->bind(':category_id', $categoryId)
            ->bind(':cooking_time', $cookingTime)
            ->bind(':difficulty', $difficulty)
            ->bind(':image', $image)
            ->bind(':id', $id)
            ->execute();
    }

    public function setStatus(int $id, string $status): bool
    {
        return $this->db
            ->query('UPDATE recipes SET status = :status WHERE id = :id')
            ->bind(':status', $status)
            ->bind(':id', $id)
            ->execute();
    }

    public function setUserState(int $id, string $state): bool
    {
        if (!array_key_exists('user_state', $this->getRecipeColumns())) {
            return false;
        }

        return $this->db
            ->query('UPDATE recipes SET user_state = :state WHERE id = :id')
            ->bind(':state', $state)
            ->bind(':id', $id)
            ->execute();
    }

    public function moveToDraft(int $recipeId, int $userId): bool
    {
        $sets = [];
        if (array_key_exists('status', $this->getRecipeColumns())) {
            $sets[] = 'status = "draft"';
        }
        if (array_key_exists('user_state', $this->getRecipeColumns())) {
            $sets[] = 'user_state = "draft"';
        }
        if ($sets === []) {
            return false;
        }

        $sql = 'UPDATE recipes SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :user_id';
        return $this->db
            ->query($sql)
            ->bind(':id', $recipeId)
            ->bind(':user_id', $userId)
            ->execute();
    }

    public function allForAdmin(): array
    {
        $sql = 'SELECT r.*, u.name AS author_name, c.name AS category_name
                FROM recipes r
                LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN categories c ON c.id = r.category_id';

        $conditions = [];
        if (array_key_exists('status', $this->getRecipeColumns())) {
            // Admin khĂ´ng cA�º§n thA�º¥y bĂ i nhĂ¡p.
            $conditions[] = '(r.status IS NULL OR r.status <> "draft")';
        }
        if (array_key_exists('user_state', $this->getRecipeColumns())) {
            // BĂ i A�‘Ă£ gA�»­i duyA�»‡t thA�°A�»ng cĂ³ user_state="completed", vA�º«n phA�º£i hiA�»‡n cho admin duyA�»‡t.
            $conditions[] = '(r.user_state IS NULL OR r.user_state <> "draft")';
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY r.id DESC';

        $this->db->query($sql)->execute();
        return $this->db->resultSet();
    }

    public function savedByUser(int $userId): array
    {
        $sql = 'SELECT r.*, u.name AS author_name, c.name AS category_name
                FROM saved_items s
                INNER JOIN recipes r ON r.id = s.item_id
                LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN categories c ON c.id = r.category_id
                WHERE s.user_id = :user_id AND s.item_type = "recipe"';
        if (array_key_exists('deleted_at', $this->getRecipeColumns())) {
            $sql .= ' AND r.deleted_at IS NULL';
        }

        if (array_key_exists('status', $this->getRecipeColumns())) {
            $sql .= ' AND r.status = "approved"';
        }
        if (array_key_exists('user_state', $this->getRecipeColumns())) {
            $sql .= ' AND (r.user_state IS NULL OR r.user_state = "published")';
        }

        $sql .= ' ORDER BY s.created_at DESC';

        $this->db->query($sql)
            ->bind(':user_id', $userId)
            ->execute();

        return $this->db->resultSet();
    }

    public function byUser(int $userId, ?string $status = null, ?string $userState = null): array
    {
        $sql = 'SELECT * FROM recipes WHERE user_id = :user_id';
        if (array_key_exists('deleted_at', $this->getRecipeColumns())) {
            $sql .= ' AND deleted_at IS NULL';
        }
        if ($status !== null && array_key_exists('status', $this->getRecipeColumns())) {
            $sql .= ' AND status = :status';
        }
        if ($userState !== null && array_key_exists('user_state', $this->getRecipeColumns())) {
            $sql .= ' AND user_state = :user_state';
        }
        $sql .= ' ORDER BY id DESC';

        $this->db->query($sql)
            ->bind(':user_id', $userId);
        if ($status !== null && array_key_exists('status', $this->getRecipeColumns())) {
            $this->db->bind(':status', $status);
        }
        if ($userState !== null && array_key_exists('user_state', $this->getRecipeColumns())) {
            $this->db->bind(':user_state', $userState);
        }
        $this->db->execute();

        return $this->db->resultSet();
    }

    public function byFollowedUsers(int $followerId): array
    {
        $sql = 'SELECT r.*, u.name AS author_name
                FROM recipes r
                INNER JOIN follows f ON f.following_id = r.user_id
                INNER JOIN users u ON u.id = r.user_id
                WHERE f.follower_id = :follower_id';
        if (array_key_exists('deleted_at', $this->getRecipeColumns())) {
            $sql .= ' AND r.deleted_at IS NULL';
        }
        $sql .= '
                ORDER BY r.id DESC';

        $this->db->query($sql)
            ->bind(':follower_id', $followerId)
            ->execute();

        return $this->db->resultSet();
    }

    public function findIngredientByName(string $name): array|false
    {
        if ($this->hasIngredientColumn('source')) {
            $sql = 'SELECT id, name FROM ingredients WHERE name = :name ORDER BY source = "library" DESC, id ASC LIMIT 1';
        } else {
            $sql = 'SELECT id, name FROM ingredients WHERE name = :name ORDER BY id ASC LIMIT 1';
        }

        $this->db->query($sql)
            ->bind(':name', $name)
            ->execute();

        return $this->db->single();
    }

    public function createIngredient(string $name): int|false
    {
        $hasStatus = $this->hasIngredientColumn('status');
        $hasSource = $this->hasIngredientColumn('source');

        if ($hasStatus && $hasSource) {
            $sql = 'INSERT INTO ingredients (name, status, source, created_at) VALUES (:name, "approved", "recipe", NOW())';
        } elseif ($hasStatus) {
            $sql = 'INSERT INTO ingredients (name, status, created_at) VALUES (:name, "approved", NOW())';
        } elseif ($hasSource) {
            $sql = 'INSERT INTO ingredients (name, source, created_at) VALUES (:name, "recipe", NOW())';
        } else {
            $sql = 'INSERT INTO ingredients (name, created_at) VALUES (:name, NOW())';
        }

        $ok = $this->db
            ->query($sql)
            ->bind(':name', $name)
            ->execute();

        if (!$ok) {
            return false;
        }

        return (int) $this->db->lastInsertId();
    }

    public function addRecipeIngredient(int $recipeId, int $ingredientId, ?string $quantity, ?string $unit): bool
    {
        return $this->db
            ->query('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit)
                     VALUES (:recipe_id, :ingredient_id, :quantity, :unit)
                     ON DUPLICATE KEY UPDATE
                        quantity = VALUES(quantity),
                        unit = VALUES(unit)')
            ->bind(':recipe_id', $recipeId)
            ->bind(':ingredient_id', $ingredientId)
            ->bind(':quantity', $quantity)
            ->bind(':unit', $unit)
            ->execute();
    }

    public function addRecipeStep(int $recipeId, int $stepNumber, string $content, ?string $image): bool
    {
        return $this->db
            ->query('INSERT INTO recipe_steps (recipe_id, step_number, content, image)
                     VALUES (:recipe_id, :step_number, :content, :image)')
            ->bind(':recipe_id', $recipeId)
            ->bind(':step_number', $stepNumber)
            ->bind(':content', $content)
            ->bind(':image', $image)
            ->execute();
    }

    public function clearRecipeIngredients(int $recipeId): bool
    {
        return $this->db
            ->query('DELETE FROM recipe_ingredients WHERE recipe_id = :recipe_id')
            ->bind(':recipe_id', $recipeId)
            ->execute();
    }

    public function clearRecipeSteps(int $recipeId): bool
    {
        return $this->db
            ->query('DELETE FROM recipe_steps WHERE recipe_id = :recipe_id')
            ->bind(':recipe_id', $recipeId)
            ->execute();
    }

    public function tagIdsByRecipe(int $recipeId): array
    {
        try {
            $this->db->query('SELECT tag_id FROM recipe_tags WHERE recipe_id = :recipe_id')
                ->bind(':recipe_id', $recipeId, PDO::PARAM_INT)
                ->execute();
        } catch (Throwable $e) {
            return [];
        }

        $rows = $this->db->resultSet();
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['tag_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public function syncRecipeTags(int $recipeId, array $tagIds): bool
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), static fn(int $v): bool => $v > 0)));

        try {
            $this->db->query('DELETE FROM recipe_tags WHERE recipe_id = :recipe_id')
                ->bind(':recipe_id', $recipeId, PDO::PARAM_INT)
                ->execute();
        } catch (Throwable $e) {
            return false;
        }

        if ($tagIds === []) {
            return true;
        }

        foreach ($tagIds as $tagId) {
            try {
                $this->db->query('INSERT INTO recipe_tags (recipe_id, tag_id, created_at)
                                  VALUES (:recipe_id, :tag_id, NOW())')
                    ->bind(':recipe_id', $recipeId, PDO::PARAM_INT)
                    ->bind(':tag_id', $tagId, PDO::PARAM_INT)
                    ->execute();
            } catch (Throwable $e) {
                // If migration has not been applied or tag is invalid, skip silently to avoid blocking recipe flow.
                continue;
            }
        }

        return true;
    }

    public function deleteOwned(int $recipeId, int $userId): bool
    {
        return $this->db
            ->query('DELETE FROM recipes WHERE id = :id AND user_id = :user_id')
            ->bind(':id', $recipeId)
            ->bind(':user_id', $userId)
            ->execute();
    }

    public function isApproved(int $recipeId): bool
    {
        if (!array_key_exists('status', $this->getRecipeColumns())) {
            return false;
        }

        $sql = 'SELECT status FROM recipes WHERE id = :id';
        if (array_key_exists('deleted_at', $this->getRecipeColumns())) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';

        $this->db->query($sql)
            ->bind(':id', $recipeId)
            ->execute();

        $row = $this->db->single();
        return (string) ($row['status'] ?? '') === 'approved';
    }

    public function isUsedInMealPlan(int $recipeId): bool
    {
        $this->db->query('SELECT 1 FROM meal_plans WHERE recipe_id = :recipe_id LIMIT 1')
            ->bind(':recipe_id', $recipeId)
            ->execute();

        return (bool) $this->db->single();
    }

    private function ensureDeletedAtColumn(): void
    {
        if (array_key_exists('deleted_at', $this->getRecipeColumns())) {
            return;
        }
        $this->db->query('ALTER TABLE recipes ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL')->execute();
        $this->recipeColumns = null;
        $this->getRecipeColumns();
    }

    public function restoreById(int $recipeId): bool
    {
        $this->ensureDeletedAtColumn();
        return $this->db
            ->query('UPDATE recipes SET deleted_at = NULL WHERE id = :id')
            ->bind(':id', $recipeId)
            ->execute();
    }

    public function deleteById(int $recipeId): bool
    {
        $this->ensureDeletedAtColumn();
        return $this->db
            ->query('UPDATE recipes SET deleted_at = NOW(), status = "rejected" WHERE id = :id')
            ->bind(':id', $recipeId)
            ->execute();
    }

    public function ingredientsByRecipe(int $recipeId): array
    {
        $sql = 'SELECT ri.id, ri.quantity, ri.unit, i.name AS ingredient_name
                FROM recipe_ingredients ri
                INNER JOIN ingredients i ON i.id = ri.ingredient_id
                WHERE ri.recipe_id = :recipe_id
                ORDER BY ri.id ASC';

        $this->db->query($sql)
            ->bind(':recipe_id', $recipeId)
            ->execute();

        return $this->db->resultSet();
    }

    public function stepsByRecipe(int $recipeId): array
    {
        $sql = 'SELECT id, step_number, content, image
                FROM recipe_steps
                WHERE recipe_id = :recipe_id
                ORDER BY step_number ASC, id ASC';

        $this->db->query($sql)
            ->bind(':recipe_id', $recipeId)
            ->execute();

        return $this->db->resultSet();
    }

    // ========== Save/Bookmark Functions ==========
    public function isSaved(int $userId, int $recipeId): bool
    {
        $this->db->query('SELECT 1 FROM saved_items WHERE user_id = :user_id AND item_id = :recipe_id AND item_type = "recipe" LIMIT 1')
            ->bind(':user_id', $userId)
            ->bind(':recipe_id', $recipeId)
            ->execute();

        return (bool) $this->db->single();
    }

    public function toggleSave(int $userId, int $recipeId): bool
    {
        // Check if already saved
        $this->db->query('SELECT id FROM saved_items WHERE user_id = :user_id AND item_id = :recipe_id AND item_type = "recipe" LIMIT 1')
            ->bind(':user_id', $userId)
            ->bind(':recipe_id', $recipeId)
            ->execute();

        $existing = $this->db->single();

        if ($existing) {
            // Remove saved item
            return $this->db
                ->query('DELETE FROM saved_items WHERE user_id = :user_id AND item_id = :recipe_id AND item_type = "recipe"')
                ->bind(':user_id', $userId)
                ->bind(':recipe_id', $recipeId)
                ->execute();
        } else {
            // Add saved item
            return $this->db
                ->query('INSERT INTO saved_items (user_id, item_id, item_type, created_at) VALUES (:user_id, :recipe_id, "recipe", NOW())')
                ->bind(':user_id', $userId)
                ->bind(':recipe_id', $recipeId)
                ->execute();
        }
    }

    // ========== Report Functions ==========
    public function saveReport(int $reporterId, int $recipeId, string $reason): bool
    {
        $this->ensureReportConstraints();

        $this->db->query("SELECT 1
                          FROM reports
                          WHERE reporter_id = :reporter_id
                            AND target_type = 'recipe'
                            AND target_id = :target_id
                          LIMIT 1")
            ->bind(':reporter_id', $reporterId, PDO::PARAM_INT)
            ->bind(':target_id', $recipeId, PDO::PARAM_INT)
            ->execute();
        if ($this->db->single()) {
            return false;
        }

        try {
            return $this->db
                ->query("INSERT INTO reports (reporter_id, target_type, target_id, reason, status, created_at)
                         VALUES (:reporter_id, 'recipe', :target_id, :reason, 'pending', NOW())")
                ->bind(':reporter_id', $reporterId, PDO::PARAM_INT)
                ->bind(':target_id', $recipeId, PDO::PARAM_INT)
                ->bind(':reason', $reason)
                ->execute();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    private function ensureReportConstraints(): void
    {
        if ($this->reportConstraintsReady) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS reports (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            reporter_id INT NOT NULL,
                            recipe_id INT NULL,
                            target_type VARCHAR(20) NULL,
                            target_id INT NULL,
                            reason TEXT NOT NULL,
                            details TEXT NULL,
                            status ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
                            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            INDEX idx_reports_recipe (recipe_id),
                            INDEX idx_reports_reporter (reporter_id),
                            INDEX idx_reports_target (target_type, target_id)
                        )")->execute();

        $this->db->query("SHOW COLUMNS FROM reports LIKE 'target_type'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE reports ADD COLUMN target_type VARCHAR(20) NULL AFTER recipe_id")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM reports LIKE 'target_id'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE reports ADD COLUMN target_id INT NULL AFTER target_type")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM reports LIKE 'details'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE reports ADD COLUMN details TEXT NULL AFTER reason")->execute();
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

        // Backfill legacy recipe reports to polymorphic columns.
        $this->db->query("UPDATE reports
                          SET target_type = 'recipe', target_id = recipe_id
                          WHERE (target_type IS NULL OR target_type = '')
                            AND recipe_id IS NOT NULL")
            ->execute();

        $this->db->query("SHOW INDEX FROM reports WHERE Key_name = 'idx_reports_target'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE reports ADD INDEX idx_reports_target (target_type, target_id)")->execute();
        }

        $this->db->query("SHOW INDEX FROM reports WHERE Key_name = 'uq_reports_once_target'")->execute();
        if (!$this->db->single()) {
            $this->db->query('DELETE r1 FROM reports r1
                              INNER JOIN reports r2
                                ON r1.reporter_id = r2.reporter_id
                               AND COALESCE(r1.target_type, "recipe") = COALESCE(r2.target_type, "recipe")
                               AND COALESCE(r1.target_id, r1.recipe_id) = COALESCE(r2.target_id, r2.recipe_id)
                               AND r1.id > r2.id')
                ->execute();
            $this->db->query("ALTER TABLE reports
                              ADD UNIQUE KEY uq_reports_once_target (reporter_id, target_type, target_id)")
                ->execute();
        }

        $this->reportConstraintsReady = true;
    }

    public function allReportsForAdmin(?string $status = null): array
    {
        $this->ensureReportConstraints();
        $hasDeletedAt = array_key_exists('deleted_at', $this->getRecipeColumns());

        $sql = 'SELECT r.id,
                       r.target_id AS recipe_id,
                       r.reporter_id,
                       r.reason,
                       r.status,
                       r.created_at,
                       u.name AS reporter_name,
                       rc.title AS recipe_title,
                       rc.status AS recipe_status,
                       ' . ($hasDeletedAt ? 'rc.deleted_at' : 'NULL') . ' AS recipe_deleted_at,
                       rc.user_id AS target_user_id
                FROM reports r
                LEFT JOIN users u ON u.id = r.reporter_id
                LEFT JOIN recipes rc ON rc.id = r.target_id
                WHERE r.target_type = "recipe"';
        if ($status !== null && $status !== '') {
            $sql .= ' AND r.status = :status';
        }
        $sql .= ' ORDER BY r.id DESC';

        $query = $this->db->query($sql);
        if ($status !== null && $status !== '') {
            $query->bind(':status', $status);
        }
        $query->execute();
        return $query->resultSet();
    }

    public function updateReportStatus(int $reportId, string $status): bool
    {
        if (!in_array($status, ['pending', 'reviewed', 'resolved'], true)) {
            return false;
        }
        $this->ensureReportConstraints();
        return $this->db
            ->query("UPDATE reports
                     SET status = :status
                     WHERE id = :id
                       AND target_type = 'recipe'")
            ->bind(':status', $status)
            ->bind(':id', $reportId)
            ->execute();
    }
    public function recommendForChat(
        ?string $meal = null,
        ?int $maxCalories = null,
        ?string $keyword = null,
        array $allergies = [],
        int $limit = 5
    ): array {
        $limit = max(1, min(20, $limit));

        $keywordFilter = $this->buildKeywordFilterParts($keyword, ['r.title', 'r.description', 'i.name'], 'chat_kwf');
        $keywordScore = $this->buildKeywordScoreParts($keyword, ['r.title', 'r.description', 'i.name'], 'chat_kws');

        $sql = 'SELECT r.id, r.title, r.description, r.image, COALESCE(SUM(COALESCE(inu.calories, 0)), 0) AS estimated_kcal,
                       ' . $keywordScore['sql'] . ' AS keyword_score
                FROM recipes r
                LEFT JOIN recipe_ingredients ri ON ri.recipe_id = r.id
                LEFT JOIN ingredients i ON i.id = ri.ingredient_id
                LEFT JOIN ingredient_nutrition inu ON inu.ingredient_id = i.id';

        $conditions = $this->publicRecipeConditions('r');

        if ($keywordFilter['sql'] !== '') {
            $conditions[] = '(' . $keywordFilter['sql'] . ')';
        }

        if ($meal !== null && trim($meal) !== '') {
            $conditions[] = '(r.title LIKE :meal_kw OR r.description LIKE :meal_kw)';
        }

        foreach ($allergies as $idx => $allergy) {
            $key = ':allergy_' . $idx;
            $conditions[] = 'NOT EXISTS (
                SELECT 1 FROM recipe_ingredients ri2
                INNER JOIN ingredients i2 ON i2.id = ri2.ingredient_id
                WHERE ri2.recipe_id = r.id AND i2.name LIKE ' . $key . '
            )';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY r.id, r.title, r.description, r.image';

        if ($maxCalories !== null && $maxCalories > 0) {
            $sql .= ' HAVING estimated_kcal <= :max_calories';
        }

        $sql .= ' ORDER BY keyword_score DESC, estimated_kcal ASC, r.id DESC LIMIT :limit';

        $query = $this->db->query($sql)
            ->bind(':limit', $limit, PDO::PARAM_INT);

        $this->bindLikeParams($query, $keywordFilter['bindings']);
        $this->bindLikeParams($query, $keywordScore['bindings']);

        if ($meal !== null && trim($meal) !== '') {
            $mealLike = '%' . trim($meal) . '%';
            $query->bind(':meal_kw', $mealLike);
        }

        foreach ($allergies as $idx => $allergy) {
            $query->bind(':allergy_' . $idx, '%' . trim((string) $allergy) . '%');
        }

        if ($maxCalories !== null && $maxCalories > 0) {
            $query->bind(':max_calories', $maxCalories, PDO::PARAM_INT);
        }

        $query->execute();
        return $query->resultSet();
    }

    public function recommendByIngredientIds(
        array $ingredientIds,
        ?int $maxCalories = null,
        ?string $keyword = null,
        array $allergies = [],
        int $limit = 10
    ): array {
        $ingredientIds = array_values(array_unique(array_filter(array_map('intval', $ingredientIds), static fn(int $v): bool => $v > 0)));
        if ($ingredientIds === []) {
            return [];
        }

        $limit = max(1, min(50, $limit));

        $candidatePlaceholders = [];
        foreach ($ingredientIds as $idx => $_id) {
            $candidatePlaceholders[] = ':cid' . $idx;
        }

        $keywordFilter = $this->buildKeywordFilterParts($keyword, ['r.title', 'r.description', 'i.name'], 'ing_kwf');
        $keywordScore = $this->buildKeywordScoreParts($keyword, ['r.title', 'r.description', 'i.name'], 'ing_kws');

        $sql = 'SELECT
                    r.id,
                    r.title,
                    r.description,
                    r.image,
                    COALESCE(nut.total_kcal, 0) AS estimated_kcal,
                    COUNT(DISTINCT CASE WHEN ri.ingredient_id IN (' . implode(', ', $candidatePlaceholders) . ') THEN ri.ingredient_id END) AS matched_count,
                    COUNT(DISTINCT ri.ingredient_id) AS total_ingredients,
                    ' . $keywordScore['sql'] . ' AS keyword_score
                FROM recipes r
                INNER JOIN recipe_ingredients ri ON ri.recipe_id = r.id
                LEFT JOIN ingredients i ON i.id = ri.ingredient_id
                LEFT JOIN (
                    SELECT ri2.recipe_id, SUM(COALESCE(inu.calories, 0)) AS total_kcal
                    FROM recipe_ingredients ri2
                    LEFT JOIN ingredient_nutrition inu ON inu.ingredient_id = ri2.ingredient_id
                    GROUP BY ri2.recipe_id
                ) nut ON nut.recipe_id = r.id';

        $conditions = $this->publicRecipeConditions('r');
        if ($keywordFilter['sql'] !== '') {
            $conditions[] = '(' . $keywordFilter['sql'] . ')';
        }
        foreach ($allergies as $idx => $allergy) {
            $conditions[] = 'NOT EXISTS (
                SELECT 1 FROM recipe_ingredients ari
                INNER JOIN ingredients ai ON ai.id = ari.ingredient_id
                WHERE ari.recipe_id = r.id
                  AND ai.name LIKE :allergy_' . $idx . '
            )';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY r.id, r.title, r.description, r.image, nut.total_kcal
                  HAVING matched_count > 0';
        if ($maxCalories !== null && $maxCalories > 0) {
            $sql .= ' AND estimated_kcal <= :max_calories';
        }

        $sql .= ' ) ranked
                  ORDER BY
                    ranked.matched_count DESC,
                    (ranked.matched_count / NULLIF(ranked.total_ingredients, 0)) DESC,
                    ranked.keyword_score DESC,
                    ranked.estimated_kcal ASC,
                    ranked.id DESC
                  LIMIT :limit';

        $sql = 'SELECT * FROM (' . $sql;

        $query = $this->db->query($sql);
        foreach ($ingredientIds as $idx => $ingredientId) {
            $query->bind(':cid' . $idx, $ingredientId, PDO::PARAM_INT);
        }
        $this->bindLikeParams($query, $keywordFilter['bindings']);
        $this->bindLikeParams($query, $keywordScore['bindings']);
        foreach ($allergies as $idx => $allergy) {
            $query->bind(':allergy_' . $idx, '%' . trim((string) $allergy) . '%');
        }
        if ($maxCalories !== null && $maxCalories > 0) {
            $query->bind(':max_calories', $maxCalories, PDO::PARAM_INT);
        }
        $query->bind(':limit', $limit, PDO::PARAM_INT)->execute();

        return $query->resultSet();
    }

    private function buildKeywordFilterParts(?string $keyword, array $fields, string $prefix): array
    {
        $tokens = $this->tokenizeKeyword($keyword);
        if ($tokens === []) {
            return ['sql' => '', 'bindings' => []];
        }

        $bindings = [];
        $orParts = [];
        foreach ($fields as $fieldIndex => $field) {
            $exactParam = ':' . $prefix . '_exact_' . $fieldIndex;
            $orParts[] = $field . ' LIKE ' . $exactParam;
            $bindings[$exactParam] = '%' . implode(' ', $tokens) . '%';

            foreach ($tokens as $tokenIndex => $token) {
                $tokenParam = ':' . $prefix . '_tok_' . $fieldIndex . '_' . $tokenIndex;
                $orParts[] = $field . ' LIKE ' . $tokenParam;
                $bindings[$tokenParam] = '%' . $token . '%';
            }
        }

        return [
            'sql' => implode(' OR ', $orParts),
            'bindings' => $bindings,
        ];
    }

    private function buildKeywordScoreParts(?string $keyword, array $fields, string $prefix): array
    {
        $tokens = $this->tokenizeKeyword($keyword);
        if ($tokens === []) {
            return ['sql' => '0', 'bindings' => []];
        }

        $exactWeights = [100, 40, 25];
        $tokenWeights = [16, 7, 5];
        $bindings = [];
        $parts = [];

        foreach ($fields as $fieldIndex => $field) {
            $exactWeight = (int) ($exactWeights[$fieldIndex] ?? 15);
            $tokenWeight = (int) ($tokenWeights[$fieldIndex] ?? 4);
            $exactParam = ':' . $prefix . '_exact_' . $fieldIndex;
            $parts[] = 'MAX(CASE WHEN ' . $field . ' LIKE ' . $exactParam . ' THEN ' . $exactWeight . ' ELSE 0 END)';
            $bindings[$exactParam] = '%' . implode(' ', $tokens) . '%';

            foreach ($tokens as $tokenIndex => $token) {
                $tokenParam = ':' . $prefix . '_tok_' . $fieldIndex . '_' . $tokenIndex;
                $parts[] = 'MAX(CASE WHEN ' . $field . ' LIKE ' . $tokenParam . ' THEN ' . $tokenWeight . ' ELSE 0 END)';
                $bindings[$tokenParam] = '%' . $token . '%';
            }
        }

        return [
            'sql' => implode(' + ', $parts),
            'bindings' => $bindings,
        ];
    }

    private function tokenizeKeyword(?string $keyword): array
    {
        $text = $this->normalizeKeywordText((string) ($keyword ?? ''));
        if ($text === '') {
            return [];
        }

        $tokens = preg_split('/\s+/', $text) ?: [];
        $tokens = array_values(array_unique(array_filter(
            array_map(static fn($v): string => trim((string) $v), $tokens),
            static fn(string $v): bool => $v !== '' && strlen($v) >= 2
        )));

        return array_slice($tokens, 0, 6);
    }

    private function normalizeKeywordText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = (string) preg_replace('/[^[:alnum:]\s]/u', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function bindLikeParams($query, array $bindings): void
    {
        foreach ($bindings as $name => $value) {
            $query->bind((string) $name, (string) $value);
        }
    }

    public function recommendByTagIds(
        array $tagIds,
        ?int $maxCalories = null,
        ?string $keyword = null,
        array $allergies = [],
        int $limit = 10
    ): array {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), static fn(int $v): bool => $v > 0)));
        if ($tagIds === []) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $tagPlaceholders = [];
        foreach ($tagIds as $idx => $_id) {
            $tagPlaceholders[] = ':tid' . $idx;
        }

        $sql = 'SELECT
                    r.id,
                    r.title,
                    r.description,
                    r.image,
                    COALESCE(nut.total_kcal, 0) AS estimated_kcal,
                    COUNT(DISTINCT CASE WHEN rt.tag_id IN (' . implode(', ', $tagPlaceholders) . ') THEN rt.tag_id END) AS matched_tag_count
                FROM recipes r
                INNER JOIN recipe_tags rt ON rt.recipe_id = r.id
                LEFT JOIN (
                    SELECT ri2.recipe_id, SUM(COALESCE(inu.calories, 0)) AS total_kcal
                    FROM recipe_ingredients ri2
                    LEFT JOIN ingredient_nutrition inu ON inu.ingredient_id = ri2.ingredient_id
                    GROUP BY ri2.recipe_id
                ) nut ON nut.recipe_id = r.id';

        $conditions = $this->publicRecipeConditions('r');
        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(r.title LIKE :kw_title OR r.description LIKE :kw_desc)';
        }
        foreach ($allergies as $idx => $allergy) {
            $conditions[] = 'NOT EXISTS (
                SELECT 1 FROM recipe_ingredients ari
                INNER JOIN ingredients ai ON ai.id = ari.ingredient_id
                WHERE ari.recipe_id = r.id
                  AND ai.name LIKE :allergy_' . $idx . '
            )';
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY r.id, r.title, r.description, r.image, nut.total_kcal
                  HAVING matched_tag_count > 0';
        if ($maxCalories !== null && $maxCalories > 0) {
            $sql .= ' AND estimated_kcal <= :max_calories';
        }

        $sql .= ' ORDER BY matched_tag_count DESC, estimated_kcal ASC, r.id DESC
                  LIMIT :limit';

        $query = $this->db->query($sql);
        foreach ($tagIds as $idx => $tagId) {
            $query->bind(':tid' . $idx, $tagId, PDO::PARAM_INT);
        }
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $query->bind(':kw_title', $like);
            $query->bind(':kw_desc', $like);
        }
        foreach ($allergies as $idx => $allergy) {
            $query->bind(':allergy_' . $idx, '%' . trim((string) $allergy) . '%');
        }
        if ($maxCalories !== null && $maxCalories > 0) {
            $query->bind(':max_calories', $maxCalories, PDO::PARAM_INT);
        }
        $query->bind(':limit', $limit, PDO::PARAM_INT)->execute();

        return $query->resultSet();
    }
}
