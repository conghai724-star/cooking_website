<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once APPROOT . '/app/models/Database.php';

function normalize_text(string $text): string
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

function contains_any(string $haystack, array $needles): bool
{
    foreach ($needles as $needle) {
        $needle = trim((string) $needle);
        if ($needle === '') {
            continue;
        }
        if (function_exists('mb_stripos')) {
            if (mb_stripos($haystack, $needle, 0, 'UTF-8') !== false) {
                return true;
            }
            continue;
        }
        if (stripos($haystack, $needle) !== false) {
            return true;
        }
    }

    return false;
}

try {
    $db = Database::getInstance();

    $db->query('SELECT tag_id, keyword_norm FROM tag_synonyms WHERE keyword_norm <> "" ORDER BY CHAR_LENGTH(keyword_norm) DESC')->execute();
    $synonyms = $db->resultSet();
    if ($synonyms === []) {
        echo "No tag synonyms found.\n";
        exit(0);
    }

    $db->query('SELECT id, slug FROM tags WHERE slug IN ("cay", "it_dau")')->execute();
    $specialTagMap = [];
    foreach ($db->resultSet() as $tagRow) {
        $slug = (string) ($tagRow['slug'] ?? '');
        $id = (int) ($tagRow['id'] ?? 0);
        if ($slug !== '' && $id > 0) {
            $specialTagMap[$slug] = $id;
        }
    }

    $db->query('SELECT id, title, description FROM recipes')->execute();
    $recipes = $db->resultSet();
    if ($recipes === []) {
        echo "No recipes found.\n";
        exit(0);
    }

    $assignedRecipes = 0;
    $insertedMappings = 0;

    foreach ($recipes as $recipe) {
        $recipeId = (int) ($recipe['id'] ?? 0);
        if ($recipeId <= 0) {
            continue;
        }

        $title = (string) ($recipe['title'] ?? '');
        $description = (string) ($recipe['description'] ?? '');
        $rawHaystack = function_exists('mb_strtolower') ? mb_strtolower($title . ' ' . $description, 'UTF-8') : strtolower($title . ' ' . $description);
        $haystack = normalize_text($title . ' ' . $description);
        if ($haystack === '' && trim($rawHaystack) === '') {
            continue;
        }

        $tagIds = [];
        foreach ($synonyms as $row) {
            $keywordNorm = trim((string) ($row['keyword_norm'] ?? ''));
            $tagId = (int) ($row['tag_id'] ?? 0);
            if ($keywordNorm === '' || $tagId <= 0) {
                continue;
            }

            if (str_contains($haystack, $keywordNorm)) {
                $tagIds[] = $tagId;
            }
        }

        // Heuristic fallback for noisy legacy text encoding.
        if (isset($specialTagMap['cay']) && contains_any($rawHaystack, ['ớt', 'ot', 'sa tế', 'sa te', 'cay', 'spicy', 'hot'])) {
            $tagIds[] = (int) $specialTagMap['cay'];
        }
        if (isset($specialTagMap['it_dau']) && contains_any($rawHaystack, ['ít dầu', 'it dau', 'không dầu', 'khong dau', 'nồi chiên không dầu', 'noi chien khong dau', 'air fry'])) {
            $tagIds[] = (int) $specialTagMap['it_dau'];
        }

        $tagIds = array_values(array_unique($tagIds));
        if ($tagIds === []) {
            continue;
        }

        $addedForRecipe = 0;
        foreach ($tagIds as $tagId) {
            $db->query('INSERT IGNORE INTO recipe_tags (recipe_id, tag_id, created_at) VALUES (:recipe_id, :tag_id, NOW())')
                ->bind(':recipe_id', $recipeId)
                ->bind(':tag_id', $tagId)
                ->execute();
            $addedForRecipe += $db->rowCount();
        }

        if ($addedForRecipe > 0) {
            $assignedRecipes++;
            $insertedMappings += $addedForRecipe;
        }
    }

    echo "Auto-tag completed.\n";
    echo "- Recipes updated: {$assignedRecipes}\n";
    echo "- New recipe_tags rows: {$insertedMappings}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Auto-tag failed: ' . $e->getMessage() . "\n");
    exit(1);
}
