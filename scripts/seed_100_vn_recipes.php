<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once APPROOT . '/app/models/Database.php';

function nowText(): string
{
    return date('Y-m-d H:i:s');
}

function tableColumns(Database $db, string $table): array
{
    $db->query('SHOW COLUMNS FROM ' . $table)->execute();
    $rows = $db->resultSet();
    $columns = [];
    foreach ($rows as $row) {
        $field = (string) ($row['Field'] ?? '');
        if ($field !== '') {
            $columns[$field] = true;
        }
    }
    return $columns;
}

function hasCol(array $columns, string $name): bool
{
    return isset($columns[$name]);
}

function ensureUser(Database $db, array $userCols): int
{
    $email = 'seed.vn.recipes@cooking.local';

    $db->query('SELECT id FROM users WHERE email = :email LIMIT 1')
        ->bind(':email', $email)
        ->execute();
    $found = $db->single();
    if (is_array($found) && isset($found['id'])) {
        return (int) $found['id'];
    }

    $insertCols = ['name', 'email', 'password'];
    $insertVals = [':name', ':email', ':password'];

    if (hasCol($userCols, 'role')) {
        $insertCols[] = 'role';
        $insertVals[] = ':role';
    }
    if (hasCol($userCols, 'status')) {
        $insertCols[] = 'status';
        $insertVals[] = ':status';
    }
    if (hasCol($userCols, 'created_at')) {
        $insertCols[] = 'created_at';
        $insertVals[] = ':created_at';
    }

    $sql = 'INSERT INTO users (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
    $db->query($sql)
        ->bind(':name', 'Seed Việt')
        ->bind(':email', $email)
        ->bind(':password', password_hash('123456', PASSWORD_DEFAULT));

    if (hasCol($userCols, 'role')) {
        $db->bind(':role', 'user');
    }
    if (hasCol($userCols, 'status')) {
        $db->bind(':status', 'active');
    }
    if (hasCol($userCols, 'created_at')) {
        $db->bind(':created_at', nowText());
    }

    $db->execute();
    return (int) $db->lastInsertId();
}

function ensureIngredient(Database $db, array $ingredientCols, string $name): int
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

    if (hasCol($ingredientCols, 'status')) {
        $insertCols[] = 'status';
        $insertVals[] = ':status';
    }
    if (hasCol($ingredientCols, 'source')) {
        $insertCols[] = 'source';
        $insertVals[] = ':source';
    }
    if (hasCol($ingredientCols, 'created_at')) {
        $insertCols[] = 'created_at';
        $insertVals[] = ':created_at';
    }

    $sql = 'INSERT INTO ingredients (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
    $db->query($sql)->bind(':name', $name);

    if (hasCol($ingredientCols, 'status')) {
        $db->bind(':status', 'approved');
    }
    if (hasCol($ingredientCols, 'source')) {
        $db->bind(':source', 'library');
    }
    if (hasCol($ingredientCols, 'created_at')) {
        $db->bind(':created_at', nowText());
    }

    $db->execute();
    return (int) $db->lastInsertId();
}

