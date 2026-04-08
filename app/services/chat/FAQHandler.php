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
                'message' => 'TĂ­nh nA�ƒng nĂ y cA�º§n A�‘A�ƒng nhA�º­p trA�°A�»›c khi sA�»­ dA�»¥ng.',
                'route' => $loginRoute,
                'actions' => $this->buildActionsFromRoute($loginRoute, 'Đăng nhập'),
                'source' => 'faq',
            ];
        }

        $route = (array) ($intent['route'] ?? []);
        return [
            'success' => true,
            'code' => 'FAQ_MATCHED',
            'message' => (string) ($intent['response'] ?? 'BA�º¡n cĂ³ thA�»ƒ thao tĂ¡c theo hA�°A�»›ng dA�º«n trĂªn website.'),
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
            'label' => $label ?? 'MA�»Ÿ trang liĂªn quan',
            'url' => $path,
            'method' => 'GET',
        ]];
    }
}

