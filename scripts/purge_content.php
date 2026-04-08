<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once APPROOT . '/app/models/Database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script can only run in CLI.\n";
    exit(1);
}

$force = in_array('--force', $argv, true);
if (!$force) {
    echo "This script will permanently delete all recipes, tips, and ingredients data.\n";
    echo "Run again with --force to execute.\n";
    exit(0);
}

function tableExists(Database $db, string $table): bool
{
    $db->query('SELECT COUNT(*) AS total
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = :table')
        ->bind(':table', $table)
        ->execute();

    $row = $db->single();
    return (int) ($row['total'] ?? 0) > 0;
}

function columnExists(Database $db, string $table, string $column): bool
{
    $db->query('SELECT COUNT(*) AS total
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :table
                  AND column_name = :column')
        ->bind(':table', $table)
        ->bind(':column', $column)
        ->execute();

    $row = $db->single();
    return (int) ($row['total'] ?? 0) > 0;
}

function execDelete(Database $db, string $sql, array $params = []): int
{
    $db->query($sql)->execute($params);
    return $db->rowCount();
}

function resetAutoIncrement(Database $db, string $table): void
{
    $db->query('ALTER TABLE ' . $table . ' AUTO_INCREMENT = 1')->execute();
}

$deleted = [];

try {
    $db = Database::getInstance();

    $db->query('SET FOREIGN_KEY_CHECKS = 0')->execute();
    $db->query('START TRANSACTION')->execute();

    if (tableExists($db, 'recipe_ingredients')) {
        $deleted['recipe_ingredients'] = execDelete($db, 'DELETE FROM recipe_ingredients');
    }

    if (tableExists($db, 'recipe_steps')) {
        $deleted['recipe_steps'] = execDelete($db, 'DELETE FROM recipe_steps');
    }

    if (tableExists($db, 'ingredient_nutrition')) {
        $deleted['ingredient_nutrition'] = execDelete($db, 'DELETE FROM ingredient_nutrition');
    }

    if (tableExists($db, 'comments')) {
        $commentsDeleted = 0;
        if (columnExists($db, 'comments', 'content_type')) {
            $commentsDeleted += execDelete(
                $db,
                'DELETE FROM comments WHERE content_type IN (\'recipe\', \'tip\', \'ingredient\')'
            );
        }
        if (columnExists($db, 'comments', 'recipe_id')) {
            $commentsDeleted += execDelete($db, 'DELETE FROM comments WHERE recipe_id IS NOT NULL');
        }
        $deleted['comments'] = $commentsDeleted;
    }

    if (tableExists($db, 'ratings')) {
        $deleted['ratings'] = execDelete($db, 'DELETE FROM ratings');
    }

    if (tableExists($db, 'meal_plans')) {
        $deleted['meal_plans'] = execDelete($db, 'DELETE FROM meal_plans');
    }

    if (tableExists($db, 'saved_items') && columnExists($db, 'saved_items', 'item_type')) {
        $deleted['saved_items'] = execDelete(
            $db,
            'DELETE FROM saved_items WHERE item_type IN (\'recipe\', \'ingredient\')'
        );
    }

    if (tableExists($db, 'reports')) {
        $reportsDeleted = 0;
        if (columnExists($db, 'reports', 'target_type')) {
            $reportsDeleted += execDelete(
                $db,
                'DELETE FROM reports WHERE target_type IN (\'recipe\', \'tip\', \'ingredient\')'
            );
        }
        if (columnExists($db, 'reports', 'recipe_id')) {
            $reportsDeleted += execDelete($db, 'DELETE FROM reports WHERE recipe_id IS NOT NULL');
        }
        $deleted['reports'] = $reportsDeleted;
    }

    if (tableExists($db, 'tips')) {
        $deleted['tips'] = execDelete($db, 'DELETE FROM tips');
    }

    if (tableExists($db, 'recipes')) {
        $deleted['recipes'] = execDelete($db, 'DELETE FROM recipes');
    }

    if (tableExists($db, 'ingredients')) {
        $deleted['ingredients'] = execDelete($db, 'DELETE FROM ingredients');
    }

    $db->query('COMMIT')->execute();
    $db->query('SET FOREIGN_KEY_CHECKS = 1')->execute();

    foreach (['recipe_ingredients', 'recipe_tags', 'recipe_steps', 'ingredient_nutrition', 'tips', 'recipes', 'ingredients'] as $table) {
        if (tableExists($db, $table)) {
            resetAutoIncrement($db, $table);
        }
    }

    echo "Purge completed.\n";
    foreach ($deleted as $table => $count) {
        echo '- ' . $table . ': ' . $count . " rows deleted\n";
    }
    exit(0);
} catch (Throwable $e) {
    try {
        if (isset($db)) {
            $db->query('ROLLBACK')->execute();
            $db->query('SET FOREIGN_KEY_CHECKS = 1')->execute();
        }
    } catch (Throwable $_) {
        // ignore secondary failure
    }

    fwrite(STDERR, 'Purge failed: ' . $e->getMessage() . "\n");
    exit(1);
}
