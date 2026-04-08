<?php

declare(strict_types=1);

require_once APPROOT . '/app/models/RecipeModel.php';
require_once APPROOT . '/app/models/IngredientAliasModel.php';
require_once APPROOT . '/app/models/IngredientModel.php';
require_once APPROOT . '/app/models/TipModel.php';
require_once APPROOT . '/app/models/TagModel.php';

final class DietHandler
{
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
        $tagIds = $this->extractTagIds($message);

        $isLowCalorieIntent = in_array($intentId, ['diet_low_calorie', 'diet_weight_loss'], true);
        if ($isLowCalorieIntent && $ingredientIds === []) {
            $ingredientIds = $this->resolveLowCalorieIngredientIds($calories);
        }

        $hasSpecificFilters = $keyword !== null || $methodKeywords !== [] || $flavorKeywords !== [] || $ingredientIds !== [] || $tagIds !== [];

        try {
            $recipeModel = new RecipeModel();
            $recipes = $this->findExactRecipes(
                $recipeModel,
                $ingredientIds,
                $tagIds,
                $meal,
                $calories,
                $recipeKeyword,
                $keyword,
                $methodKeywords,
                $flavorKeywords,
                $allergies,
                5
            );
        } catch (Throwable $e) {
            return [
                'success' => true,
                'code' => 'DIET_TEMP_UNAVAILABLE',
                'message' => 'Tinh nang goi y tam thoi chua san sang. Ban thu lai sau it phut.',
                'source' => 'diet_handler',
            ];
        }

        $items = $this->mapRecipeItems($recipes);
        $nextState = [
            'meal' => $meal,
            'calories' => $calories,
            'allergies' => $allergies,
        ];

