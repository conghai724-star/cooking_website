<?php

declare(strict_types=1);

require_once APPROOT . '/app/models/RecipeModel.php';
require_once APPROOT . '/app/models/IngredientAliasModel.php';
require_once APPROOT . '/app/models/IngredientModel.php';
require_once APPROOT . '/app/models/TagModel.php';

final class DietHandler
{
    private const MEALS = ['sang', 'trua', 'toi'];
    private const METHOD_FALLBACK = ['canh', 'chien', 'xao', 'hap', 'nuong', 'kho'];
    private const FLAVOR_FALLBACK = ['cay', 'chua', 'ngot', 'man', 'thanh'];
    private const ALLERGY_TOKENS = ['tom', 'cua', 'ca', 'bo', 'heo', 'ga', 'trung', 'sua', 'dau phong', 'lac'];

    private array $tagKeywordMapCache = [];

    public function handle(array $intent, string $message, array $context = []): array
    {
        $intentId = (string) ($intent['id'] ?? '');
        if ($intentId === 'nutrition_recipe_calories') {
            return $this->handleRecipeCaloriesIntent($intent, $message, $context);
        }

        $matchedEntities = (array) ($context['matched_entities'] ?? []);
        $chatState = is_array($context['chat_state'] ?? null)
            ? $context['chat_state']
            : ['meal' => null, 'calories' => null, 'allergies' => []];

        $meal = $this->resolveMeal($matchedEntities, $chatState);
        $calories = $this->resolveCaloriesLimit($matchedEntities, $chatState, $message);
        $allergies = $this->resolveAllergies($message, $chatState);
        $keyword = $this->extractSearchKeyword($message);
        $methodKeywords = $this->resolveCookingMethodKeywords($matchedEntities, $message);
        $flavorKeywords = $this->resolveFlavorKeywords($matchedEntities, $message);
        $recipeKeyword = $this->mergeRecipeKeyword($keyword, $methodKeywords, $flavorKeywords);
        $ingredientIds = $this->extractIngredientIds($message);

        $isLowCalorieIntent = in_array($intentId, ['diet_low_calorie', 'diet_weight_loss'], true);
        if ($isLowCalorieIntent && $ingredientIds === []) {
            $ingredientIds = $this->resolveLowCalorieIngredientIds($calories);
        }

        try {
            $recipeModel = new RecipeModel();
            $recipes = $ingredientIds !== []
                ? $recipeModel->recommendByIngredientIds($ingredientIds, $calories, $recipeKeyword, $allergies, 5)
                : $recipeModel->recommendForChat($meal, $calories, $recipeKeyword, $allergies, 5);
        } catch (Throwable $e) {
            return [
                'success' => true,
                'code' => 'DIET_TEMP_UNAVAILABLE',
                'message' => 'Tinh nang goi y dinh duong tam thoi chua san sang. Ban thu lai sau.',
                'source' => 'diet_handler',
            ];
        }

        $items = $this->mapRecipeItems($recipes);
        $nextState = [
            'meal' => $meal,
            'calories' => $calories,
            'allergies' => $allergies,
        ];

        if ($items === []) {
            return [
                'success' => true,
                'code' => 'DIET_NO_RESULT',
                'message' => 'Toi chua tim duoc mon phu hop theo bo loc hien tai. Ban thu doi keyword hoac muc kcal.',
                'suggestions' => (array) ($intent['suggest_next'] ?? []),
                'context_updates' => [
                    'last_keyword' => $keyword,
                    'chat_state' => $nextState,
                ],
                'source' => 'diet_handler',
            ];
        }

        return [
            'success' => true,
            'code' => 'DIET_MATCHED',
            'message' => (string) ($intent['response'] ?? 'Toi da tim duoc mot so mon phu hop.'),
            'items' => $items,
            'actions' => $this->buildActionsFromItems($items),
            'suggestions' => (array) ($intent['suggest_next'] ?? []),
            'context_updates' => [
                'last_recipe' => (string) ($items[0]['title'] ?? ''),
                'last_keyword' => $recipeKeyword ?? $keyword,
                'chat_state' => $nextState,
            ],
            'source' => 'diet_handler',
        ];
    }

    private function resolveMeal(array $matchedEntities, array $chatState): ?string
    {
        $raw = isset($matchedEntities['meal'][0])
            ? (string) ($matchedEntities['meal'][0])
            : (string) ($chatState['meal'] ?? '');

        $raw = $this->normalizeText($raw);
        if ($raw === '') {
            return null;
        }

        foreach (self::MEALS as $meal) {
            if ($this->containsWholeToken($raw, $meal)) {
                return $meal;
            }
        }

        return null;
    }

