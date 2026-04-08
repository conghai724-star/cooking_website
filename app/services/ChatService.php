<?php

declare(strict_types=1);

require_once APPROOT . '/app/services/chat/IntentMatcher.php';
require_once APPROOT . '/app/services/chat/FAQHandler.php';
require_once APPROOT . '/app/services/chat/RecipeHandler.php';
require_once APPROOT . '/app/services/chat/DietHandler.php';
require_once APPROOT . '/app/services/chat/AIHandler.php';

final class ChatService
{
    private array $intentMap;
    private IntentMatcher $intentMatcher;
    private FAQHandler $faqHandler;
    private RecipeHandler $recipeHandler;
    private DietHandler $dietHandler;
    private AIHandler $aiHandler;

    public function __construct()
    {
        $this->intentMap = require APPROOT . '/config/intent_map.php';
        $this->intentMatcher = new IntentMatcher();
        $this->faqHandler = new FAQHandler();
        $this->recipeHandler = new RecipeHandler();
        $this->dietHandler = new DietHandler();
        $this->aiHandler = new AIHandler();
    }

    public function handle(string $message, array $context = []): array
    {
        $message = trim($message);
        if ($message === '') {
            return [
                'success' => false,
                'code' => 'EMPTY_MESSAGE',
                'message' => 'Vui lòng nhập nội dung câu hỏi.',
            ];
        }

        $context = $this->mergeContext($context);
        $match = $this->intentMatcher->match($message, $this->intentMap);
        if ($match === null) {
            $aiResult = $this->aiHandler->handle($message, $context);
            if (($aiResult['success'] ?? false) === true) {
                $this->saveContext($context);
                return $aiResult;
            }

            $fallback = (array) ($this->intentMap['default_fallback'] ?? []);
            $suggestedIntents = $this->buildFallbackIntentSuggestions($fallback);
            return [
                'success' => true,
                'code' => 'FALLBACK',
                'intent' => null,
                'message' => (string) ($fallback['message'] ?? 'Xin lỗi, tôi chưa hiểu câu hỏi.'),
                'suggestions' => (array) ($fallback['suggestions'] ?? []),
                'suggested_intents' => $suggestedIntents,
                'actions' => $this->buildActionsFromIntentSuggestions($suggestedIntents),
                'source' => 'fallback',
            ];
        }

        $intent = (array) ($match['intent'] ?? []);
        $context['matched_entities'] = (array) ($match['entities'] ?? []);
        $handler = $this->resolveHandler($intent);
        $result = $handler->handle($intent, $message, $context);

        $contextUpdates = (array) ($result['context_updates'] ?? []);
        $context = $this->applyContextUpdates($context, $intent, $contextUpdates);
        $this->saveContext($context);

        $result['success'] = (bool) ($result['success'] ?? true);
        $result['intent'] = (string) ($intent['id'] ?? '');
        $result['intent_type'] = (string) ($intent['type'] ?? 'faq');
        $result['intent_group'] = (string) ($intent['group'] ?? '');
        $result['confidence'] = (float) ($match['score'] ?? 0.0);
        $result['matched_signals'] = (array) ($match['signals'] ?? []);
        $result['matched_entities'] = (array) ($match['entities'] ?? []);

        return $result;
    }

    private function resolveHandler(array $intent): object
    {
        $intentId = (string) ($intent['id'] ?? '');
        $group = (string) ($intent['group'] ?? '');
        $type = (string) ($intent['type'] ?? '');

        if ($type === 'ai') {
            return $this->aiHandler;
        }

        if ($type === 'logic') {
            if (
                $group === 'nutrition'
                || $group === 'nutrition_ai'
                || str_starts_with($intentId, 'diet_')
                || str_starts_with($intentId, 'nutrition_')
                || $intentId === 'recipe_recommend'
            ) {
                return $this->dietHandler;
            }
            return $this->recipeHandler;
        }

        if ($type === 'route') {
            if (in_array($group, ['recipe', 'ingredient', 'tip', 'meal_plan'], true)) {
                return $this->recipeHandler;
            }
            return $this->faqHandler;
        }

        if ($type === 'faq') {
            return $this->faqHandler;
        }

        if ($group === 'nutrition_ai' || str_starts_with($intentId, 'diet_')) {
            return $this->dietHandler;
        }

        if (in_array($group, ['recipe', 'ingredient', 'tip', 'meal_plan'], true)) {
            return $this->recipeHandler;
        }

        return $this->faqHandler;
    }

