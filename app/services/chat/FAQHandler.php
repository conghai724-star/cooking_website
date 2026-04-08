<?php

declare(strict_types=1);

final class FAQHandler
{
    public function handle(array $intent, string $message, array $context = []): array
    {
        $requiresLogin = (bool) ($intent['requires_login'] ?? false);
        $isLoggedIn = (bool) ($context['is_logged_in'] ?? false);

        if ($requiresLogin && !$isLoggedIn) {
            $loginRoute = ['method' => 'GET', 'path' => '/login'];
            return [
                'success' => true,
                'code' => 'LOGIN_REQUIRED',
                'message' => 'Tính năng này cần đăng nhập trước khi sử dụng.',
                'route' => $loginRoute,
                'actions' => $this->buildActionsFromRoute($loginRoute, 'Đăng nhập'),
                'source' => 'faq',
            ];
        }

        $route = (array) ($intent['route'] ?? []);
        return [
            'success' => true,
            'code' => 'FAQ_MATCHED',
            'message' => (string) ($intent['response'] ?? 'Bạn có thể thao tác theo hướng dẫn trên website.'),
            'route' => $route,
            'actions' => $this->buildActionsFromRoute($route),
            'source' => 'faq',
        ];
    }

    private function buildActionsFromRoute(array $route, ?string $label = null): array
    {
        $method = strtoupper((string) ($route['method'] ?? 'GET'));
        $path = trim((string) ($route['path'] ?? ''));
        if ($method !== 'GET' || $path === '' || str_contains($path, '{')) {
            return [];
        }

        return [[
            'type' => 'link',
            'label' => $label ?? 'Mở trang liên quan',
            'url' => $path,
            'method' => 'GET',
        ]];
    }
}

