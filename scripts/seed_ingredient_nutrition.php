<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once APPROOT . '/app/models/Database.php';
require_once APPROOT . '/app/core/Model.php';
require_once APPROOT . '/app/models/IngredientModel.php';

function normalize_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }

    $name = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);

    $patterns = [
        '/[àáạảãâầấậẩẫăằắặẳẵ]/u' => 'a',
        '/[èéẹẻẽêềếệểễ]/u' => 'e',
        '/[ìíịỉĩ]/u' => 'i',
        '/[òóọỏõôồốộổỗơờớợởỡ]/u' => 'o',
        '/[ùúụủũưừứựửữ]/u' => 'u',
        '/[ỳýỵỷỹ]/u' => 'y',
        '/[đ]/u' => 'd',
    ];
    foreach ($patterns as $pattern => $replacement) {
        $name = (string) preg_replace($pattern, $replacement, $name);
    }

    $name = (string) preg_replace('/[^a-z0-9\s]/', ' ', $name);
    $name = (string) preg_replace('/\s+/', ' ', $name);

    return trim($name);
}

function nutrition_profiles(): array
{
    return [
        'thit ga' => [165.0, 31.0, 3.6, 0.0],
        'thit heo' => [242.0, 27.0, 14.0, 0.0],
        'thit bo' => [250.0, 26.0, 15.0, 0.0],
        'ca basa' => [120.0, 22.0, 4.0, 0.0],
        'ca thu' => [205.0, 19.0, 14.0, 0.0],
        'tom' => [99.0, 24.0, 0.3, 0.2],
        'muc' => [92.0, 16.0, 1.4, 3.1],
        'dau hu' => [76.0, 8.0, 4.8, 1.9],
        'trung ga' => [155.0, 13.0, 11.0, 1.1],
        'nam dui ga' => [35.0, 2.8, 0.3, 5.6],
        'ca chua' => [18.0, 0.9, 0.2, 3.9],
        'hanh tay' => [40.0, 1.1, 0.1, 9.3],
        'hanh tim' => [72.0, 2.5, 0.1, 16.8],
        'toi' => [149.0, 6.4, 0.5, 33.1],
        'gung' => [80.0, 1.8, 0.8, 18.0],
        'sa' => [99.0, 1.8, 0.5, 25.3],
        'ot' => [40.0, 2.0, 0.4, 9.0],
        'rau muong' => [19.0, 2.6, 0.2, 3.1],
        'rau cai' => [27.0, 2.9, 0.4, 4.7],
        'bap cai' => [25.0, 1.3, 0.1, 5.8],
        'ca rot' => [41.0, 0.9, 0.2, 10.0],
        'khoai tay' => [77.0, 2.0, 0.1, 17.0],
        'nam rom' => [22.0, 3.1, 0.3, 3.3],
        'nam kim cham' => [37.0, 2.7, 0.3, 7.8],
        'dua leo' => [15.0, 0.7, 0.1, 3.6],
        'rau ngo' => [23.0, 2.1, 0.5, 3.7],
        'hanh la' => [32.0, 1.8, 0.2, 7.3],
        'la chanh' => [29.0, 2.9, 0.7, 6.5],
        'nuoc mam' => [35.0, 5.0, 0.0, 1.0],
        'muoi' => [0.0, 0.0, 0.0, 0.0],
        'duong' => [387.0, 0.0, 0.0, 100.0],
        'tieu' => [251.0, 10.4, 3.3, 64.0],
        'dau an' => [884.0, 0.0, 100.0, 0.0],
        'nuoc tuong' => [53.0, 8.0, 0.6, 4.9],
        'mat ong' => [304.0, 0.3, 0.0, 82.4],
        'bot nghe' => [312.0, 9.7, 3.3, 67.1],
        'sa te' => [350.0, 3.0, 30.0, 15.0],
        'me chua' => [239.0, 2.8, 0.6, 62.5],
        'dua' => [50.0, 0.5, 0.1, 13.1],
        'dau que' => [31.0, 1.8, 0.1, 7.0],
        'bong cai xanh' => [35.0, 2.8, 0.4, 7.0],
        'cai thia' => [13.0, 1.5, 0.2, 2.2],
        'muop' => [20.0, 0.8, 0.1, 4.4],
        'bau' => [14.0, 0.6, 0.1, 3.4],
        'bi do' => [26.0, 1.0, 0.1, 6.5],
        'uc ga' => [165.0, 31.0, 3.6, 0.0],
        'thit bo nac' => [217.0, 26.0, 12.0, 0.0],
        'thit heo nac' => [143.0, 21.0, 6.0, 0.0],
        'ca hoi' => [208.0, 20.0, 13.0, 0.0],
        'tom tuoi' => [99.0, 24.0, 0.3, 0.2],
        'muc ong' => [92.0, 16.0, 1.4, 3.1],
        'dau hu non' => [55.0, 5.3, 2.7, 1.9],
        'dau hu chien' => [271.0, 17.2, 20.0, 8.5],
        'cai ngot' => [22.0, 2.6, 0.3, 3.6],
        'bap cai tim' => [31.0, 1.4, 0.2, 7.4],
        'khoai lang' => [86.0, 1.6, 0.1, 20.0],
        'ot do' => [40.0, 2.0, 0.4, 9.0],
        'rau mui' => [23.0, 2.1, 0.5, 3.7],
    ];
}