    private function mergeContext(array $context): array
    {
        $saved = (array) ($_SESSION['chat_context'] ?? []);
        $merged = array_merge($saved, $context);

        if (!isset($merged['chat_state']) || !is_array($merged['chat_state'])) {
            $merged['chat_state'] = [
                'meal' => null,
                'calories' => null,
                'allergies' => [],
            ];
        }

        return $merged;
    }

    private function saveContext(array $context): void
    {
        $allowedScalar = ['last_recipe', 'last_keyword', 'last_intent', 'last_calories_limit', 'last_meal'];
        $allowedArray = ['chat_state'];

        $persisted = [];
        foreach ($allowedScalar as $key) {
            if (!array_key_exists($key, $context)) {
                continue;
            }
            $value = $context[$key];
            if (is_scalar($value) || $value === null) {
                $persisted[$key] = $value;
            }
        }

        foreach ($allowedArray as $key) {
            if (!array_key_exists($key, $context) || !is_array($context[$key])) {
                continue;
            }
            $persisted[$key] = $context[$key];
        }

        $_SESSION['chat_context'] = $persisted;
    }

    private function applyContextUpdates(array $context, array $intent, array $updates): array
    {
        $context['last_intent'] = (string) ($intent['id'] ?? '');

        foreach ($updates as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (is_scalar($value) || $value === null || is_array($value)) {
                $context[$key] = $value;
            }
        }

        $entityMap = (array) ($context['matched_entities'] ?? []);
        if (isset($entityMap['calories'][0])) {
            $context['last_calories_limit'] = (string) $entityMap['calories'][0];
        }
        if (isset($entityMap['meal'][0])) {
            $context['last_meal'] = (string) $entityMap['meal'][0];
        }

        $chatState = is_array($context['chat_state'] ?? null) ? $context['chat_state'] : ['meal' => null, 'calories' => null, 'allergies' => []];
        if (isset($context['last_meal'])) {
            $chatState['meal'] = (string) $context['last_meal'];
        }
        if (isset($context['last_calories_limit'])) {
            $chatState['calories'] = (string) $context['last_calories_limit'];
        }
        if (!isset($chatState['allergies']) || !is_array($chatState['allergies'])) {
            $chatState['allergies'] = [];
        }
        $context['chat_state'] = $chatState;

        return $context;
    }

    private function buildFallbackIntentSuggestions(array $fallback): array
    {
        if (($fallback['suggest_intents'] ?? false) !== true) {
            return [];
        }

        $all = (array) ($this->intentMap['intents'] ?? []);
        $index = [];
        foreach ($all as $intent) {
            if (!is_array($intent)) {
                continue;
            }
            $id = (string) ($intent['id'] ?? '');
            if ($id !== '') {
                $index[$id] = $intent;
            }
        }

        $suggested = [];
        foreach ((array) ($fallback['suggest_intent_ids'] ?? []) as $id) {
            $intent = $index[(string) $id] ?? null;
            if (is_array($intent)) {
                $suggested[] = $intent;
            }
        }

        if ($suggested === []) {
            usort($all, static fn(array $a, array $b): int => ((int) ($b['priority'] ?? 0)) <=> ((int) ($a['priority'] ?? 0)));
            foreach ($all as $intent) {
                if (count($suggested) >= 3) {
                    break;
                }
                if (!is_array($intent)) {
                    continue;
                }
                $suggested[] = $intent;
            }
        }

        return array_map(static function (array $intent): array {
            return [
                'id' => (string) ($intent['id'] ?? ''),
                'type' => (string) ($intent['type'] ?? 'faq'),
                'group' => (string) ($intent['group'] ?? ''),
                'label' => (string) ($intent['response'] ?? ($intent['id'] ?? 'Intent')),
                'route' => (array) ($intent['route'] ?? []),
            ];
        }, $suggested);
    }

    private function buildActionsFromIntentSuggestions(array $suggestedIntents): array
    {
        $actions = [];
        foreach ($suggestedIntents as $intent) {
            if (!is_array($intent)) {
                continue;
            }
            $route = (array) ($intent['route'] ?? []);
            $method = strtoupper((string) ($route['method'] ?? 'GET'));
            $path = trim((string) ($route['path'] ?? ''));
            if ($method !== 'GET' || $path === '' || str_contains($path, '{')) {
                continue;
            }
            $actions[] = [
                'type' => 'link',
                'label' => (string) ($intent['id'] ?? 'Mở tính năng'),
                'url' => $path,
                'method' => 'GET',
            ];
        }
        return $actions;
    }
}



