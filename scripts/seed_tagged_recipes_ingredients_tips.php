<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once APPROOT . '/app/models/Database.php';

function nowText(): string
{
    return date('Y-m-d H:i:s');
}

function slugify(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return 'tip';
    }

    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $text = str_replace(['đ', 'Đ'], ['d', 'd'], $text);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($converted) && $converted !== '') {
            $text = $converted;
        }
    }

    $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'tip';
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

function ensureUser(Database $db, array $userCols): int
{
    $email = 'seed.bundle@cooking.local';
    $db->query('SELECT id FROM users WHERE email = :email LIMIT 1')
        ->bind(':email', $email)
        ->execute();
    $row = $db->single();
    if (is_array($row) && isset($row['id'])) {
        return (int) $row['id'];
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
        ->bind(':name', 'Seed Bundle')
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

function ensureIngredient(Database $db, array $ingredientCols, string $name, int $userId): int
{
    $db->query('SELECT id FROM ingredients WHERE name = :name ORDER BY id ASC LIMIT 1')
        ->bind(':name', $name)
        ->execute();
    $row = $db->single();
    if (is_array($row) && isset($row['id'])) {
        return (int) $row['id'];
    }

    $insertCols = ['name'];
    $insertVals = [':name'];
    if (hasCol($ingredientCols, 'description')) {
        $insertCols[] = 'description';
        $insertVals[] = ':description';
    }
    if (hasCol($ingredientCols, 'preparation')) {
        $insertCols[] = 'preparation';
        $insertVals[] = ':preparation';
    }
    if (hasCol($ingredientCols, 'storage')) {
        $insertCols[] = 'storage';
        $insertVals[] = ':storage';
    }
    if (hasCol($ingredientCols, 'status')) {
        $insertCols[] = 'status';
        $insertVals[] = ':status';
    }
    if (hasCol($ingredientCols, 'user_id')) {
        $insertCols[] = 'user_id';
        $insertVals[] = ':user_id';
    }
    if (hasCol($ingredientCols, 'created_at')) {
        $insertCols[] = 'created_at';
        $insertVals[] = ':created_at';
    }

    $sql = 'INSERT INTO ingredients (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
    $db->query($sql)
        ->bind(':name', $name);

    if (hasCol($ingredientCols, 'description')) {
        $db->bind(':description', 'Nguyên liệu dùng cho món ăn gia đình.');
    }
    if (hasCol($ingredientCols, 'preparation')) {
        $db->bind(':preparation', 'Rửa sạch và sơ chế tùy món.');
    }
    if (hasCol($ingredientCols, 'storage')) {
        $db->bind(':storage', 'Bảo quản ngăn mát từ 1-3 ngày.');
    }
    if (hasCol($ingredientCols, 'status')) {
        $db->bind(':status', 'approved');
    }
    if (hasCol($ingredientCols, 'user_id')) {
        $db->bind(':user_id', $userId);
    }
    if (hasCol($ingredientCols, 'created_at')) {
        $db->bind(':created_at', nowText());
    }

    $db->execute();
    return (int) $db->lastInsertId();
}

function ensureTip(Database $db, array $tipCols, int $userId, string $title, string $excerpt, string $content): int
{
    $baseSlug = slugify($title);
    $slug = $baseSlug;

    $db->query('SELECT id FROM tips WHERE slug = :slug LIMIT 1')
        ->bind(':slug', $slug)
        ->execute();
    $exists = $db->single();
    if (is_array($exists) && isset($exists['id'])) {
        return (int) $exists['id'];
    }

    $insertCols = ['title', 'slug', 'excerpt', 'content'];
    $insertVals = [':title', ':slug', ':excerpt', ':content'];

    if (hasCol($tipCols, 'user_id')) {
        $insertCols[] = 'user_id';
        $insertVals[] = ':user_id';
    }
    if (hasCol($tipCols, 'author_name')) {
        $insertCols[] = 'author_name';
        $insertVals[] = ':author_name';
    }
    if (hasCol($tipCols, 'status')) {
        $insertCols[] = 'status';
        $insertVals[] = ':status';
    }
    if (hasCol($tipCols, 'created_at')) {
        $insertCols[] = 'created_at';
        $insertVals[] = ':created_at';
    }

    $sql = 'INSERT INTO tips (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
    $db->query($sql)
        ->bind(':title', $title)
        ->bind(':slug', $slug)
        ->bind(':excerpt', $excerpt)
        ->bind(':content', $content);

    if (hasCol($tipCols, 'user_id')) {
        $db->bind(':user_id', $userId);
    }
    if (hasCol($tipCols, 'author_name')) {
        $db->bind(':author_name', 'Bep nha');
    }
    if (hasCol($tipCols, 'status')) {
        $db->bind(':status', 'approved');
    }
    if (hasCol($tipCols, 'created_at')) {
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

    $db->query($sql)
        ->bind(':title', $title)
        ->execute();
    $row = $db->single();
    if (is_array($row) && isset($row['id'])) {
        return (int) $row['id'];
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

function addRecipeIngredient(Database $db, int $recipeId, int $ingredientId, string $quantity, string $unit): void
{
    $db->query('INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit)
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

function addRecipeTag(Database $db, int $recipeId, int $tagId): void
{
    $db->query('INSERT IGNORE INTO recipe_tags (recipe_id, tag_id, created_at) VALUES (:recipe_id, :tag_id, NOW())')
        ->bind(':recipe_id', $recipeId)
        ->bind(':tag_id', $tagId)
        ->execute();
}

try {
    $db = Database::getInstance();

    $userCols = tableColumns($db, 'users');
    $ingredientCols = tableColumns($db, 'ingredients');
    $tipCols = tableColumns($db, 'tips');
    $recipeCols = tableColumns($db, 'recipes');

    $userId = ensureUser($db, $userCols);

    $ingredientPool = [
        'Ức gà', 'Thịt bò nạc', 'Thịt heo nạc', 'Cá hồi', 'Cá basa', 'Tôm tươi', 'Mực ống', 'Đậu hũ non', 'Đậu hũ chiên', 'Trứng gà',
        'Cải thìa', 'Cải ngọt', 'Rau muống', 'Bắp cải tím', 'Bông cải xanh', 'Đậu que', 'Cà rốt', 'Khoai tây', 'Khoai lang', 'Bí đỏ',
        'Mướp', 'Bầu', 'Cà chua', 'Dưa leo', 'Hành tây', 'Hành tím', 'Tỏi', 'Gừng', 'Sả', 'Ớt đỏ',
        'Nấm đùi gà', 'Nấm rơm', 'Nấm kim châm', 'Rau mùi', 'Hành lá', 'Lá chanh', 'Me chua', 'Sa tế', 'Mật ong', 'Nước tương'
    ];

    $ingredientIds = [];
    foreach ($ingredientPool as $name) {
        $ingredientIds[$name] = ensureIngredient($db, $ingredientCols, $name, $userId);
    }

    $db->query('SELECT COUNT(*) AS c FROM ingredients')->execute();
    $ingredientCount = (int) (($db->single()['c'] ?? 0));

    $tipTitles = [
        'Cách khử mùi tanh cá nhanh', 'Mẹo xào rau xanh giòn', 'Ướp thịt gà mềm ngon', 'Canh chua đậm vị không bị gắt', 'Chiên ít dầu vẫn giòn',
        'Bảo quản rau sống 3 ngày', 'Mẹo cắt hành không cay mắt', 'Nêm nếm 3 bước chuẩn vị', 'Nấu canh trong vị thanh', 'Kho cá không bị nát',
        'Luộc rau giữ màu xanh', 'Xào tỏi không bị cháy', 'Ướp thịt bò không dai', 'Nấu súp sánh mịn', 'Hấp hải sản giữ ngọt',
        'Dùng nồi chiên không dầu đúng cách', 'Giảm mặn trong món kho', 'Làm sốt cà chua nhanh', 'Món chua ngọt cân vị', 'Chọn dầu ăn phù hợp từng món',
        'Sơ chế mực không tanh', 'Nêm món cay hài hòa', 'Bảo quản gia vị khô', 'Làm sạch nấm đúng cách', 'Ướp tôm không bị bở',
        'Mẹo hầm mềm nhanh', 'Canh bí đỏ thơm béo nhẹ', 'Xử lý món lỡ tay quá cay', 'Mẹo nấu ăn cho người giảm cân', 'Chuẩn bị meal prep 3 ngày'
    ];

    $tipsCreated = 0;
    foreach ($tipTitles as $idx => $title) {
        $excerpt = 'Mẹo bếp #' . ($idx + 1) . ': ' . $title;
        $content = "Bước 1: Chuẩn bị nguyên liệu sạch.\nBước 2: Sơ chế đúng kỹ thuật theo món.\nBước 3: Nêm nếm từ nhẹ đến đậm để kiểm soát vị.";
        $tipId = ensureTip($db, $tipCols, $userId, $title, $excerpt, $content);
        if ($tipId > 0) {
            $tipsCreated++;
        }
    }

    $db->query('SELECT slug, id FROM tags')->execute();
    $tagMap = [];
    foreach ($db->resultSet() as $row) {
        $slug = (string) ($row['slug'] ?? '');
        $id = (int) ($row['id'] ?? 0);
        if ($slug !== '' && $id > 0) {
            $tagMap[$slug] = $id;
        }
    }

    $methods = ['xao', 'chien', 'hap', 'luoc', 'nuong', 'kho', 'canh', 'sup'];
    $methodLabel = [
        'xao' => 'xào', 'chien' => 'chiên', 'hap' => 'hấp', 'luoc' => 'luộc',
        'nuong' => 'nướng', 'kho' => 'kho', 'canh' => 'canh', 'sup' => 'súp'
    ];

    $mains = ['Ức gà', 'Thịt bò nạc', 'Thịt heo nạc', 'Tôm tươi', 'Đậu hũ non'];
    $veggies = ['Cải thìa', 'Bông cải xanh', 'Đậu que', 'Cà rốt', 'Bí đỏ', 'Mướp'];
    $tasteByMethod = ['xao' => 'dam_da', 'chien' => 'beo', 'hap' => 'thanh', 'luoc' => 'thanh', 'nuong' => 'man', 'kho' => 'chua', 'canh' => 'chua', 'sup' => 'thanh'];
    $healthByMethod = ['xao' => 'healthy', 'chien' => 'it_dau', 'hap' => 'it_dau', 'luoc' => 'it_dau', 'nuong' => 'it_calo', 'kho' => 'an_kieng', 'canh' => 'healthy', 'sup' => 'it_calo'];
    $mealByMethod = ['canh' => 'mon_nuoc', 'sup' => 'mon_nuoc'];

    $recipesProcessed = 0;
    $recipeTagsAdded = 0;

    for ($i = 0; $i < count($mains); $i++) {
        for ($j = 0; $j < count($methods); $j++) {
            $main = $mains[$i];
            $method = $methods[$j];
            $veg = $veggies[($i + $j) % count($veggies)];

            $title = $main . ' ' . $methodLabel[$method] . ' ' . $veg;
            $description = 'Món ' . $methodLabel[$method] . ' từ ' . $main . ' kết hợp ' . $veg . ', dễ nấu cho bữa cơm gia đình.';
            $difficulty = ($j % 3 === 0) ? 'easy' : (($j % 3 === 1) ? 'medium' : 'hard');
            $time = 18 + (($i * 7 + $j * 4) % 32);

            $recipeId = ensureRecipe($db, $recipeCols, $userId, $title, $description, $time, $difficulty);
            $recipesProcessed++;

            addRecipeIngredient($db, $recipeId, $ingredientIds[$main], '250', 'g');
            addRecipeIngredient($db, $recipeId, $ingredientIds[$veg], '150', 'g');
            addRecipeIngredient($db, $recipeId, $ingredientIds['Tỏi'], '10', 'g');
            addRecipeIngredient($db, $recipeId, $ingredientIds['Nước tương'], '1', 'muỗng canh');

            $slugs = [$method, $tasteByMethod[$method], $healthByMethod[$method], $mealByMethod[$method] ?? 'mon_chinh'];
            foreach ($slugs as $slug) {
                $tagId = (int) ($tagMap[$slug] ?? 0);
                if ($tagId <= 0) {
                    continue;
                }
                addRecipeTag($db, $recipeId, $tagId);
                $recipeTagsAdded++;
            }
        }
    }

    $db->query('SELECT COUNT(*) AS c FROM tips')->execute();
    $tipCount = (int) (($db->single()['c'] ?? 0));

    $db->query('SELECT COUNT(*) AS c FROM recipes')->execute();
    $recipeCount = (int) (($db->single()['c'] ?? 0));

    $db->query('SELECT COUNT(*) AS c FROM recipe_tags')->execute();
    $recipeTagCount = (int) (($db->single()['c'] ?? 0));

    echo "Seed bundle completed.\n";
    echo "- Ingredients total: {$ingredientCount}\n";
    echo "- Tips total: {$tipCount}\n";
    echo "- Recipes total: {$recipeCount}\n";
    echo "- recipe_tags total: {$recipeTagCount}\n";
    echo "- Recipes processed this run: {$recipesProcessed}\n";
    echo "- Recipe tag links attempted: {$recipeTagsAdded}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed bundle failed: ' . $e->getMessage() . "\n");
    exit(1);
}
