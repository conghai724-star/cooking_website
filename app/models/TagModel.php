<?php

declare(strict_types=1);

class TagModel extends Model
{
    public function all(): array
    {
        try {
            $this->db->query('SELECT id, name, slug, type FROM tags ORDER BY type ASC, name ASC')->execute();
            return $this->db->resultSet();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function allGroupedByType(): array
    {
        $rows = $this->all();
        $grouped = [];
        foreach ($rows as $row) {
            $type = trim((string) ($row['type'] ?? 'other'));
            if ($type === '') {
                $type = 'other';
            }
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $row;
        }
        return $grouped;
    }

    public function findTagIdsFromMessage(string $message, int $limit = 12): array
    {
        $normalized = $this->normalizeText($message);
        if ($normalized === '') {
            return [];
        }

        $limit = max(1, min(50, $limit));
        try {
            $this->db->query('SELECT DISTINCT ts.tag_id
                              FROM tag_synonyms ts
                              WHERE ts.keyword_norm <> ""
                                AND INSTR(:message_norm, ts.keyword_norm) > 0
                              LIMIT :limit')
                ->bind(':message_norm', $normalized)
                ->bind(':limit', $limit, PDO::PARAM_INT)
                ->execute();
        } catch (Throwable $e) {
            return [];
        }

        $rows = $this->db->resultSet();
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['tag_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public function getKeywordMapByType(string $type): array
    {
        $type = trim($type);
        if ($type === '') {
            return [];
        }

        try {
            $this->db->query(
                'SELECT t.slug, ts.keyword_norm
                 FROM tags t
                 LEFT JOIN tag_synonyms ts ON ts.tag_id = t.id
                 WHERE t.type = :type
                 ORDER BY t.slug ASC, ts.keyword_norm ASC'
            )->bind(':type', $type)->execute();
        } catch (Throwable $e) {
            return [];
        }

        $rows = $this->db->resultSet();
        $map = [];
        foreach ($rows as $row) {
            $slug = $this->normalizeText((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            if (!isset($map[$slug])) {
                $map[$slug] = [$slug];
            }

            $alias = $this->normalizeText((string) ($row['keyword_norm'] ?? ''));
            if ($alias !== '') {
                $map[$slug][] = $alias;
            }
        }

        foreach ($map as $slug => $aliases) {
            $map[$slug] = array_values(array_unique(array_filter($aliases, static fn($v): bool => is_string($v) && $v !== '')));
        }

        return $map;
    }

    private function normalizeText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = str_replace(['đ', 'Đ'], ['d', 'd'], $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        }

        $text = (string) preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