function estimate_macros(string $normalizedName): array
{
    $defaultProfile = [80.0, 3.0, 2.0, 8.0];
    if ($normalizedName === '') {
        return $defaultProfile;
    }

    $profiles = nutrition_profiles();
    if (isset($profiles[$normalizedName])) {
        return $profiles[$normalizedName];
    }

    $rules = [
        [['uc ga', 'thit ga'], [165.0, 31.0, 3.6, 0.0]],
        [['thit bo'], [250.0, 26.0, 15.0, 0.0]],
        [['thit heo'], [242.0, 27.0, 14.0, 0.0]],
        [['ca hoi'], [208.0, 20.0, 13.0, 0.0]],
        [['ca basa'], [120.0, 22.0, 4.0, 0.0]],
        [['ca thu'], [205.0, 19.0, 14.0, 0.0]],
        [['tom'], [99.0, 24.0, 0.3, 0.2]],
        [['muc'], [92.0, 16.0, 1.4, 3.1]],
        [['trung ga'], [155.0, 13.0, 11.0, 1.1]],
        [['dau hu'], [76.0, 8.0, 4.8, 1.9]],

        [['bong cai'], [35.0, 2.8, 0.4, 7.0]],
        [['cai', 'rau'], [25.0, 2.0, 0.3, 4.0]],
        [['ca chua'], [18.0, 0.9, 0.2, 3.9]],
        [['dua leo'], [15.0, 0.7, 0.1, 3.6]],
        [['ca rot'], [41.0, 0.9, 0.2, 10.0]],
        [['khoai tay'], [77.0, 2.0, 0.1, 17.0]],
        [['khoai lang'], [86.0, 1.6, 0.1, 20.0]],
        [['bi do'], [26.0, 1.0, 0.1, 6.5]],
        [['muop', 'bau'], [20.0, 0.8, 0.1, 4.0]],
        [['nam'], [31.0, 3.2, 0.3, 3.3]],

        [['dau an'], [884.0, 0.0, 100.0, 0.0]],
        [['mat ong'], [304.0, 0.3, 0.0, 82.4]],
        [['duong'], [387.0, 0.0, 0.0, 100.0]],
        [['sa te'], [350.0, 3.0, 30.0, 15.0]],
        [['nuoc tuong'], [53.0, 8.0, 0.6, 4.9]],
        [['nuoc mam'], [35.0, 5.0, 0.0, 1.0]],
        [['muoi'], [0.0, 0.0, 0.0, 0.0]],
    ];

    foreach ($rules as [$tokens, $values]) {
        foreach ($tokens as $token) {
            if (str_contains($normalizedName, $token)) {
                return $values;
            }
        }
    }

    return $defaultProfile;
}

try {
    $db = Database::getInstance();
    $ingredientModel = new IngredientModel();

    $db->query('SELECT id, name FROM ingredients ORDER BY id ASC')->execute();
    $ingredients = $db->resultSet();

    $updated = 0;
    foreach ($ingredients as $row) {
        $id = (int) ($row['id'] ?? 0);
        $name = (string) ($row['name'] ?? '');
        if ($id <= 0 || trim($name) === '') {
            continue;
        }

        $normalized = normalize_name($name);
        [$calories, $protein, $fat, $carb] = estimate_macros($normalized);

        $ok = $ingredientModel->upsertNutrition($id, $calories, $protein, $fat, $carb);
        if ($ok) {
            $updated++;
        }
    }

    $db->query('SELECT COUNT(*) AS c FROM ingredient_nutrition')->execute();
    $count = (int) (($db->single()['c'] ?? 0));

    echo "Nutrition seed completed.\n";
    echo '- Ingredients processed: ' . count($ingredients) . "\n";
    echo "- Upsert success: {$updated}\n";
    echo "- ingredient_nutrition rows: {$count}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Nutrition seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
