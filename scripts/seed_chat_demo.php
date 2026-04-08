<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once APPROOT . '/app/models/Database.php';

function now(): string
{
    return date('Y-m-d H:i:s');
}

function tableColumns(Database $db, string $table): array
{
    $db->query('SHOW COLUMNS FROM ' . $table)->execute();
    $rows = $db->resultSet();
    $cols = [];
    foreach ($rows as $row) {
        $field = (string) ($row['Field'] ?? '');
        if ($field !== '') {
            $cols[$field] = true;
        }
    }
    return $cols;
}

function hasCol(array $cols, string $name): bool
{
    return isset($cols[$name]);
}

function ensureUser(Database $db, array $cols, string $name, string $email, string $password, string $role = 'user'): int
{
    $db->query('SELECT id FROM users WHERE email = :email LIMIT 1')
        ->bind(':email', $email)
        ->execute();
    $found = $db->single();
    if (is_array($found) && isset($found['id'])) {
        return (int) $found['id'];
    }

    $insertCols = ['name', 'email', 'password'];
    $insertVals = [':name', ':email', ':password'];
    if (hasCol($cols, 'role')) {
        $insertCols[] = 'role';
        $insertVals[] = ':role';
    }
    if (hasCol($cols, 'status')) {
        $insertCols[] = 'status';
        $insertVals[] = ':status';
    }
    if (hasCol($cols, 'created_at')) {
        $insertCols[] = 'created_at';
        $insertVals[] = ':created_at';
    }

    $sql = 'INSERT INTO users (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
    $db->query($sql)
        ->bind(':name', $name)
        ->bind(':email', $email)
        ->bind(':password', password_hash($password, PASSWORD_DEFAULT));

    if (hasCol($cols, 'role')) {
        $db->bind(':role', $role);
    }
    if (hasCol($cols, 'status')) {
        $db->bind(':status', 'active');
    }
    if (hasCol($cols, 'created_at')) {
        $db->bind(':created_at', now());
    }

    $db->execute();
    return (int) $db->lastInsertId();
}

function ensureIngredient(Database $db, array $cols, string $name): int
{
    $db->query('SELECT id FROM ingredients WHERE name = :name ORDER BY id ASC LIMIT 1')
        ->bind(':name', $name)
        ->execute();
    $found = $db->single();
    if (is_array($found) && isset($found['id'])) {
        return (int) $found['id'];
    }

    $insertCols = ['name'];
    $insertVals = [':name'];
    if (hasCol($cols, 'status')) {
        $insertCols[] = 'status';
        $insertVals[] = ':status';
    }
    if (hasCol($cols, 'source')) {
        $insertCols[] = 'source';
        $insertVals[] = ':source';
    }
    if (hasCol($cols, 'created_at')) {
        $insertCols[] = 'created_at';
        $insertVals[] = ':created_at';
    }

    $sql = 'INSERT INTO ingredients (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
    $db->query($sql)->bind(':name', $name);
    if (hasCol($cols, 'status')) {
        $db->bind(':status', 'approved');
    }
    if (hasCol($cols, 'source')) {
        $db->bind(':source', 'library');
    }
    if (hasCol($cols, 'created_at')) {
        $db->bind(':created_at', now());
    }
    $db->execute();
    return (int) $db->lastInsertId();
}

function upsertNutrition(Database $db, int $ingredientId, float $calories, float $protein, float $fat, float $carb): void
{
    $sql = 'INSERT INTO ingredient_nutrition (ingredient_id, calories, protein, fat, carb)
            VALUES (:ingredient_id, :calories, :protein, :fat, :carb)
            ON DUPLICATE KEY UPDATE
                calories = VALUES(calories),
                protein = VALUES(protein),
                fat = VALUES(fat),
                carb = VALUES(carb)';
    $db->query($sql)
        ->bind(':ingredient_id', $ingredientId)
        ->bind(':calories', $calories)
        ->bind(':protein', $protein)
        ->bind(':fat', $fat)
        ->bind(':carb', $carb)
        ->execute();
}

function ensureRecipe(Database $db, array $cols, int $userId, string $title, string $description, int $cookingTime, string $difficulty): int
{
    $sql = 'SELECT id FROM recipes WHERE title = :title';
    if (hasCol($cols, 'deleted_at')) {
        $sql .= ' AND deleted_at IS NULL';
    }
    $sql .= ' ORDER BY id ASC LIMIT 1';
    $db->query($sql)->bind(':title', $title)->execute();
    $found = $db->single();
    if (is_array($found) && isset($found['id'])) {
        return (int) $found['id'];
    }

    $insertCols = ['user_id', 'title', 'description'];
    $insertVals = [':user_id', ':title', ':description'];

    if (hasCol($cols, 'cooking_time')) {
        $insertCols[] = 'cooking_time';
        $insertVals[] = ':cooking_time';
    }
    if (hasCol($cols, 'difficulty')) {
        $insertCols[] = 'difficulty';
        $insertVals[] = ':difficulty';
    }
    if (hasCol($cols, 'status')) {
        $insertCols[] = 'status';
        $insertVals[] = ':status';
    }
    if (hasCol($cols, 'user_state')) {
        $insertCols[] = 'user_state';
        $insertVals[] = ':user_state';
    }
    if (hasCol($cols, 'created_at')) {
        $insertCols[] = 'created_at';
        $insertVals[] = ':created_at';
    }

    $insertSql = 'INSERT INTO recipes (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
    $db->query($insertSql)
        ->bind(':user_id', $userId)
        ->bind(':title', $title)
        ->bind(':description', $description);

    if (hasCol($cols, 'cooking_time')) {
        $db->bind(':cooking_time', $cookingTime);
    }
    if (hasCol($cols, 'difficulty')) {
        $db->bind(':difficulty', $difficulty);
    }
    if (hasCol($cols, 'status')) {
        $db->bind(':status', 'approved');
    }
    if (hasCol($cols, 'user_state')) {
        $db->bind(':user_state', 'published');
    }
    if (hasCol($cols, 'created_at')) {
        $db->bind(':created_at', now());
    }

    $db->execute();
    return (int) $db->lastInsertId();
}