    private function resolveCaloriesLimit(array $matchedEntities, array $chatState, string $message): ?int
    {
        $candidates = [];

        if (isset($matchedEntities['calories']) && is_array($matchedEntities['calories'])) {
            $candidates = array_merge($candidates, $matchedEntities['calories']);
        }

        $stateCalories = $chatState['calories'] ?? null;
        if (is_scalar($stateCalories)) {
            $candidates[] = (string) $stateCalories;
        }

        $candidates[] = $message;

        foreach ($candidates as $candidate) {
            if (!is_scalar($candidate)) {
                continue;
            }

            if (preg_match('/(\d{2,4})\s*(kcal|calo)?/ui', (string) $candidate, $m) === 1) {
                $limit = (int) $m[1];
                if ($limit >= 50 && $limit <= 3000) {
                    return $limit;
                }
            }
        }

        return null;
    }

    private function resolveAllergies(string $message, array $chatState): array
    {
        $normalized = $this->normalizeText($message);

        if (str_contains($normalized, 'khong di ung') || str_contains($normalized, 'bo di ung')) {
            return [];
        }

        $detected = [];
        foreach (self::ALLERGY_TOKENS as $token) {
            if (preg_match('/(?<![a-z0-9])(di ung|khong an|tranh)\s+' . preg_quote($token, '/') . '(?![a-z0-9])/u', $normalized) === 1) {
                $detected[] = $token;
            }
        }

        $previous = is_array($chatState['allergies'] ?? null) ? $chatState['allergies'] : [];

        return array_values(array_unique(array_filter(
            array_merge($previous, $detected),
            static fn($v): bool => is_string($v) && trim($v) !== ''
        )));
    }

    private function extractIngredientIds(string $message): array
    {
        try {
            $aliasModel = new IngredientAliasModel();
            return $aliasModel->resolveIngredientIdsFromText($message, 12);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function resolveCookingMethodKeywords(array $matchedEntities, string $message): array
    {
        $values = [];
        $normalizedMessage = $this->normalizeText($message);
        $keywordMap = $this->getTagKeywordMapByType('method', self::METHOD_FALLBACK);

        foreach ((array) ($matchedEntities['cooking_method'] ?? []) as $value) {
            $token = $this->normalizeText((string) $value);
            $canonical = $this->resolveCanonicalFromKeywordMap($token, $keywordMap);
            if ($canonical !== null) {
                $values[] = $canonical;
            }
        }

        if ($values === []) {
            foreach ($keywordMap as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    if ($this->containsWholeToken($normalizedMessage, (string) $alias)) {
                        $values[] = (string) $canonical;
                        break;
                    }
                }
            }
        }

        return array_values(array_unique($values));
    }

    private function resolveFlavorKeywords(array $matchedEntities, string $message): array
    {
        $values = [];
        $normalizedMessage = $this->normalizeText($message);
        $keywordMap = $this->getTagKeywordMapByType('taste', self::FLAVOR_FALLBACK);

        foreach ((array) ($matchedEntities['flavor'] ?? []) as $value) {
            $token = $this->normalizeText((string) $value);
            $canonical = $this->resolveCanonicalFromKeywordMap($token, $keywordMap);
            if ($canonical !== null) {
                $values[] = $canonical;
            }
        }

        if ($values === []) {
            foreach ($keywordMap as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    if ($this->containsWholeToken($normalizedMessage, (string) $alias)) {
                        $values[] = (string) $canonical;
                        break;
                    }
                }
            }
        }

        return array_values(array_unique($values));
    }

    private function mergeRecipeKeyword(?string $keyword, array $methodKeywords, array $flavorKeywords): ?string
    {
        $baseKeyword = is_string($keyword) ? trim($keyword) : '';
        if ($baseKeyword !== '') {
            return $baseKeyword;
        }

        $method = '';
        foreach ($methodKeywords as $token) {
            $token = trim((string) $token);
            if ($token !== '') {
                $method = $token;
                break;
            }
        }

        $flavor = '';
        foreach ($flavorKeywords as $token) {
            $token = trim((string) $token);
            if ($token !== '') {
                $flavor = $token;
                break;
            }
        }

        if ($method !== '' && $flavor !== '' && $method !== $flavor) {
            return $method . ' ' . $flavor;
        }
        if ($method !== '') {
            return $method;
        }
        if ($flavor !== '') {
            return $flavor;
        }

        return null;
    }

    private function buildActionsFromItems(array $items): array
    {
        $actions = [];
        foreach ($items as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($title === '' || $url === '') {
                continue;
            }

            $actions[] = [
                'type' => 'link',
                'label' => $title,
                'url' => $url,
                'method' => 'GET',
            ];

            if (count($actions) >= 4) {
                break;
            }
        }

        return $actions;
    }

