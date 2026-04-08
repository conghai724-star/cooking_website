<?php

declare(strict_types=1);

class IngredientAliasModel extends Model
{
    private bool $ready = false;

    private function ensureTable(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS ingredient_aliases (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ingredient_id INT NOT NULL,
                alias VARCHAR(255) NOT NULL,
                alias_normalized VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_ingredient_aliases_alias_normalized (alias_normalized),
                INDEX idx_ingredient_aliases_ingredient_id (ingredient_id)
            )')->execute();

        $this->ready = true;
    }

    public function syncDefaultAliasesFromIngredients(): void
    {
        $this->ensureTable();
        $this->db->query('SELECT id, name FROM ingredients')->execute();
        $rows = $this->db->resultSet();
        foreach ($rows as $row) {
            $ingredientId = (int) ($row['id'] ?? 0);
            $alias = trim((string) ($row['name'] ?? ''));
            if ($ingredientId <= 0 || $alias === '') {
                continue;
            }

            $normalized = $this->normalize($alias);
            if ($normalized === '') {
                continue;
            }

            $this->db->query(
                'INSERT INTO ingredient_aliases (ingredient_id, alias, alias_normalized)
                 VALUES (:ingredient_id, :alias, :alias_normalized)
                 ON DUPLICATE KEY UPDATE ingredient_id = VALUES(ingredient_id), alias = VALUES(alias)'
            )
                ->bind(':ingredient_id', $ingredientId)
                ->bind(':alias', $alias)
                ->bind(':alias_normalized', $normalized)
                ->execute();
        }
    }

    public function resolveIngredientIdsFromText(string $message, int $limit = 10): array
    {
        $this->ensureTable();
        $this->syncDefaultAliasesFromIngredients();

        $normalized = $this->normalize($message);
        if ($normalized === '') {
            return [];
        }

        $tokens = array_values(array_filter(explode(' ', $normalized), static fn(string $t): bool => $t !== ''));
        if ($tokens === []) {
            return [];
        }

        $ngrams = [];
        $tokenCount = count($tokens);
        for ($n = 1; $n <= 3; $n++) {
            for ($i = 0; $i <= $tokenCount - $n; $i++) {
                $ngrams[] = implode(' ', array_slice($tokens, $i, $n));
            }
        }
        $ngrams = array_values(array_unique($ngrams));
        if ($ngrams === []) {
            return [];
        }

        $placeholders = [];
        foreach ($ngrams as $idx => $_) {
            $placeholders[] = ':p' . $idx;
        }

        $sql = 'SELECT ingredient_id, alias_normalized
                FROM ingredient_aliases
                WHERE alias_normalized IN (' . implode(', ', $placeholders) . ')
                ORDER BY CHAR_LENGTH(alias_normalized) DESC
                LIMIT :limit';

        $query = $this->db->query($sql);
        foreach ($ngrams as $idx => $gram) {
            $query->bind(':p' . $idx, $gram);
        }
        $query->bind(':limit', $limit, PDO::PARAM_INT)->execute();

        $rows = $this->db->resultSet();
        $ids = [];
        foreach ($rows as $row) {
            $ingredientId = (int) ($row['ingredient_id'] ?? 0);
            if ($ingredientId > 0) {
                $ids[$ingredientId] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    private function normalize(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = str_replace(['đ', 'Đ'], ['d', 'd'], $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        }
        $text = (string) preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
