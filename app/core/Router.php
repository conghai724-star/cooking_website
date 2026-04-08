<?php

declare(strict_types=1);

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
    ];

    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function any(string $path, array $handler, array $middlewares = []): void
    {
        foreach (array_keys($this->routes) as $method) {
            $this->addRoute($method, $path, $handler, $middlewares);
        }
    }

    private function addRoute(string $method, string $path, array $handler, array $middlewares = []): void
    {
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $pattern = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', '([^/]+)', $path);
        $regex = '#^' . $pattern . '$#';

        $this->routes[$method][] = [
            'path' => $path,
            'regex' => $regex,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . ltrim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $method = strtoupper($method);
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !csrf_verify_request()) {
            csrf_reject();
        }
        $routeList = $this->routes[$method] ?? [];

        foreach ($routeList as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            $middlewares = $route['middlewares'] ?? [];
            foreach ($middlewares as $middleware) {
                if (!is_callable($middleware)) {
                    continue;
                }

                if ($middleware() === false) {
                    return;
                }
            }

            array_shift($matches);
            [$controllerRef, $action] = $route['handler'];
            $controllerRef = str_replace('\\', '/', $controllerRef);

            $controllerPath = APPROOT . '/app/controllers/' . $controllerRef . '.php';
            $controllerName = basename($controllerRef);

            if (!file_exists($controllerPath) && !str_contains($controllerRef, '/')) {
                $adminFallbackPath = APPROOT . '/app/controllers/admin/' . $controllerRef . '.php';
                if (file_exists($adminFallbackPath)) {
                    $controllerPath = $adminFallbackPath;
                }
            }

            if (!file_exists($controllerPath)) {
                $this->error404('Không tìm thấy controller: ' . $controllerRef);
                return;
            }

            require_once $controllerPath;

            if (!class_exists($controllerName)) {
                $this->error404('Thieu class: ' . $controllerName);
                return;
            }

            $controller = new $controllerName();
            if (!method_exists($controller, $action)) {
                $this->error404('Không tìm thấy action: ' . $action);
                return;
            }

            call_user_func_array([$controller, $action], $matches);
            return;
        }

        $this->error404();
    }

    private function error404(string $message = ''): void
    {
        http_response_code(404);
        $errorMessage = $message;
        require APPROOT . '/app/views/errors/404.php';
    }
}