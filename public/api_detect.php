<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once APPROOT . '/app/services/IngredientVisionService.php';

header('Content-Type: application/json; charset=UTF-8');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Chi ho tro POST.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Khong tim thay file image.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['image'];
if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Upload loi hoac file khong hop le.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$tmpPath = (string) ($file['tmp_name'] ?? '');
if ($tmpPath === '' || !is_file($tmpPath)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Khong tim thay file upload.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $service = new IngredientVisionService();
    $result = $service->detectIngredientsFromImage($tmpPath);

    echo json_encode([
        'success' => true,
        'data' => $result,
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (RuntimeException $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Khong the nhan dien anh luc nay.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
