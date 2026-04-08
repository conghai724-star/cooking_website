<?php

declare(strict_types=1);

final class IntentMatcher
{
    public function match(string $message, array $intentMap): ?array
    {
        $intents = (array) ($intentMap['intents'] ?? []);
        if ($intents === []) {
            return null;
        }

        $normalizeConfig = (array) (($intentMap['matcher'] ?? [])['normalize'] ?? []);
        $scoring = (array) (($intentMap['matcher'] ?? [])['scoring'] ?? []);

        $keywordHit = (float) ($scoring['keyword_hit'] ?? 1.0);
        $phraseHit = (float) ($scoring['phrase_hit'] ?? 2.0);
        $regexHit = (float) ($scoring['regex_hit'] ?? 2.5);
        $entityHit = (float) ($scoring['entity_hit'] ?? 1.0);
        $priorityWeight = (float) ($scoring['priority_weight'] ?? 0.1);

        $normalizedMessage = $this->normalize($message, $normalizeConfig);
        $best = null;

        foreach ($intents as $intent) {
            if (!is_array($intent)) {
                continue;
            }

            $score = 0.0;
            $signals = [];
            $matchedEntities = [];
            $hasTextSignal = false;

            foreach ((array) ($intent['keywords'] ?? []) as $keyword) {
                $normalizedKeyword = $this->normalize((string) $keyword, $normalizeConfig);
                if ($normalizedKeyword !== '' && str_contains($normalizedMessage, $normalizedKeyword)) {
                    $score += $keywordHit;
                    $signals[] = 'keyword:' . $normalizedKeyword;
                    $hasTextSignal = true;
                }
            }

            foreach ((array) ($intent['phrases'] ?? []) as $phrase) {
                $normalizedPhrase = $this->normalize((string) $phrase, $normalizeConfig);
                if ($normalizedPhrase !== '' && str_contains($normalizedMessage, $normalizedPhrase)) {
                    $score += $phraseHit;
                    $signals[] = 'phrase:' . $normalizedPhrase;
                    $hasTextSignal = true;
                }
            }

            foreach ((array) ($intent['patterns'] ?? []) as $pattern) {
                $patternText = (string) $pattern;
                if ($patternText !== '' && @preg_match($patternText, $message) === 1) {
                    $score += $regexHit;
                    $signals[] = 'regex:' . $patternText;
                    $hasTextSignal = true;
                }
            }

            foreach ((array) ($intent['entities'] ?? []) as $entityName => $entityRule) {
                if (!is_string($entityName) || trim($entityName) === '') {
                    continue;
                }

                $matchedValues = $this->matchEntity($message, $normalizedMessage, $entityRule, $normalizeConfig);
                if ($matchedValues === []) {
                    continue;
                }

                if (!$hasTextSignal && (($intent['entity_only'] ?? false) !== true)) {
                    continue;
                }

                $matchedEntities[$entityName] = $matchedValues;
                $score += $entityHit * count($matchedValues);
                foreach ($matchedValues as $value) {
                    $signals[] = 'entity:' . $entityName . ':' . $value;
                }
            }

            if ($score <= 0.0) {
                continue;
            }

            $priority = (int) ($intent['priority'] ?? 0);
            $score += $priority * $priorityWeight;

            if ($best === null || $score > (float) ($best['score'] ?? 0.0)) {
                $best = [
                    'intent' => $intent,
                    'score' => $score,
                    'signals' => $signals,
                    'entities' => $matchedEntities,
                ];
            }
        }

        return $best;
    }

    private function normalize(string $text, array $config): string
    {
        if (($config['trim'] ?? true) === true) {
            $text = trim($text);
        }

        if (($config['lowercase'] ?? true) === true) {
            $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        }

        if (($config['remove_diacritics'] ?? false) === true) {
            $text = $this->removeVietnameseDiacritics($text);
        }

        if (($config['collapse_spaces'] ?? true) === true) {
            $text = (string) preg_replace('/\s+/u', ' ', $text);
        }

        return $text;
    }

    private function removeVietnameseDiacritics(string $text): string
    {
        $patterns = [
            '/[Ă Ă¡A�º¡A�º£Ă£Ă¢A�º§A�º¥A�º­A�º©A�º«A�ƒA�º±A�º¯A�º·A�º³A�ºµ]/u' => 'a',
            '/[Ă€ĂA�º A�º¢ĂƒĂ�?A�º¦A�º¤A�º¬A�º¨A�ºªA��?A�º°A�º®A�º¶A�º²A�º´]/u' => 'A',
            '/[Ă¨Ă©A�º¹A�º»A�º½ĂªA�»A�º¿A�»‡A�»ƒA�»…]/u' => 'e',
            '/[ĂˆĂ‰A�º¸A�ººA�º¼Ă�?A�»€A�º¾A�»†A�»�?A�»�?]/u' => 'E',
            '/[Ă¬Ă­A�»‹A�»‰A�©]/u' => 'i',
            '/[ĂŒĂA�»�?A�»ˆA�¨]/u' => 'I',
            '/[Ă²Ă³A�»A�»ĂµĂ´A�»“A�»‘A�»™A�»•A�»—A�¡A�»A�»›A�»£A�»ŸA�»¡]/u' => 'o',
            '/[Ă’Ă“A�»ŒA�»�?Ă•Ă”A�»’A�»A�»˜A�»”A�»–A� A�»œA�»�?A�»¢A�»�?A�» ]/u' => 'O',
            '/[Ă¹ĂºA�»¥A�»§A�©A�°A�»«A�»©A�»±A�»­A�»¯]/u' => 'u',
            '/[Ă™Ă�?A�»¤A�»¦A�¨A�¯A�»ªA�»¨A�»°A�»¬A�»®]/u' => 'U',
            '/[A�»³Ă½A�»µA�»·A�»¹]/u' => 'y',
            '/[A�»²ĂA�»´A�»¶A�»¸]/u' => 'Y',
            '/[Ä‚â€Ă¢â‚¬Ëœ]/u' => 'd',
            '/[Đ]/u' => 'D',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $text = (string) preg_replace($pattern, $replacement, $text);
        }

        $text = (string) preg_replace('/[^A-Za-z0-9\s]/u', ' ', $text);
        return $text;
    }

    private function matchEntity(string $rawMessage, string $normalizedMessage, mixed $rule, array $normalizeConfig): array
    {
        $matches = [];

        if (is_array($rule)) {
            foreach ($rule as $token) {
                $normalizedToken = $this->normalize((string) $token, $normalizeConfig);
                if ($normalizedToken === '') {
                    continue;
                }
                if (str_contains($normalizedMessage, $normalizedToken)) {
                    $matches[] = $normalizedToken;
                }
            }
            return array_values(array_unique($matches));
        }

        if (!is_string($rule) || trim($rule) === '') {
            return [];
        }

        $pattern = '/' . str_replace('/', '\/', trim($rule)) . '/u';
        $regexMatches = [];
        if (@preg_match_all($pattern, $rawMessage, $regexMatches) !== 1) {
            return [];
        }

        $values = (array) ($regexMatches[0] ?? []);
        $normalizedValues = [];
        foreach ($values as $value) {
            $normalizedValue = $this->normalize((string) $value, $normalizeConfig);
            if ($normalizedValue !== '') {
                $normalizedValues[] = $normalizedValue;
            }
        }

        return array_values(array_unique($normalizedValues));
    }
}
