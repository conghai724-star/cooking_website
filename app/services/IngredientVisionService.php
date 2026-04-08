<?php

declare(strict_types=1);

require_once APPROOT . '/app/models/IngredientAliasModel.php';
require_once APPROOT . '/app/models/IngredientModel.php';
require_once APPROOT . '/app/models/RecipeModel.php';

final class IngredientVisionService
{
    public function detectIngredientsFromImage(string $imagePath): array
    {
        if (!is_file($imagePath)) {
            throw new RuntimeException('Anh upload khong hop le.');
        }
        if (ROBOFLOW_API_KEY === '' || ROBOFLOW_MODEL === '') {
            throw new RuntimeException('Roboflow chua duoc cau hinh. Vui long them ROBOFLOW_API_KEY va ROBOFLOW_MODEL trong .env');
        }

        $response = $this->detectRaw($imagePath);
        $predictions = is_array($response['predictions'] ?? null) ? $response['predictions'] : [];

        $imageWidth = (float) ($response['image']['width'] ?? $response['width'] ?? 0);
        $imageHeight = (float) ($response['image']['height'] ?? $response['height'] ?? 0);

        $items = [];
        foreach ($predictions as $prediction) {
            if (!is_array($prediction)) {
                continue;
            }
            $label = trim((string) ($prediction['class'] ?? $prediction['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $confidence = (float) ($prediction['confidence'] ?? 0);
            if ($confidence <= 0) {
                continue;
            }

            $box = null;
            $x = isset($prediction['x']) ? (float) $prediction['x'] : null;
            $y = isset($prediction['y']) ? (float) $prediction['y'] : null;
            $w = isset($prediction['width']) ? (float) $prediction['width'] : null;
            $h = isset($prediction['height']) ? (float) $prediction['height'] : null;
            if ($x !== null && $y !== null && $w !== null && $h !== null && $imageWidth > 0 && $imageHeight > 0) {
                $left = max(0.0, ($x - ($w / 2.0)) / $imageWidth);
                $top = max(0.0, ($y - ($h / 2.0)) / $imageHeight);
                $normW = max(0.0, min(1.0, $w / $imageWidth));
                $normH = max(0.0, min(1.0, $h / $imageHeight));
                $box = [
                    'x' => min(1.0, $left),
                    'y' => min(1.0, $top),
                    'w' => $normW,
                    'h' => $normH,
                ];
            }

            $items[] = [
                'label' => $label,
                'vi_label' => $this->toDisplayVietnamese($label),
                'confidence' => $confidence,
                'box' => $box,
            ];
        }

        return [
            'detections' => $items,
        ];
    }

    public function suggestRecipes(array $payload): array
    {
        $labels = $this->collectLabels($payload);
        if ($labels === []) {
            return [
                'labels' => [],
                'resolved_ingredients' => [],
                'recipes' => [],
            ];
        }

        $aliasModel = new IngredientAliasModel();
        $ingredientIds = [];
        foreach ($labels as $label) {
            $resolved = $aliasModel->resolveIngredientIdsFromText($label, 5);
            foreach ($resolved as $ingredientId) {
                $ingredientIds[(int) $ingredientId] = true;
            }
        }

        $ids = array_map('intval', array_keys($ingredientIds));
        if ($ids === []) {
            return [
                'labels' => $labels,
                'resolved_ingredients' => [],
                'recipes' => [],
            ];
        }

        $maxCalories = $this->toNullablePositiveInt($payload['max_calories'] ?? null);
        $keyword = trim((string) ($payload['keyword'] ?? ''));
        $keyword = $keyword !== '' ? $keyword : null;
        $limit = $this->clampInt($payload['limit'] ?? 8, 1, 20, 8);

        $recipeModel = new RecipeModel();
        $recipes = [];
        try {
            $recipes = $recipeModel->recommendByIngredientIds($ids, $maxCalories, $keyword, [], $limit);
        } catch (Throwable $_) {
            $fallbackKeyword = trim(implode(' ', array_slice($labels, 0, 6)));
            if ($fallbackKeyword === '') {
                $fallbackKeyword = $keyword;
            } elseif ($keyword !== null) {
                $fallbackKeyword = trim($keyword . ' ' . $fallbackKeyword);
            }

            try {
                $recipes = $recipeModel->allApprovedPaged($limit, 0, $fallbackKeyword !== '' ? $fallbackKeyword : null);
            } catch (Throwable $_) {
                $recipes = [];
            }
        }

        $ingredientModel = new IngredientModel();
        $ingredients = [];
        try {
            $ingredients = $ingredientModel->findBasicByIds($ids);
        } catch (Throwable $_) {
            $ingredients = [];
        }

        return [
            'labels' => $labels,
            'resolved_ingredients' => array_map(static function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'name' => (string) ($row['name'] ?? ''),
                ];
            }, $ingredients),
            'recipes' => array_map(function (array $row) use ($ids): array {
                $mc = isset($row['matched_count']) ? (int) $row['matched_count'] : null;
                $ti = isset($row['total_ingredients']) ? (int) $row['total_ingredients'] : null;
                $selCount = count($ids);
                $pctInRecipe = ($mc !== null && $ti !== null && $ti > 0)
                    ? (int) max(0, min(100, (int) round(100 * $mc / $ti)))
                    : null;
                $pctOfSelection = ($mc !== null && $selCount > 0)
                    ? (int) max(0, min(100, (int) round(100 * $mc / $selCount)))
                    : null;

                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'title' => (string) ($row['title'] ?? ''),
                    'description' => (string) ($row['description'] ?? ''),
                    'image' => (string) ($row['image'] ?? ''),
                    'estimated_kcal' => isset($row['estimated_kcal']) ? (float) $row['estimated_kcal'] : null,
                    'matched_count' => $mc,
                    'total_ingredients' => $ti,
                    'ingredient_match_percent' => $pctInRecipe,
                    'selection_match_percent' => $pctOfSelection,
                    'url' => URLROOT . '/recipes/' . (int) ($row['id'] ?? 0),
                ];
            }, $recipes),
        ];
    }

