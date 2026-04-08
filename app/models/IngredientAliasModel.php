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
        // Cleanup noisy legacy aliases.
        $this->db->query('DELETE FROM ingredient_aliases WHERE CHAR_LENGTH(alias_normalized) < 2')->execute();

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

        $tokens = array_values(array_filter(
            explode(' ', $normalized),
            static fn(string $t): bool => $t !== '' && strlen($t) >= 2
        ));
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
        $matchedAliases = [];
        foreach ($rows as $row) {
            $ingredientId = (int) ($row['ingredient_id'] ?? 0);
            if ($ingredientId > 0) {
                $ids[$ingredientId] = true;
            }
            $aliasNormalized = trim((string) ($row['alias_normalized'] ?? ''));
            if ($aliasNormalized !== '') {
                $matchedAliases[$aliasNormalized] = true;
            }
        }

        // Expand exact matches to close variants, e.g. "thit heo" -> "thit heo nac".
        if ($ids !== [] && $matchedAliases !== []) {
            $prefixClauses = [];
            $prefixValues = [];
            $prefixIndex = 0;
            foreach (array_keys($matchedAliases) as $matched) {
                if (strlen($matched) < 4) {
                    continue;
                }
                $param = ':x' . $prefixIndex++;
                $prefixClauses[] = 'alias_normalized LIKE ' . $param;
                $prefixValues[$param] = $matched . ' %';
            }

            if ($prefixClauses !== []) {
                $sqlExpand = 'SELECT ingredient_id
                              FROM ingredient_aliases
                              WHERE ' . implode(' OR ', $prefixClauses) . '
                              LIMIT :limit';

                $queryExpand = $this->db->query($sqlExpand);
                foreach ($prefixValues as $param => $value) {
                    $queryExpand->bind($param, $value);
                }
                $queryExpand->bind(':limit', $limit, PDO::PARAM_INT)->execute();

                foreach ($queryExpand->resultSet() as $row) {
                    $ingredientId = (int) ($row['ingredient_id'] ?? 0);
                    if ($ingredientId > 0) {
                        $ids[$ingredientId] = true;
                    }
                }
            }
        }

        // Fallback prefix match.
        if ($ids === []) {
            $likeClauses = [];
            $likeValues = [];
            foreach ($ngrams as $idx => $gram) {
                if (strlen($gram) < 3) {
                    continue;
                }
                $param = ':l' . $idx;
                $likeClauses[] = 'alias_normalized LIKE ' . $param;
                $likeValues[$param] = $gram . '%';
            }

            if ($likeClauses !== []) {
                $sqlLike = 'SELECT ingredient_id, alias_normalized
                            FROM ingredient_aliases
                            WHERE ' . implode(' OR ', $likeClauses) . '
                            ORDER BY CHAR_LENGTH(alias_normalized) ASC
                            LIMIT :limit';

                $queryLike = $this->db->query($sqlLike);
                foreach ($likeValues as $param => $value) {
                    $queryLike->bind($param, $value);
                }
                $queryLike->bind(':limit', $limit, PDO::PARAM_INT)->execute();

                $rowsLike = $queryLike->resultSet();
                foreach ($rowsLike as $row) {
                    $ingredientId = (int) ($row['ingredient_id'] ?? 0);
                    if ($ingredientId > 0) {
                        $ids[$ingredientId] = true;
                    }
                }
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

        $map = [
            '\\u00E0' => 'a', '\\u00E1' => 'a', '\\u1EA1' => 'a', '\\u1EA3' => 'a', '\\u00E3' => 'a',
            '\\u00E2' => 'a', '\\u1EA7' => 'a', '\\u1EA5' => 'a', '\\u1EAD' => 'a', '\\u1EA9' => 'a', '\\u1EAB' => 'a',
            '\\u0103' => 'a', '\\u1EB1' => 'a', '\\u1EAF' => 'a', '\\u1EB7' => 'a', '\\u1EB3' => 'a', '\\u1EB5' => 'a',
            '\\u00E8' => 'e', '\\u00E9' => 'e', '\\u1EB9' => 'e', '\\u1EBB' => 'e', '\\u1EBD' => 'e',
            '\\u00EA' => 'e', '\\u1EC1' => 'e', '\\u1EBF' => 'e', '\\u1EC7' => 'e', '\\u1EC3' => 'e', '\\u1EC5' => 'e',
            '\\u00EC' => 'i', '\\u00ED' => 'i', '\\u1ECB' => 'i', '\\u1EC9' => 'i', '\\u0129' => 'i',
            '\\u00F2' => 'o', '\\u00F3' => 'o', '\\u1ECD' => 'o', '\\u1ECF' => 'o', '\\u00F5' => 'o',
            '\\u00F4' => 'o', '\\u1ED3' => 'o', '\\u1ED1' => 'o', '\\u1ED9' => 'o', '\\u1ED5' => 'o', '\\u1ED7' => 'o',
            '\\u01A1' => 'o', '\\u1EDD' => 'o', '\\u1EDB' => 'o', '\\u1EE3' => 'o', '\\u1EDF' => 'o', '\\u1EE1' => 'o',
            '\\u00F9' => 'u', '\\u00FA' => 'u', '\\u1EE5' => 'u', '\\u1EE7' => 'u', '\\u0169' => 'u',
            '\\u01B0' => 'u', '\\u1EEB' => 'u', '\\u1EE9' => 'u', '\\u1EF1' => 'u', '\\u1EED' => 'u', '\\u1EEF' => 'u',
            '\\u1EF3' => 'y', '\\u00FD' => 'y', '\\u1EF5' => 'y', '\\u1EF7' => 'y', '\\u1EF9' => 'y',
            '\\u0111' => 'd',
        ];
        foreach ($map as $unicodeKey => $asciiValue) {
            $decoded = json_decode('"' . $unicodeKey . '"');
            if (is_string($decoded) && $decoded !== '') {
                $text = str_replace($decoded, $asciiValue, $text);
            }
        }

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