    private function resolveLowCalorieIngredientIds(?int $requestedCalories): array
    {
        $seedCalories = 120;
        if ($requestedCalories !== null && $requestedCalories > 0) {
            if ($requestedCalories <= 300) {
                $seedCalories = 90;
            } elseif ($requestedCalories <= 500) {
                $seedCalories = 120;
            } else {
                $seedCalories = 160;
            }
        }

        try {
            $ingredientModel = new IngredientModel();
            return $ingredientModel->findLowCalorieIngredientIds($seedCalories, 24);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function handleRecipeCaloriesIntent(array $intent, string $message, array $context): array
    {
        $lastRecipe = trim((string) ($context['last_recipe'] ?? ''));
        $keyword = $this->extractSearchKeyword($message);
        if ($keyword === null || $keyword === '') {
            $keyword = $this->extractSearchKeyword($lastRecipe);
        }

        if ($keyword === null || $keyword === '') {
            return [
                'success' => true,
                'code' => 'CALORIES_NEED_RECIPE',
                'message' => 'Ban hay gui ten mon cu the de toi uoc tinh calo chinh xac hon.',
                'suggestions' => (array) ($intent['suggest_next'] ?? []),
                'source' => 'diet_handler',
            ];
        }

        try {
            $recipeModel = new RecipeModel();
            $rows = $recipeModel->recommendForChat(null, null, $keyword, [], 1);
        } catch (Throwable $e) {
            return [
                'success' => true,
                'code' => 'CALORIES_TEMP_UNAVAILABLE',
                'message' => 'Hien tai toi chua lay duoc du lieu calo. Ban thu lai sau.',
                'source' => 'diet_handler',
            ];
        }

        if ($rows === []) {
            return [
                'success' => true,
                'code' => 'CALORIES_NO_RESULT',
                'message' => 'Toi chua tim thay mon phu hop de tinh calo. Ban thu gui ten mon day du hon.',
                'source' => 'diet_handler',
            ];
        }

        $row = $rows[0];
        $title = (string) ($row['title'] ?? $keyword);
        $kcal = (int) round((float) ($row['estimated_kcal'] ?? 0));
        $recipeId = (int) ($row['id'] ?? 0);

        return [
            'success' => true,
            'code' => 'CALORIES_MATCHED',
            'message' => $kcal > 0
                ? ($title . ' uoc tinh khoang ' . $kcal . ' kcal.')
                : ('Toi chua co so kcal cho mon ' . $title . '.'),
            'actions' => $recipeId > 0 ? [[
                'type' => 'link',
                'label' => 'Xem mon',
                'url' => '/recipes/' . $recipeId,
                'method' => 'GET',
            ]] : [],
            'context_updates' => [
                'last_recipe' => $title,
                'last_keyword' => $keyword,
            ],
            'source' => 'diet_handler',
        ];
    }

    private function extractSearchKeyword(string $message): ?string
    {
        $text = $this->normalizeText($message);
        if ($text === '') {
            return null;
        }

        $trimPhrases = [
            'toi co',
            'toi muon',
            'an gi',
            'goi y mon',
            'goi y cong thuc',
            'de xuat',
            'hom nay',
            'toi nay',
            'sang',
            'trua',
            'toi an gi',
            'giam can',
            'it calo',
            'kcal',
            'calo',
            'bao nhieu',
            'cong thuc',
            'mon',
            'cach nau',
            'nau mon',
            'huong dan nau',
            'cach lam',
            'lam mon',
        ];

        $escaped = array_map(static fn(string $phrase): string => preg_quote($phrase, '/'), $trimPhrases);
        usort($escaped, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        if ($escaped !== []) {
            $prefixPattern = '/^(?:' . implode('|', $escaped) . ')\s+/u';
            $suffixPattern = '/\s+(?:' . implode('|', $escaped) . ')$/u';
            do {
                $before = $text;
                $text = (string) preg_replace($prefixPattern, '', $text);
                $text = (string) preg_replace($suffixPattern, '', $text);
                $text = trim($text);
            } while ($text !== $before && $text !== '');
        }

        $text = (string) preg_replace('/\d+/', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        $methodMap = $this->getTagKeywordMapByType('method', self::METHOD_FALLBACK);
        $flavorMap = $this->getTagKeywordMapByType('taste', self::FLAVOR_FALLBACK);
        $reserved = [];
        foreach ([$methodMap, $flavorMap] as $map) {
            foreach ($map as $canonical => $aliases) {
                $reserved[] = (string) $canonical;
                foreach ((array) $aliases as $alias) {
                    $reserved[] = (string) $alias;
                }
            }
        }

        $reserved = array_values(array_unique(array_filter(array_map(
            fn(string $v): string => $this->normalizeText($v),
            $reserved
        ), static fn(string $v): bool => $v !== '')));

        if ($text !== '' && $reserved !== []) {
            $tokens = preg_split('/\s+/', $text) ?: [];
            $filtered = [];
            foreach ($tokens as $token) {
                $token = $this->normalizeText((string) $token);
                if ($token === '') {
                    continue;
                }
                if (in_array($token, $reserved, true)) {
                    continue;
                }
                $filtered[] = $token;
            }
            $text = trim(implode(' ', array_values(array_unique($filtered))));
        }

        if ($text === '' || strlen($text) < 2) {
            return null;
        }

        return $text;
    }

    private function normalizeText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

        $patterns = [
            '/[\x{00E0}\x{00E1}\x{1EA1}\x{1EA3}\x{00E3}\x{00E2}\x{1EA7}\x{1EA5}\x{1EAD}\x{1EA9}\x{1EAB}\x{0103}\x{1EB1}\x{1EAF}\x{1EB7}\x{1EB3}\x{1EB5}]/u' => 'a',
            '/[\x{00E8}\x{00E9}\x{1EB9}\x{1EBB}\x{1EBD}\x{00EA}\x{1EC1}\x{1EBF}\x{1EC7}\x{1EC3}\x{1EC5}]/u' => 'e',
            '/[\x{00EC}\x{00ED}\x{1ECB}\x{1EC9}\x{0129}]/u' => 'i',
            '/[\x{00F2}\x{00F3}\x{1ECD}\x{1ECF}\x{00F5}\x{00F4}\x{1ED3}\x{1ED1}\x{1ED9}\x{1ED5}\x{1ED7}\x{01A1}\x{1EDD}\x{1EDB}\x{1EE3}\x{1EDF}\x{1EE1}]/u' => 'o',
            '/[\x{00F9}\x{00FA}\x{1EE5}\x{1EE7}\x{0169}\x{01B0}\x{1EEB}\x{1EE9}\x{1EF1}\x{1EED}\x{1EEF}]/u' => 'u',
            '/[\x{1EF3}\x{00FD}\x{1EF5}\x{1EF7}\x{1EF9}]/u' => 'y',
            '/[\x{0111}]/u' => 'd',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $text = (string) preg_replace($pattern, $replacement, $text);
        }

        $text = (string) preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function getTagKeywordMapByType(string $type, array $fallback): array
    {
        $type = trim($type);
        if ($type === '') {
            return $this->buildFallbackKeywordMap($fallback);
        }

        if (!array_key_exists($type, $this->tagKeywordMapCache)) {
            try {
                $tagModel = new TagModel();
                $map = $tagModel->getKeywordMapByType($type);
                $this->tagKeywordMapCache[$type] = is_array($map) ? $map : [];
            } catch (Throwable $e) {
                $this->tagKeywordMapCache[$type] = [];
            }
        }

        $result = $this->tagKeywordMapCache[$type] ?? [];
        if (!is_array($result) || $result === []) {
            return $this->buildFallbackKeywordMap($fallback);
        }

        return $result;
    }

    private function buildFallbackKeywordMap(array $fallback): array
    {
        $map = [];
        foreach ($fallback as $token) {
            $normalized = $this->normalizeText((string) $token);
            if ($normalized === '') {
                continue;
            }
            $map[$normalized] = [$normalized];
        }

        return $map;
    }

    private function resolveCanonicalFromKeywordMap(string $token, array $keywordMap): ?string
    {
        if ($token === '') {
            return null;
        }

        foreach ($keywordMap as $canonical => $aliases) {
            if ($token === (string) $canonical || in_array($token, (array) $aliases, true)) {
                return (string) $canonical;
            }
        }

        return null;
    }

    private function containsWholeToken(string $text, string $token): bool
    {
        $text = trim($text);
        $token = trim($token);
        if ($text === '' || $token === '') {
            return false;
        }

        return preg_match('/(?<![a-z0-9])' . preg_quote($token, '/') . '(?![a-z0-9])/u', $text) === 1;
    }

    private function mapRecipeItems(array $recipes): array
    {
        $items = [];

        foreach ($recipes as $recipe) {
            if (!is_array($recipe)) {
                continue;
            }

            $recipeId = (int) ($recipe['id'] ?? 0);
            if ($recipeId <= 0) {
                continue;
            }

            $title = (string) ($recipe['title'] ?? ('Recipe #' . $recipeId));
            $kcal = (float) ($recipe['estimated_kcal'] ?? 0);
            $items[] = [
                'id' => $recipeId,
                'title' => $kcal > 0 ? ($title . ' (~' . (int) round($kcal) . ' kcal)') : $title,
                'url' => '/recipes/' . $recipeId,
            ];
        }

        return $items;
    }
}