        private function collectLabels(array $payload): array
    {
        $labels = [];

        $rawIngredients = $payload['ingredients'] ?? [];
        if (is_array($rawIngredients)) {
            foreach ($rawIngredients as $item) {
                if (!is_scalar($item)) {
                    continue;
                }
                $text = trim((string) $item);
                if ($text !== '') {
                    $labels[] = $text;
                }
            }
        }

        $rawDetections = $payload['detections'] ?? [];
        if (is_array($rawDetections)) {
            foreach ($rawDetections as $detection) {
                if (!is_array($detection)) {
                    continue;
                }

                $label = trim((string) ($detection['label'] ?? ''));
                $viLabel = trim((string) ($detection['vi_label'] ?? ''));
                if ($label === '' && $viLabel === '') {
                    continue;
                }

                $confidence = isset($detection['confidence']) ? (float) $detection['confidence'] : 1.0;
                if ($confidence < 0.25) {
                    continue;
                }

                if ($label !== '') {
                    $labels[] = $this->toDisplayVietnamese($label);
                }
                if ($viLabel !== '') {
                    $labels[] = $viLabel;
                }
            }
        }

        $normalized = array_values(array_unique(array_filter(array_map(
            static fn(string $v): string => trim($v),
            $labels
        ), static fn(string $v): bool => $v !== '')));

        return array_slice($normalized, 0, 30);
    }

    private function detectRaw(string $imagePath): array
    {
        $driver = strtolower(trim((string) env_value('ROBOFLOW_DRIVER', 'api')));
        if ($driver === 'python') {
            return $this->callRoboflowPython($imagePath);
        }

        try {
            return $this->callRoboflowApi($imagePath);
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            $this->logRoboflowError('api', $message);

            if (ROBOFLOW_FALLBACK_PYTHON && $this->shouldFallbackToPython($message)) {
                $this->logRoboflowError('fallback', 'Switching to python driver after API failure.');
                return $this->callRoboflowPython($imagePath);
            }

            throw $e;
        }
    }

