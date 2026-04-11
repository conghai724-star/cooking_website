<?php

declare(strict_types=1);

require_once APPROOT . '/app/services/IngredientVisionService.php';

class IngredientVisionController extends Controller
{
    public function health(): void
    {
        $model = trim((string) ROBOFLOW_MODEL);
        $driver = strtolower(trim((string) env_value('ROBOFLOW_DRIVER', 'api')));
        $live = (string) ($_GET['live'] ?? '0') === '1';

        $modelParts = explode('/', $model, 2);
        $modelProject = trim((string) ($modelParts[0] ?? ''));
        $modelVersion = trim((string) ($modelParts[1] ?? ''));
        $modelFormatOk = $modelProject !== '' && ctype_digit($modelVersion);

        $payload = [
            'driver' => $driver,
            'base_url' => (string) ROBOFLOW_BASE_URL,
            'confidence' => (int) ROBOFLOW_CONFIDENCE,
            'has_api_key' => trim((string) ROBOFLOW_API_KEY) !== '',
            'model' => $model,
            'model_format_ok' => $modelFormatOk,
            'model_project' => $modelProject,
            'model_version' => $modelVersion,
            'curl_enabled' => function_exists('curl_init'),
            'live_checked' => false,
            'live_ok' => null,
            'live_message' => null,
            'live_detection_count' => null,
        ];

        if (!$live) {
            $this->jsonSuccess($payload, 'AI health config only.');
        }

        $payload['live_checked'] = true;
        $tmpBase = tempnam(sys_get_temp_dir(), 'rf_probe_');
        if (!is_string($tmpBase) || $tmpBase === '') {
            $payload['live_ok'] = false;
            $payload['live_message'] = 'Không tạo được file tạm để probe.';
            $this->jsonError('SERVICE_ERROR', 'AI live health check failed.', 422, $payload);
        }
        $tmpFile = $tmpBase . '.png';
        @rename($tmpBase, $tmpFile);

        try {
            // 1x1 transparent png
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5w2a4AAAAASUVORK5CYII=', true);
            if (!is_string($png) || $png === '') {
                throw new RuntimeException('Không tạo được payload ảnh test.');
            }
            if (file_put_contents($tmpFile, $png) === false) {
                throw new RuntimeException('Không ghi được ảnh test để probe.');
            }

            $service = new IngredientVisionService();
            $result = $service->detectIngredientsFromImage($tmpFile);
            $count = count((array) ($result['detections'] ?? []));

            $payload['live_ok'] = true;
            $payload['live_message'] = 'Roboflow reachable và model hợp lệ.';
            $payload['live_detection_count'] = $count;
            $this->jsonSuccess($payload, 'AI live health check passed.');
        } catch (Throwable $e) {
            $payload['live_ok'] = false;
            $payload['live_message'] = $e->getMessage();
            $this->jsonError('SERVICE_ERROR', 'AI live health check failed.', 422, $payload);
        } finally {
            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    public function ui(): void
    {
        $this->view('ai/ingredient_vision', [
            'title' => 'AI Nhận Diện Nguyên Liệu',
            'useRecipeHubLayout' => true,
        ]);
    }

    public function dragSearchUi(): void
    {
        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        $rows = $ingredientModel->all('approved', 'library');

        $grouped = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $category = trim((string) ($row['category_name'] ?? ''));
            if ($category === '') {
                $category = 'Khác';
            }
            $grouped[$category][] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => $name,
                'image' => trim((string) ($row['image'] ?? '')),
            ];
        }

        $preferredOrder = ['Rau củ', 'Thịt', 'Hải sản', 'Gia vị'];
        $sorted = [];
        foreach ($preferredOrder as $cat) {
            if (!empty($grouped[$cat])) {
                $sorted[$cat] = $grouped[$cat];
                unset($grouped[$cat]);
            }
        }
        $khac = $grouped['Khác'] ?? null;
        unset($grouped['Khác']);
        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($grouped as $cat => $items) {
            $sorted[$cat] = $items;
        }
        if ($khac !== null) {
            $sorted['Khác'] = $khac;
        }

        $this->view('ai/ingredient_drag_search', [
            'title' => 'Chọn nguyên liệu bằng hình ảnh',
            'useRecipeHubLayout' => true,
            'ingredientGroups' => $sorted,
        ]);
    }

    public function suggestRecipes(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['GET', 'POST'], true)) {
            $this->jsonError('BAD_REQUEST', 'Phương thức không hợp lệ.', 400);
        }

        $payload = $this->readInput($method);

        try {
            $service = new IngredientVisionService();
            $result = $service->suggestRecipes($payload);
        } catch (RuntimeException $e) {
            $this->jsonError('SERVICE_ERROR', $e->getMessage(), 422);
        } catch (Throwable $e) {
            $this->jsonError('SERVER_ERROR', 'Không thể xử lý dữ liệu nguyên liệu lúc này.', 500);
        }

        $this->jsonSuccess($result, 'Gợi ý công thức thành công.');
    }

    public function detectIngredients(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method !== 'POST') {
            $this->jsonError('BAD_REQUEST', 'Phương thức không hợp lệ.', 400);
        }

        $file = $_FILES['image'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonError('VALIDATION_ERROR', 'Bạn cần upload ảnh hợp lệ.', 422);
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_file($tmpPath)) {
            $this->jsonError('VALIDATION_ERROR', 'Không tìm thấy file upload.', 422);
        }

        try {
            $service = new IngredientVisionService();
            $result = $service->detectIngredientsFromImage($tmpPath);
        } catch (RuntimeException $e) {
            $this->jsonError('SERVICE_ERROR', $e->getMessage(), 422);
        } catch (Throwable $e) {
            $this->jsonError('SERVER_ERROR', 'Không thể nhận diện ảnh lúc này.', 500);
        }

        $this->jsonSuccess($result, 'Nhận diện nguyên liệu thành công.');
    }

    private function readInput(string $method): array
    {
        if ($method === 'GET') {
            $ingredientsRaw = trim((string) ($_GET['ingredients'] ?? ''));
            $ingredients = $ingredientsRaw !== ''
                ? array_values(array_filter(array_map('trim', explode(',', $ingredientsRaw)), static fn(string $v): bool => $v !== ''))
                : [];

            return [
                'ingredients' => $ingredients,
                'keyword' => trim((string) ($_GET['keyword'] ?? '')),
                'limit' => (int) ($_GET['limit'] ?? 8),
                'max_calories' => (int) ($_GET['max_calories'] ?? 0),
            ];
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        $rawBody = file_get_contents('php://input');
        if (!is_string($rawBody) || trim($rawBody) === '') {
            return [];
        }

        $decoded = json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