function upsertRecipeIngredient(Database $db, int $recipeId, int $ingredientId, string $quantity, string $unit): void
{
    $sql = 'INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit)
            VALUES (:recipe_id, :ingredient_id, :quantity, :unit)
            ON DUPLICATE KEY UPDATE
                quantity = VALUES(quantity),
                unit = VALUES(unit)';
    $db->query($sql)
        ->bind(':recipe_id', $recipeId)
        ->bind(':ingredient_id', $ingredientId)
        ->bind(':quantity', $quantity)
        ->bind(':unit', $unit)
        ->execute();
}

try {
    $db = Database::getInstance();
    $userCols = tableColumns($db, 'users');
    $recipeCols = tableColumns($db, 'recipes');
    $ingredientCols = tableColumns($db, 'ingredients');

    $adminId = ensureUser($db, $userCols, 'Admin Demo', 'admin_demo@cooking.local', '123456', 'super_admin');
    $userId = ensureUser($db, $userCols, 'User Demo', 'user_demo@cooking.local', '123456', 'user');

    $ingredients = [
        ['Oat', 389, 16.9, 6.9, 66.3],
        ['Egg', 155, 13, 11, 1.1],
        ['Chicken Breast', 165, 31, 3.6, 0],
        ['Salmon', 208, 20, 13, 0],
        ['Sweet Potato', 86, 1.6, 0.1, 20],
        ['Tomato', 18, 0.9, 0.2, 3.9],
        ['Lettuce', 15, 1.4, 0.2, 2.9],
        ['Pork Lean', 242, 27, 14, 0],
    ];

    $ingredientIds = [];
    foreach ($ingredients as [$name, $cal, $pro, $fat, $carb]) {
        $iid = ensureIngredient($db, $ingredientCols, $name);
        upsertNutrition($db, $iid, (float) $cal, (float) $pro, (float) $fat, (float) $carb);
        $ingredientIds[$name] = $iid;
    }

    $recipes = [
        [
            'title' => 'Breakfast Oat Egg Bowl',
            'desc' => 'Light breakfast with oat and egg for weight-loss plan.',
            'time' => 15,
            'difficulty' => 'easy',
            'ingredients' => [
                ['Oat', '60', 'g'],
                ['Egg', '1', 'pcs'],
                ['Tomato', '80', 'g'],
            ],
        ],
        [
            'title' => 'Lunch Chicken Sweet Potato',
            'desc' => 'Balanced lunch with chicken breast and sweet potato.',
            'time' => 25,
            'difficulty' => 'easy',
            'ingredients' => [
                ['Chicken Breast', '150', 'g'],
                ['Sweet Potato', '180', 'g'],
                ['Lettuce', '80', 'g'],
            ],
        ],
        [
            'title' => 'Dinner Salmon Salad',
            'desc' => 'Low-calorie dinner with salmon and fresh salad.',
            'time' => 20,
            'difficulty' => 'easy',
            'ingredients' => [
                ['Salmon', '120', 'g'],
                ['Lettuce', '120', 'g'],
                ['Tomato', '100', 'g'],
            ],
        ],
        [
            'title' => 'Lean Pork Tomato Stir Fry',
            'desc' => 'Pork-based meal for users preferring pork ingredient.',
            'time' => 18,
            'difficulty' => 'medium',
            'ingredients' => [
                ['Pork Lean', '120', 'g'],
                ['Tomato', '120', 'g'],
            ],
        ],
    ];

    foreach ($recipes as $recipe) {
        $rid = ensureRecipe(
            $db,
            $recipeCols,
            $userId > 0 ? $userId : $adminId,
            (string) $recipe['title'],
            (string) $recipe['desc'],
            (int) $recipe['time'],
            (string) $recipe['difficulty']
        );

        foreach ((array) $recipe['ingredients'] as $item) {
            [$ingredientName, $quantity, $unit] = $item;
            if (!isset($ingredientIds[$ingredientName])) {
                continue;
            }
            upsertRecipeIngredient($db, $rid, (int) $ingredientIds[$ingredientName], (string) $quantity, (string) $unit);
        }
    }

    echo "Seed completed.\n";
    echo "Admin: admin_demo@cooking.local / 123456\n";
    echo "User : user_demo@cooking.local / 123456\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
