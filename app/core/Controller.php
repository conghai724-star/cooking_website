<?php

declare(strict_types=1);

abstract class Controller
{
    protected function jsonSuccess(array $data = [], string $message = 'OK', int $status = 200): void
    {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function jsonError(string $code, string $message, int $status, array $errors = []): void
    {
        $payload = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        $this->jsonResponse($payload, $status);
    }

    protected function jsonResponse(array $payload, int $status = 200): void
    {
        $isSuccess = (bool) ($payload['success'] ?? ($status < 400));
        if (!array_key_exists('success', $payload)) {
            $payload['success'] = $isSuccess;
        }
        if (!array_key_exists('code', $payload)) {
            $payload['code'] = $isSuccess ? 'OK' : $this->errorCodeFromStatus($status);
        }
        if (!array_key_exists('message', $payload)) {
            $payload['message'] = $isSuccess ? 'Thanh cong' : 'Co loi xay ra';
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function errorCodeFromStatus(int $status): string
    {
        return match ($status) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            default => 'SERVER_ERROR',
        };
    }

    protected function view(string $view, array $data = []): void
    {
        $viewPath = APPROOT . '/app/views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo 'Không tìm thấy giao dien: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($data, EXTR_SKIP);
        require APPROOT . '/app/views/layouts/header.php';
        require $viewPath;
        require APPROOT . '/app/views/layouts/footer.php';
    }

    protected function adminView(string $view, array $data = []): void
    {
        $viewPath = APPROOT . '/app/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo 'Không tìm thấy giao dien';
            return;
        }

        extract($data, EXTR_SKIP);
        require APPROOT . '/app/views/admin/layouts/admin_header.php';
        require $viewPath;
        require APPROOT . '/app/views/admin/layouts/admin_footer.php';
    }

    protected function model(string $model): object
    {
        $modelPath = APPROOT . '/app/models/' . $model . '.php';
        if (!file_exists($modelPath)) {
            throw new RuntimeException('Không tìm thấy model: ' . $model);
        }

        require_once $modelPath;
        return new $model();
    }

    protected function service(string $service): object
    {
        $serviceRef = str_replace('\\', '/', $service);
        $servicePath = APPROOT . '/app/services/' . $serviceRef . '.php';
        if (!file_exists($servicePath)) {
            throw new RuntimeException('Không tìm thấy service: ' . $service);
        }

        require_once $servicePath;
        $serviceClass = basename($serviceRef);
        if (!class_exists($serviceClass)) {
            throw new RuntimeException('Thieu class service: ' . $serviceClass);
        }

        return new $serviceClass();
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . URLROOT . $path);
        exit;
    }

    protected function renderNotFound(string $message = 'Không tìm thấy du lieu.'): void
    {
        http_response_code(404);
        $errorMessage = $message;
        require APPROOT . '/app/views/errors/404.php';
    }

    protected function renderForbidden(string $view = 'errors/403', array $data = []): void
    {
        http_response_code(403);
        $this->view($view, $data);
    }
}