        if ($items !== []) {
            return [
                'success' => true,
                'code' => 'DIET_MATCHED',
                'message' => (string) ($intent['response'] ?? 'Minh da tim duoc mot so mon phu hop cho ban.'),
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

        $supportItems = $this->buildSupportSuggestions($message, $keyword, $methodKeywords, $flavorKeywords);
        if ($supportItems !== []) {
            $supportMessage = $hasSpecificFilters
                ? 'Khong co cong thuc mon do theo dung tieu chi, nhung minh da noi long dieu kien de goi y cac mon gan giong.'
                : 'Chua co mon khop hoan toan, nhung minh co vai goi y gan dung de ban chon nhanh.';

            return [
                'success' => true,
                'code' => 'DIET_SUPPORT_SUGGESTED',
                'message' => $supportMessage,
                'items' => $supportItems,
                'actions' => $this->buildActionsFromItems($supportItems),
                'suggestions' => [
                    'Goi y mon xao it calo',
                    'Mon xao cay duoi 500 kcal',
                    'Mon xao khong thit bo',
                ],
                'context_updates' => [
                    'last_keyword' => $recipeKeyword ?? $keyword,
                    'chat_state' => $nextState,
                ],
                'source' => 'diet_handler',
            ];
        }

        return [
            'success' => true,
            'code' => 'DIET_NO_RESULT',
            'message' => 'Minh chua tim thay noi dung phu hop. Ban vao trang Cong thuc de xem toan bo va loc them theo nhu cau.',
            'actions' => [[
                'type' => 'link',
                'label' => 'Mo trang Cong thuc',
                'url' => '/recipes',
                'method' => 'GET',
            ]],
            'suggestions' => (array) ($intent['suggest_next'] ?? []),
            'context_updates' => [
                'last_keyword' => $recipeKeyword ?? $keyword,
                'chat_state' => $nextState,
            ],
            'source' => 'diet_handler',
        ];
    }

    private function findExactRecipes(
        RecipeModel $recipeModel,
        array $ingredientIds,
        array $tagIds,
        ?string $meal,
        ?int $calories,
        ?string $recipeKeyword,
        ?string $keyword,
        array $methodKeywords,
        array $flavorKeywords,
        array $allergies,
        int $limit
    ): array {
        $candidates = $this->buildKeywordCandidates($recipeKeyword, $keyword, $methodKeywords, $flavorKeywords);
        if ($candidates === []) {
            $candidates = [null];
        }

        if ($tagIds !== []) {
            foreach ($candidates as $candidate) {
                $rows = $recipeModel->recommendByTagIds($tagIds, $calories, $candidate, $allergies, $limit);
                if ($rows !== []) {
                    return $rows;
                }
            }

            // If keyword is too strict, keep tag filter and relax text filter.
            $rows = $recipeModel->recommendByTagIds($tagIds, $calories, null, $allergies, $limit);
            if ($rows !== []) {
                return $rows;
            }
        }
        if ($ingredientIds !== []) {
            foreach ($candidates as $candidate) {
                $rows = $recipeModel->recommendByIngredientIds($ingredientIds, $calories, $candidate, $allergies, $limit);
                if ($rows !== []) {
                    return $rows;
                }
            }
            return [];
        }

        foreach ($candidates as $candidate) {
            $rows = $recipeModel->recommendForChat($meal, $calories, $candidate, $allergies, $limit);
            if ($rows !== []) {
                return $rows;
            }
        }

        return [];
    }

    private function buildKeywordCandidates(?string $recipeKeyword, ?string $keyword, array $methodKeywords, array $flavorKeywords): array
    {
        $candidates = [];

        if (is_string($recipeKeyword) && trim($recipeKeyword) !== '') {
            $candidates[] = trim($recipeKeyword);
        }
        if (is_string($keyword) && trim($keyword) !== '') {
            $candidates[] = trim($keyword);
        }

        foreach ($methodKeywords as $method) {
            $method = trim((string) $method);
            if ($method !== '') {
                $candidates[] = $method;
            }
        }

        foreach ($flavorKeywords as $flavor) {
            $flavor = trim((string) $flavor);
            if ($flavor !== '') {
                $candidates[] = $flavor;
            }
        }

        $normalized = [];
        $seen = [];
        foreach ($candidates as $candidate) {
            $key = trim((string) $candidate);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $normalized[] = $key;
        }

        return $normalized;
    }

    private function buildSupportSuggestions(string $message, ?string $keyword, array $methodKeywords, array $flavorKeywords): array
    {
        $items = [];
        $mainKeyword = $this->pickSupportKeyword($message, $keyword, $methodKeywords, $flavorKeywords);

        try {
            $recipeModel = new RecipeModel();
            $rows = $recipeModel->recommendForChat(null, null, $mainKeyword, [], 3);
            if ($rows === []) {
                $rows = $recipeModel->recommendForChat(null, null, null, [], 3);
            }
            foreach ($this->mapRecipeItems($rows) as $item) {
                $items[] = $item;
            }
        } catch (Throwable $e) {
            // no-op
        }

        try {
            $ingredientModel = new IngredientModel();
            $rows = $ingredientModel->allPaged('approved', 'library', 2, 0, $mainKeyword);
            if ($rows === []) {
                $rows = $ingredientModel->allPaged(null, 'library', 2, 0, $mainKeyword);
            }
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                $name = trim((string) ($row['name'] ?? ''));
                if ($id <= 0 || $name === '') {
                    continue;
                }
                $items[] = [
                    'id' => $id,
                    'title' => 'Nguyen lieu: ' . $name,
                    'url' => '/ingredients/' . $id,
                ];
            }
        } catch (Throwable $e) {
            // no-op
        }

        try {
            $tipModel = new TipModel();
            $rows = $tipModel->allPaged('approved', 2, 0, $mainKeyword);
            if ($rows === []) {
                $rows = $tipModel->allPaged(null, 2, 0, $mainKeyword);
            }
            foreach ($rows as $row) {
                $slug = trim((string) ($row['slug'] ?? ''));
                $title = trim((string) ($row['title'] ?? ''));
                if ($slug === '' || $title === '') {
                    continue;
                }
                $items[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'title' => 'Meo: ' . $title,
                    'url' => '/tips/' . $slug,
                ];
            }
        } catch (Throwable $e) {
            // no-op
        }

        return array_slice($this->uniqueItems($items), 0, 6);
    }

    private function pickSupportKeyword(string $message, ?string $keyword, array $methodKeywords, array $flavorKeywords): ?string
    {
        if (is_string($keyword) && trim($keyword) !== '') {
            return trim($keyword);
        }
        if ($methodKeywords !== []) {
            return (string) $methodKeywords[0];
        }
        if ($flavorKeywords !== []) {
            return (string) $flavorKeywords[0];
        }

        $normalized = $this->normalizeText($message);
        return $normalized !== '' ? $normalized : null;
    }

    private function uniqueItems(array $items): array
    {
        $seen = [];
        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($title === '' || $url === '') {
                continue;
            }

            $key = strtolower($title . '|' . $url);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $item;
        }

        return $result;
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