function ensureRecipe(Database $db, array $recipeCols, int $userId, string $title, string $description, int $cookingTime, string $difficulty): int
{
    $sql = 'SELECT id FROM recipes WHERE title = :title';
    if (hasCol($recipeCols, 'deleted_at')) {
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

    if (hasCol($recipeCols, 'cooking_time')) {
        $insertCols[] = 'cooking_time';
        $insertVals[] = ':cooking_time';
    }
    if (hasCol($recipeCols, 'difficulty')) {
        $insertCols[] = 'difficulty';
        $insertVals[] = ':difficulty';
    }
    if (hasCol($recipeCols, 'status')) {
        $insertCols[] = 'status';
        $insertVals[] = ':status';
    }
    if (hasCol($recipeCols, 'user_state')) {
        $insertCols[] = 'user_state';
        $insertVals[] = ':user_state';
    }
    if (hasCol($recipeCols, 'created_at')) {
        $insertCols[] = 'created_at';
        $insertVals[] = ':created_at';
    }

    $insertSql = 'INSERT INTO recipes (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
    $db->query($insertSql)
        ->bind(':user_id', $userId)
        ->bind(':title', $title)
        ->bind(':description', $description);

    if (hasCol($recipeCols, 'cooking_time')) {
        $db->bind(':cooking_time', $cookingTime);
    }
    if (hasCol($recipeCols, 'difficulty')) {
        $db->bind(':difficulty', $difficulty);
    }
    if (hasCol($recipeCols, 'status')) {
        $db->bind(':status', 'approved');
    }
    if (hasCol($recipeCols, 'user_state')) {
        $db->bind(':user_state', 'published');
    }
    if (hasCol($recipeCols, 'created_at')) {
        $db->bind(':created_at', nowText());
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

    $authorId = ensureUser($db, $userCols);

    $ingredientNames = [
        'Thịt gà', 'Thịt heo', 'Thịt bò', 'Cá basa', 'Cá thu', 'Tôm', 'Mực', 'Đậu hũ', 'Trứng gà', 'Nấm đùi gà',
        'Cà chua', 'Hành tây', 'Hành tím', 'Tỏi', 'Gừng', 'Sả', 'Ớt', 'Rau muống', 'Rau cải', 'Bắp cải',
        'Cà rốt', 'Khoai tây', 'Nấm rơm', 'Nấm kim châm', 'Dưa leo', 'Rau ngò', 'Hành lá', 'Lá chanh',
        'Nước mắm', 'Muối', 'Đường', 'Tiêu', 'Dầu ăn', 'Nước tương', 'Mật ong', 'Bột nghệ', 'Sa tế',
        'Me chua', 'Dứa', 'Đậu que', 'Bông cải xanh', 'Cải thìa', 'Mướp', 'Bầu', 'Bí đỏ'
    ];

    $ingredientIds = [];
    foreach ($ingredientNames as $name) {
        $ingredientIds[$name] = ensureIngredient($db, $ingredientCols, $name);
    }

    $mains = [
        'Thịt gà', 'Thịt heo', 'Thịt bò', 'Cá basa', 'Cá thu',
        'Tôm', 'Mực', 'Đậu hũ', 'Trứng gà', 'Nấm đùi gà',
    ];

    $styles = [
        ['name' => 'xào sả ớt', 'extra' => ['Sả', 'Ớt', 'Tỏi', 'Nước mắm']],
        ['name' => 'kho tiêu', 'extra' => ['Tiêu', 'Nước mắm', 'Hành tím', 'Đường']],
        ['name' => 'hấp gừng', 'extra' => ['Gừng', 'Hành lá', 'Muối', 'Tiêu']],
        ['name' => 'nướng mật ong', 'extra' => ['Mật ong', 'Tỏi', 'Tiêu', 'Nước tương']],
        ['name' => 'canh chua', 'extra' => ['Me chua', 'Cà chua', 'Dứa', 'Rau ngò']],
        ['name' => 'om nấm', 'extra' => ['Nấm rơm', 'Hành tím', 'Tỏi', 'Nước tương']],
        ['name' => 'chiên giòn', 'extra' => ['Dầu ăn', 'Muối', 'Tiêu', 'Tỏi']],
        ['name' => 'rim mặn ngọt', 'extra' => ['Nước mắm', 'Đường', 'Hành tím', 'Tỏi']],
        ['name' => 'luộc chấm mắm', 'extra' => ['Muối', 'Gừng', 'Nước mắm', 'Ớt']],
        ['name' => 'sốt cà', 'extra' => ['Cà chua', 'Hành tây', 'Tỏi', 'Nước tương']],
    ];

    $veggies = [
        'Rau muống', 'Rau cải', 'Bắp cải', 'Cà rốt', 'Khoai tây',
        'Đậu que', 'Bông cải xanh', 'Cải thìa', 'Mướp', 'Bầu',
    ];

    $difficulties = ['easy', 'easy', 'medium', 'medium', 'hard'];

    $created = 0;
    $linked = 0;

    for ($i = 0; $i < 10; $i++) {
        for ($j = 0; $j < 10; $j++) {
            $main = $mains[$i];
            $style = $styles[$j];
            $veg = $veggies[($i + $j) % count($veggies)];

            $title = $main . ' ' . $style['name'] . ' ' . $veg;
            $description = 'Món ' . strtolower($style['name']) . ' từ ' . mb_strtolower($main, 'UTF-8')
                . ', kết hợp ' . mb_strtolower($veg, 'UTF-8')
                . '. Hương vị đậm đà, dễ nấu tại nhà.';

            $time = 15 + (($i * 3 + $j * 4) % 35);
            $difficulty = $difficulties[($i + $j) % count($difficulties)];

            $recipeId = ensureRecipe($db, $recipeCols, $authorId, $title, $description, $time, $difficulty);
            $created++;

            $ingredientsForRecipe = [
                [$main, '300', 'g'],
                [$veg, '150', 'g'],
                [$style['extra'][0], '20', 'g'],
                [$style['extra'][1], '10', 'g'],
                ['Dầu ăn', '1', 'muỗng canh'],
            ];

            foreach ($ingredientsForRecipe as [$ingName, $qty, $unit]) {
                if (!isset($ingredientIds[$ingName])) {
                    $ingredientIds[$ingName] = ensureIngredient($db, $ingredientCols, $ingName);
                }

                upsertRecipeIngredient(
                    $db,
                    $recipeId,
                    (int) $ingredientIds[$ingName],
                    (string) $qty,
                    (string) $unit
                );
                $linked++;
            }
        }
    }

    echo "Seed completed.\n";
    echo "- Recipes processed: " . $created . "\n";
    echo "- Recipe ingredients upserted: " . $linked . "\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
