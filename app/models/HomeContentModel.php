<?php

declare(strict_types=1);

class HomeContentModel extends Model
{
    private bool $ready = false;

    private function ensureTables(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS home_banners (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                subtitle TEXT NULL,
                image_url VARCHAR(255) NULL,
                cta_text VARCHAR(80) NULL,
                cta_url VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                start_at DATETIME NULL,
                end_at DATETIME NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )")->execute();

        $this->db->query("CREATE TABLE IF NOT EXISTS home_featured_recipes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipe_id INT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_home_featured_recipe (recipe_id),
                INDEX idx_home_featured_sort (sort_order, id)
            )")->execute();

        $this->db->query("CREATE TABLE IF NOT EXISTS home_recipe_of_day (
                id INT AUTO_INCREMENT PRIMARY KEY,
                for_date DATE NOT NULL,
                recipe_id INT NOT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_home_recipe_of_day_date (for_date),
                INDEX idx_home_recipe_of_day_recipe (recipe_id)
            )")->execute();

        $this->ready = true;
    }

    public function getActiveBanner(): ?array
    {
        $this->ensureTables();
        $this->db->query("SELECT *
                          FROM home_banners
                          WHERE is_active = 1
                            AND (start_at IS NULL OR start_at <= NOW())
                            AND (end_at IS NULL OR end_at >= NOW())
                          ORDER BY id DESC
                          LIMIT 1")
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }

    public function saveBanner(
        string $title,
        ?string $subtitle,
        ?string $imageUrl,
        ?string $ctaText,
        ?string $ctaUrl,
        bool $isActive
    ): bool {
        $this->ensureTables();
        if (trim($title) === '') {
            return false;
        }

        // Keep one active banner for home simplicity.
        $this->db->query('UPDATE home_banners SET is_active = 0')->execute();
        return $this->db
            ->query('INSERT INTO home_banners
                     (title, subtitle, image_url, cta_text, cta_url, is_active, created_at)
                     VALUES
                     (:title, :subtitle, :image_url, :cta_text, :cta_url, :is_active, NOW())')
            ->bind(':title', mb_substr($title, 0, 255))
            ->bind(':subtitle', $subtitle)
            ->bind(':image_url', $imageUrl)
            ->bind(':cta_text', $ctaText)
            ->bind(':cta_url', $ctaUrl)
            ->bind(':is_active', $isActive ? 1 : 0, PDO::PARAM_INT)
            ->execute();
    }

    public function syncFeaturedRecipes(array $recipeIds): bool
    {
        $this->ensureTables();
        $ids = array_values(array_unique(array_map(static fn($v): int => (int) $v, $recipeIds)));
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));

        $this->db->query('DELETE FROM home_featured_recipes')->execute();
        $ok = true;
        foreach ($ids as $index => $id) {
            $ok = $ok && $this->db
                ->query('INSERT INTO home_featured_recipes (recipe_id, sort_order, is_active, created_at)
                         VALUES (:recipe_id, :sort_order, 1, NOW())')
                ->bind(':recipe_id', $id, PDO::PARAM_INT)
                ->bind(':sort_order', $index + 1, PDO::PARAM_INT)
                ->execute();
        }
        return $ok;
    }

    public function listFeaturedRecipeIds(): array
    {
        $this->ensureTables();
        $this->db->query('SELECT recipe_id
                          FROM home_featured_recipes
                          WHERE is_active = 1
                          ORDER BY sort_order ASC, id ASC')
            ->execute();
        return array_map(static fn(array $r): int => (int) ($r['recipe_id'] ?? 0), $this->db->resultSet());
    }

    public function getFeaturedRecipes(int $limit = 6): array
    {
        $this->ensureTables();
        $this->db->query('SELECT r.*, u.name AS author_name,
                          COALESCE((
                              SELECT GROUP_CONCAT(DISTINCT LOWER(t.slug) SEPARATOR ",")
                              FROM recipe_tags rt
                              INNER JOIN tags t ON t.id = rt.tag_id
                              WHERE rt.recipe_id = r.id
                          ), "") AS tag_slugs
                          FROM home_featured_recipes hf
                          INNER JOIN recipes r ON r.id = hf.recipe_id
                          LEFT JOIN users u ON u.id = r.user_id
                          WHERE hf.is_active = 1
                            AND r.status = "approved"
                          ORDER BY hf.sort_order ASC, hf.id ASC
                          LIMIT :limit')
            ->bind(':limit', max(1, $limit), PDO::PARAM_INT)
            ->execute();
        return $this->db->resultSet();
    }

    public function setRecipeOfDay(string $forDate, int $recipeId): bool
    {
        $this->ensureTables();
        if ($recipeId <= 0) {
            return false;
        }
        return $this->db
            ->query('INSERT INTO home_recipe_of_day (for_date, recipe_id, created_at)
                     VALUES (:for_date, :recipe_id, NOW())
                     ON DUPLICATE KEY UPDATE recipe_id = VALUES(recipe_id), updated_at = NOW()')
            ->bind(':for_date', $forDate)
            ->bind(':recipe_id', $recipeId, PDO::PARAM_INT)
            ->execute();
    }

    public function getRecipeOfDay(?string $forDate = null): ?array
    {
        $this->ensureTables();
        $date = $forDate ?: date('Y-m-d');
        $this->db->query('SELECT r.*, u.name AS author_name, d.for_date
                          FROM home_recipe_of_day d
                          INNER JOIN recipes r ON r.id = d.recipe_id
                          LEFT JOIN users u ON u.id = r.user_id
                          WHERE d.for_date = :for_date
                          LIMIT 1')
            ->bind(':for_date', $date)
            ->execute();
        $row = $this->db->single();
        return is_array($row) ? $row : null;
    }
}
