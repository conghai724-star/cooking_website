<?php

declare(strict_types=1);

require_once APPROOT . '/app/models/RecipeModel.php';
require_once APPROOT . '/app/models/IngredientModel.php';
require_once APPROOT . '/app/models/TipModel.php';

final class RecipeHandler
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
                'message' => 'Ban can dang nhap de su dung tinh nang nay.',
                'route' => $loginRoute,
                'actions' => $this->buildActionsFromRoute($loginRoute, 'Dang nhap'),
                'source' => 'recipe_handler',
            ];
        }

        $group = (string) ($intent['group'] ?? '');
        $route = (array) ($intent['route'] ?? []);

        if (in_array($group, ['recipe', 'ingredient', 'tip'], true)) {
            $items = $this->buildContentSuggestions($group, $message);
            if ($items !== []) {
                return [
                    'success' => true,
                    'code' => 'CONTENT_SUGGESTED',
                    'message' => $this->suggestedMessageByGroup($group),
                    'items' => $items,
                    'actions' => $this->buildActionsFromItems($items),
                    'route' => $route,
                    'source' => 'recipe_handler',
                ];
            }
        }

        return [
            'success' => true,
            'code' => 'ROUTE_FALLBACK',
            'message' => (string) ($intent['response'] ?? 'Da tim thay tinh nang lien quan den noi dung ban can.'),
            'route' => $route,
            'actions' => $this->buildActionsFromRoute($route),
            'source' => 'recipe_handler',
        ];
    }

    private function buildContentSuggestions(string $group, string $message): array
    {
        $keyword = $this->extractKeyword($message);

        try {
            if ($group === 'recipe') {
                $model = new RecipeModel();
                $rows = $model->recommendForChat(null, null, $keyword, [], 4);
                return array_map(static function (array $row): array {
                    $id = (int) ($row['id'] ?? 0);
                    $title = trim((string) ($row['title'] ?? ''));
                    if ($id <= 0 || $title === '') {
                        return [];
                    }
                    return [
                        'id' => $id,
                        'title' => $title,
                        'url' => '/recipes/' . $id,
                    ];
                }, $rows);
            }

            if ($group === 'ingredient') {
                $model = new IngredientModel();
                $rows = $model->allPaged('approved', 'library', 4, 0, $keyword);
                if ($rows === []) {
                    $rows = $model->allPaged(null, 'library', 4, 0, $keyword);
                }
                return array_map(static function (array $row): array {
                    $id = (int) ($row['id'] ?? 0);
                    $title = trim((string) ($row['name'] ?? ''));
                    if ($id <= 0 || $title === '') {
                        return [];
                    }
                    return [
                        'id' => $id,
                        'title' => $title,
                        'url' => '/ingredients/' . $id,
                    ];
                }, $rows);
            }

            if ($group === 'tip') {
                $model = new TipModel();
                $rows = $model->allPaged('approved', 4, 0, $keyword);
                if ($rows === []) {
                    $rows = $model->allPaged(null, 4, 0, $keyword);
                }
                return array_map(static function (array $row): array {
                    $slug = trim((string) ($row['slug'] ?? ''));
                    $title = trim((string) ($row['title'] ?? ''));
                    if ($slug === '' || $title === '') {
                        return [];
                    }
                    return [
                        'id' => (int) ($row['id'] ?? 0),
                        'title' => $title,
                        'url' => '/tips/' . $slug,
                    ];
                }, $rows);
            }
        } catch (Throwable $e) {
            return [];
        }

        return [];
    }

    private function suggestedMessageByGroup(string $group): string
    {
        if ($group === 'recipe') {
            return 'Toi tim thay mot vai cong thuc phu hop cho ban.';
        }
        if ($group === 'ingredient') {
            return 'Toi tim thay mot vai nguyen lieu phu hop cho ban.';
        }
        if ($group === 'tip') {
            return 'Toi tim thay mot vai meo vat phu hop cho ban.';
        }
        return 'Toi da tim thay mot vai noi dung phu hop cho ban.';
    }

    private function extractKeyword(string $message): ?string
    {
        $text = trim($message);
        if ($text === '') {
            return null;
        }

        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = preg_replace('/[\\x{0111}\\x{0110}]/u', 'd', $text);

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        }

        $stopPhrases = [
            'goi y', 'de xuat', 'mon an', 'cong thuc', 'nguyen lieu', 'meo', 'meo vat',
            'toi muon', 'cho toi', 'xem', 'o dau', 'giup toi', 'tim', 'ban co the'
        ];
        foreach ($stopPhrases as $phrase) {
            $text = str_replace($phrase, ' ', $text);
        }

        $text = (string) preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text !== '' ? $text : null;
    }

    private function buildActionsFromItems(array $items): array
    {
        $actions = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['title'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            $actions[] = [
                'type' => 'link',
                'label' => $label,
                'url' => $url,
                'method' => 'GET',
            ];
            if (count($actions) >= 4) {
                break;
            }
        }
        return $actions;
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
            'label' => $label ?? 'Mo trang lien quan',
            'url' => $path,
            'method' => 'GET',
        ]];
    }
}