    private function resolveMeal(array $matchedEntities, array $chatState): ?string
    {
        $raw = isset($matchedEntities['meal'][0])
            ? (string) $matchedEntities['meal'][0]
            : ((string) ($chatState['meal'] ?? ''));

        $raw = $this->normalizeText($raw);
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, 'sang')) {
            return 'sang';
        }
        if (str_contains($raw, 'trua')) {
            return 'trua';
        }
        if (str_contains($raw, 'toi')) {
            return 'toi';
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
        $known = ['tom', 'cua', 'ca', 'bo', 'heo', 'ga', 'trung', 'sua', 'dau phong', 'lac'];
        $normalized = $this->normalizeText($message);

        if (str_contains($normalized, 'khong di ung') || str_contains($normalized, 'bo di ung')) {
            return [];
        }

        $detected = [];
        foreach ($known as $token) {
            if (
                str_contains($normalized, 'di ung ' . $token)
                || str_contains($normalized, 'khong an ' . $token)
                || str_contains($normalized, 'tranh ' . $token)
            ) {
                $detected[] = $token;
            }
        }

        $previous = is_array($chatState['allergies'] ?? null) ? $chatState['allergies'] : [];
        return array_values(array_unique(array_filter(array_merge($previous, $detected), static fn($v): bool => is_string($v) && trim($v) !== '')));
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

    private function extractTagIds(string $message): array
    {
        try {
            $tagModel = new TagModel();
            return $tagModel->findTagIdsFromMessage($message, 12);
        } catch (Throwable $e) {
            return [];
        }
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

    private function resolveCookingMethodKeywords(array $matchedEntities, string $message): array
    {
        $accepted = ['canh', 'chien', 'xao', 'hap', 'nuong', 'kho'];
        $normalizedMessage = $this->normalizeText($message);
        $values = [];

        foreach ((array) ($matchedEntities['cooking_method'] ?? []) as $value) {
            $token = $this->normalizeText((string) $value);
            if ($token !== '' && in_array($token, $accepted, true)) {
                $values[] = $token;
            }
        }

        if ($values === []) {
            foreach ($accepted as $token) {
                if (str_contains($normalizedMessage, $token)) {
                    $values[] = $token;
                }
            }
        }

        if ($values === [] && str_contains($normalizedMessage, 'xoa')) {
            $values[] = 'xao';
        }

        return array_values(array_unique($values));
    }

    private function resolveFlavorKeywords(array $matchedEntities, string $message): array
    {
        $accepted = ['cay', 'chua', 'ngot', 'man', 'thanh'];
        $normalizedMessage = $this->normalizeText($message);
        $values = [];

        foreach ((array) ($matchedEntities['flavor'] ?? []) as $value) {
            $token = $this->normalizeText((string) $value);
            if ($token !== '' && in_array($token, $accepted, true)) {
                $values[] = $token;
            }
        }

        if ($values === []) {
            foreach ($accepted as $token) {
                if (str_contains($normalizedMessage, $token)) {
                    $values[] = $token;
                }
            }
        }

        return array_values(array_unique($values));
    }

    private function mergeRecipeKeyword(?string $keyword, array $methodKeywords, array $flavorKeywords): ?string
    {
        $parts = [];

        if (is_string($keyword) && trim($keyword) !== '') {
            $parts[] = trim($keyword);
        }
        foreach ($methodKeywords as $token) {
            $token = trim((string) $token);
            if ($token !== '') {
                $parts[] = $token;
            }
        }
        foreach ($flavorKeywords as $token) {
            $token = trim((string) $token);
            if ($token !== '') {
                $parts[] = $token;
            }
        }

        $parts = array_values(array_unique($parts));
        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
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
                'message' => 'Ban hay gui ten mon cu the de minh uoc tinh calo chinh xac hon.',
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
                'message' => 'Hien tai minh chua lay duoc du lieu calo. Ban thu lai sau.',
                'source' => 'diet_handler',
            ];
        }

        if ($rows === []) {
            return [
                'success' => true,
                'code' => 'CALORIES_NO_RESULT',
                'message' => 'Minh chua tim thay mon phu hop de tinh calo. Ban thu gui ten mon day du hon.',
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
                : ('Minh chua co so kcal cho mon ' . $title . '.'),
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

        $stopPhrases = [
            'toi co',
            'toi muon',
            'cach nau',
            'nau mon',
            'huong dan nau',
            'chi cach nau',
            'chi toi cach nau',
            'cach lam',
            'lam mon',
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
        ];

        foreach ($stopPhrases as $phrase) {
            $text = str_replace($phrase, ' ', $text);
        }

        $text = (string) preg_replace('/\d+/', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

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
}