    private function callRoboflowApi(string $imagePath): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Server chua bat extension cURL.');
        }

        $base = rtrim((string) ROBOFLOW_BASE_URL, '/');
        $query = http_build_query([
            'api_key' => ROBOFLOW_API_KEY,
            'confidence' => max(1, min(99, ROBOFLOW_CONFIDENCE)),
        ]);
        $modelPath = trim((string) ROBOFLOW_MODEL, '/');
        if ($modelPath === '') {
            throw new RuntimeException('Roboflow model khong hop le.');
        }
        $url = $base . '/' . $modelPath . '?' . $query;

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Khong the khoi tao ket noi Roboflow.');
        }

        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
        $file = curl_file_create($imagePath, $mime, basename($imagePath));

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => ['file' => $file],
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            throw new RuntimeException($this->buildRoboflowErrorMessage(
                'Khong nhan duoc phan hoi tu Roboflow.',
                $url,
                $code,
                $err,
                ''
            ));
        }
        if ($err !== '') {
            throw new RuntimeException($this->buildRoboflowErrorMessage(
                'Loi ket noi Roboflow: ' . $err,
                $url,
                $code,
                $err,
                $raw
            ));
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException($this->buildRoboflowErrorMessage(
                'Roboflow tra ve du lieu khong hop le.',
                $url,
                $code,
                $err,
                $raw
            ));
        }
        if ($code < 200 || $code >= 300) {
            $message = trim((string) ($decoded['message'] ?? ''));
            $baseMessage = $message !== '' ? $message : 'Roboflow tra ve loi HTTP ' . $code;
            throw new RuntimeException($this->buildRoboflowErrorMessage(
                $baseMessage,
                $url,
                $code,
                $err,
                $raw
            ));
        }

        return $decoded;
    }
    private function callRoboflowPython(string $imagePath): array
    {
        $scriptPath = APPROOT . '/ai/detect.py';
        if (!is_file($scriptPath)) {
            throw new RuntimeException('Thieu file ai/detect.py');
        }

        $threshold = max(0.0, min(1.0, ((float) ROBOFLOW_CONFIDENCE) / 100.0));
        $pythonCandidates = [];
        $pythonEnv = trim(env_value('PYTHON_BIN', ''));
        if ($pythonEnv !== '') {
            $pythonCandidates[] = $pythonEnv;
        }
        $pythonCandidates[] = 'python';
        $pythonCandidates[] = 'py -3';
        $pythonCandidates[] = 'py';
        $pythonCandidates = array_values(array_unique($pythonCandidates));

        $lastOutput = '';
        foreach ($pythonCandidates as $pythonPrefix) {
            $command = $pythonPrefix
                . ' '
                . escapeshellarg($scriptPath)
                . ' '
                . escapeshellarg($imagePath)
                . ' '
                . escapeshellarg((string) ROBOFLOW_MODEL)
                . ' '
                . escapeshellarg((string) ROBOFLOW_API_KEY)
                . ' '
                . escapeshellarg((string) $threshold)
                . ' 2>&1';

            $raw = shell_exec($command);
            $tryOutput = is_string($raw) ? trim($raw) : '';
            if ($tryOutput === '') {
                continue;
            }

            $lastOutput = $tryOutput;
            $decoded = $this->extractJsonPayload($tryOutput);
            if (is_array($decoded)) {
                if (isset($decoded['error'])) {
                    throw new RuntimeException((string) $decoded['error']);
                }
                return $decoded;
            }

            if (
                stripos($tryOutput, 'python was not found') !== false ||
                stripos($tryOutput, 'is not recognized as an internal or external command') !== false
            ) {
                continue;
            }

            throw new RuntimeException('Output Python khong phai JSON: ' . $tryOutput);
        }

        throw new RuntimeException('Khong tim thay Python. Cai Python va set PYTHON_BIN trong .env (vi du: C:\\Python312\\python.exe). Last: ' . $lastOutput);
    }
    private function extractJsonPayload(string $output): ?array
    {
        $text = trim($output);
        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $lines = preg_split('/\r\n|\n|\r/', $text) ?: [];
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim((string) $lines[$i]);
            if ($line === '') {
                continue;
            }
            if ($line[0] !== '{' && $line[0] !== '[') {
                continue;
            }

            $decodedLine = json_decode($line, true);
            if (is_array($decodedLine)) {
                return $decodedLine;
            }
        }

        return null;
    }    private function toDisplayVietnamese(string $label): string
    {
        $normalized = strtolower(trim($label));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        $vi = static function (string $unicodeEscaped): string {
            $decoded = json_decode('"' . $unicodeEscaped . '"');
            return is_string($decoded) && $decoded !== '' ? $decoded : $unicodeEscaped;
        };

        $alias = [
            'akabare_khursani' => $vi('\u1eda Akabare'),
            'apple' => $vi('T\u00e1o'),
            'artichoke' => $vi('Atiso'),
            'ash_gourd_kubhindo' => $vi('B\u00ed \u0111ao'),
            'asparagus_kurilo' => $vi('M\u0103ng t\u00e2y'),
            'avocado' => $vi('B\u01a1'),
            'bacon' => $vi('Th\u1ecbt x\u00f4ng kh\u00f3i'),
            'bamboo_shoots_tama' => $vi('M\u0103ng tre'),
            'banana' => $vi('Chu\u1ed1i'),
            'beans' => $vi('\u0110\u1eadu cove'),
            'beaten_rice_chiura' => $vi('C\u01a1m d\u1eb9t (chiura)'),
            'beetroot' => $vi('C\u1ee7 d\u1ec1n'),
            'bethu_ko_saag' => $vi('Rau bethu'),
            'bitter_gourd' => $vi('Kh\u1ed5 qua'),
            'black_lentils' => $vi('\u0110\u1eadu l\u0103ng \u0111en'),
            'black_beans' => $vi('\u0110\u1eadu \u0111en'),
            'bottle_gourd_lauka' => $vi('B\u1ea7u'),
            'bread' => $vi('B\u00e1nh m\u00ec'),
            'brinjal' => $vi('C\u00e0 t\u00edm'),
            'broad_beans_bakullo' => $vi('\u0110\u1eadu t\u1eb1m'),
            'broccoli' => $vi('B\u00f4ng c\u1ea3i xanh'),
            'buff_meat' => $vi('Th\u1ecbt tr\u00e2u'),
            'butter' => $vi('B\u01a1 l\u1ea1t'),
            'cabbage' => $vi('B\u1eafp c\u1ea3i'),
            'capsicum' => $vi('\u1eda chu\u00f4ng'),
            'carrot' => $vi('C\u00e0 r\u1ed1t'),
            'cassava_ghar_tarul' => $vi('Khoai m\u00ec'),
            'cauliflower' => $vi('S\u00fap l\u01a1'),
            'chayote_iskus' => $vi('Su su'),
            'cheese' => $vi('Ph\u00f4 mai'),
            'chicken' => $vi('Th\u1ecbt g\u00e0'),
            'chicken_gizzards' => $vi('M\u1ec1 g\u00e0'),
            'chickpeas' => $vi('\u0110\u1eadu g\u00e0'),
            'chili_pepper_khursani' => $vi('\u1eda'),
            'chili_powder' => $vi('B\u1ed9t \u1edbt'),
            'chowmein_noodles' => $vi('M\u00ec chowmein'),
            'cinnamon' => $vi('Qu\u1ebf'),
            'coriander_dhaniya' => $vi('Rau m\u00f9i'),
            'corn' => $vi('B\u1eafp'),
            'cornflakec' => $vi('Ng\u0169 c\u1ed1c b\u1eafp'),
            'cornflakes' => $vi('Ng\u0169 c\u1ed1c b\u1eafp'),
            'crab_meat' => $vi('Th\u1ecbt cua'),
            'cucumber' => $vi('D\u01b0a leo'),
            'egg' => $vi('Tr\u1ee9ng'),
            'farsi_ko_munta' => $vi('Ng\u1ecdn b\u00ed'),
            'fiddlehead_ferns_niguro' => $vi('Rau d\u1edbn'),
            'fish' => $vi('C\u00e1'),
            'garden_peas' => $vi('\u0110\u1eadu H\u00e0 Lan'),
            'garden_cress_chamsur_ko_saag' => $vi('Rau c\u1ea3i son'),
            'garlic' => $vi('T\u1ecfi'),
            'green_brinjal' => $vi('C\u00e0 t\u00edm xanh'),
            'green_lentils' => $vi('\u0110\u1eadu l\u0103ng xanh'),
            'green_mint_pudina' => $vi('B\u1ea1c h\u00e0'),
            'gundruk' => $vi('Rau l\u00ean men Gundruk'),
            'ham' => $vi('Gi\u0103m b\u00f4ng'),
            'jack_fruit' => $vi('M\u00edt'),
            'ketchup' => $vi('T\u01b0\u01a1ng c\u00e0'),
            'lapsi_nepali_hog_plum' => $vi('M\u1eadn Nepal'),
            'lemon_nimbu' => $vi('Chanh v\u00e0ng'),
            'lime_kagati' => $vi('Chanh xanh'),
            'masyaura' => $vi('Masyaura'),
            'milk' => $vi('S\u1eefa'),
            'minced_meat' => $vi('Th\u1ecbt b\u0103m'),
            'moringa_leaves_sajyun_ko_munta' => $vi('L\u00e1 ch\u00f9m ng\u00e2y'),
            'mushroom' => $vi('N\u1ea5m'),
            'mutton' => $vi('Th\u1ecbt c\u1eebu'),
            'nutrela_soya_chunks' => $vi('Th\u1ecbt chay \u0111\u1eadu n\u00e0nh'),
            'okra_bhindi' => $vi('\u0110\u1eadu b\u1eafp'),
            'onion' => $vi('H\u00e0nh t\u00e2y'),
            'onion_leaves' => $vi('L\u00e1 h\u00e0nh'),
            'palak_indian_spinach' => $vi('Rau bina \u1ea4n \u0110\u1ed9'),
            'palungo_nepali_spinach' => $vi('Rau bina Nepal'),
            'paneer' => $vi('Ph\u00f4 mai Paneer'),
            'papaya' => $vi('\u0110u \u0111\u1ee7'),
            'pea' => $vi('\u0110\u1eadu H\u00e0 Lan'),
            'pear' => $vi('L\u00ea'),
            'pointed_gourd_chuche_karela' => $vi('B\u00ed nh\u1ecdn'),
            'pork' => $vi('Th\u1ecbt heo'),
            'pork_belly' => $vi('Ba ch\u1ec9 heo'),
            'pork_meat' => $vi('Th\u1ecbt heo'),
            'potato' => $vi('Khoai t\u00e2y'),
            'pumpkin_farsi' => $vi('B\u00ed \u0111\u1ecf'),
            'radish' => $vi('C\u1ee7 c\u1ea3i'),
            'rahar_ko_daal' => $vi('\u0110\u1eadu pigeon (toor dal)'),
            'rayo_ko_saag' => $vi('Rau c\u1ea3i m\u00f9 t\u1ea1t'),
            'red_beans' => $vi('\u0110\u1eadu \u0111\u1ecf'),
            'red_lentils' => $vi('\u0110\u1eadu l\u0103ng \u0111\u1ecf'),
            'rice_chamal' => $vi('G\u1ea1o'),
            'sajjyun_moringa_drumsticks' => $vi('Qu\u1ea3 ch\u00f9m ng\u00e2y'),
            'sausage' => $vi('X\u00fac x\u00edch'),
            'shrimp' => $vi('T\u00f4m'),
            'snake_gourd_chichindo' => $vi('B\u00ed r\u1eafn'),
            'soy_sauce' => $vi('N\u01b0\u1edbc t\u01b0\u01a1ng'),
            'soyabean_bhatmas' => $vi('\u0110\u1eadu n\u00e0nh'),
            'sponge_gourd_ghiraula' => $vi('M\u01b0\u1edbp'),
            'stinging_nettle_sisnu' => $vi('Rau t\u1ea7m ma'),
            'strawberry' => $vi('D\u00e2u t\u00e2y'),
            'sugar' => $vi('\u0110\u01b0\u1eddng'),
            'sweet_potato_suthuni' => $vi('Khoai lang'),
            'taro_leaves_karkalo' => $vi('L\u00e1 khoai m\u00f4n'),
            'taro_root_pidalu' => $vi('C\u1ee7 khoai m\u00f4n'),
            'thukpa_noodles' => $vi('M\u00ec thukpa'),
            'tomato' => $vi('C\u00e0 chua'),
            'tori_ko_saag' => $vi('Rau c\u1ea3i tori'),
            'tree_tomato_rukh_tamatar' => $vi('C\u00e0 chua th\u00e2n g\u1ed7'),
            'turnip' => $vi('C\u1ee7 c\u1ea3i tr\u1eafng'),
            'wheat' => $vi('L\u00faa m\u00ec'),
            'yellow_lentils' => $vi('\u0110\u1eadu l\u0103ng v\u00e0ng'),
            'mayonnaise' => $vi('S\u1ed1t mayonnaise'),
            'noodle' => $vi('M\u00ec s\u1ee3i'),
            'spring_onion' => $vi('H\u00e0nh l\u00e1'),
            'beef' => $vi('Th\u1ecbt b\u00f2'),
        ];

        if (isset($alias[$normalized])) {
            return $alias[$normalized];
        }

        return trim($label);
    }
    private function clampInt(mixed $value, int $min, int $max, int $fallback): int
    {
        if (!is_numeric($value)) {
            return $fallback;
        }
        $n = (int) $value;
        if ($n < $min) {
            return $min;
        }
        if ($n > $max) {
            return $max;
        }
        return $n;
    }

    private function toNullablePositiveInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }
        $n = (int) $value;
        return $n > 0 ? $n : null;
    }

    private function buildRoboflowErrorMessage(
        string $message,
        string $url,
        int $httpCode,
        string $curlError,
        string $rawBody
    ): string {
        if (!ROBOFLOW_DEBUG) {
            return $message;
        }

        $debugUrl = preg_replace('/([?&]api_key=)[^&]+/i', '$1***', $url) ?: $url;
        $debugRaw = trim($rawBody);
        if ($debugRaw !== '' && strlen($debugRaw) > 400) {
            $debugRaw = substr($debugRaw, 0, 400) . '...';
        }

        $parts = [$message, '[rf_debug]'];
        $parts[] = 'http=' . $httpCode;
        if ($curlError !== '') {
            $parts[] = 'curl=' . $curlError;
        }
        $parts[] = 'url=' . $debugUrl;
        if ($debugRaw !== '') {
            $parts[] = 'raw=' . $debugRaw;
        }

        return implode(' ', $parts);
    }

    private function shouldFallbackToPython(string $message): bool
    {
        $lower = strtolower($message);
        return str_contains($lower, 'forbidden')
            || str_contains($lower, 'http=403')
            || str_contains($lower, 'resource not found')
            || str_contains($lower, 'http=404');
    }

    private function logRoboflowError(string $channel, string $message): void
    {
        $logDir = APPROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            return;
        }

        $line = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            $channel,
            $message
        );

        @file_put_contents($logDir . '/roboflow.log', $line, FILE_APPEND);
    }
}