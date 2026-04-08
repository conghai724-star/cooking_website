<?php

declare(strict_types=1);

class ChatHistoryModel extends Model
{
    private bool $ready = false;

    private function ensureTable(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS chat_histories (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                session_id VARCHAR(128) NULL,
                user_message TEXT NOT NULL,
                bot_message TEXT NULL,
                matched_intent VARCHAR(120) NULL,
                confidence_score DECIMAL(10,4) NULL,
                result_code VARCHAR(60) NULL,
                http_status SMALLINT NOT NULL DEFAULT 200,
                source VARCHAR(60) NULL,
                latency_ms DECIMAL(10,2) NULL,
                meta_json TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_chat_histories_user (user_id),
                INDEX idx_chat_histories_intent (matched_intent),
                INDEX idx_chat_histories_created (created_at)
            )')->execute();

        $this->ready = true;
    }

    public function create(array $payload): bool
    {
        $this->ensureTable();

        return $this->db
            ->query('INSERT INTO chat_histories (
                        user_id, session_id, user_message, bot_message, matched_intent,
                        confidence_score, result_code, http_status, source, latency_ms, meta_json, created_at
                    ) VALUES (
                        :user_id, :session_id, :user_message, :bot_message, :matched_intent,
                        :confidence_score, :result_code, :http_status, :source, :latency_ms, :meta_json, NOW()
                    )')
            ->bind(':user_id', isset($payload['user_id']) ? (int) $payload['user_id'] : null)
            ->bind(':session_id', isset($payload['session_id']) ? (string) $payload['session_id'] : null)
            ->bind(':user_message', (string) ($payload['user_message'] ?? ''))
            ->bind(':bot_message', isset($payload['bot_message']) ? (string) $payload['bot_message'] : null)
            ->bind(':matched_intent', isset($payload['matched_intent']) ? (string) $payload['matched_intent'] : null)
            ->bind(':confidence_score', isset($payload['confidence_score']) ? (float) $payload['confidence_score'] : null)
            ->bind(':result_code', isset($payload['result_code']) ? (string) $payload['result_code'] : null)
            ->bind(':http_status', (int) ($payload['http_status'] ?? 200))
            ->bind(':source', isset($payload['source']) ? (string) $payload['source'] : null)
            ->bind(':latency_ms', isset($payload['latency_ms']) ? (float) $payload['latency_ms'] : null)
            ->bind(':meta_json', isset($payload['meta_json']) ? (string) $payload['meta_json'] : null)
            ->execute();
    }

    private function buildFilter(array $filters): array
    {
        $where = [];
        $binds = [];

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 'user_id = :user_id';
            $binds[':user_id'] = $userId;
        }

        $intent = trim((string) ($filters['intent'] ?? ''));
        if ($intent !== '') {
            $where[] = 'matched_intent = :intent';
            $binds[':intent'] = $intent;
        }

        $code = trim((string) ($filters['code'] ?? ''));
        if ($code !== '') {
            $where[] = 'result_code = :code';
            $binds[':code'] = $code;
        }

        $from = trim((string) ($filters['from'] ?? ''));
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where[] = 'created_at >= :from_date';
            $binds[':from_date'] = $from . ' 00:00:00';
        }

        $to = trim((string) ($filters['to'] ?? ''));
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where[] = 'created_at <= :to_date';
            $binds[':to_date'] = $to . ' 23:59:59';
        }

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $where[] = '(user_message LIKE :kw OR bot_message LIKE :kw OR meta_json LIKE :kw)';
            $binds[':kw'] = '%' . $keyword . '%';
        }

        $sqlWhere = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));
        return [$sqlWhere, $binds];
    }

    public function countFiltered(array $filters = []): int
    {
        $this->ensureTable();
        [$sqlWhere, $binds] = $this->buildFilter($filters);
        $query = $this->db->query('SELECT COUNT(*) AS total FROM chat_histories ' . $sqlWhere);
        foreach ($binds as $key => $value) {
            $query->bind($key, $value);
        }
        $query->execute();
        $row = $query->single();
        return (int) ($row['total'] ?? 0);
    }

    public function listFiltered(int $limit, int $offset, array $filters = []): array
    {
        $this->ensureTable();
        [$sqlWhere, $binds] = $this->buildFilter($filters);
        $query = $this->db->query(
            'SELECT *
             FROM chat_histories
             ' . $sqlWhere . '
             ORDER BY id DESC
             LIMIT :limit OFFSET :offset'
        )
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->bind(':offset', $offset, PDO::PARAM_INT);

        foreach ($binds as $key => $value) {
            $query->bind($key, $value);
        }

        $query->execute();
        return $query->resultSet();
    }

    public function latestStateByUser(int $limit = 20): array
    {
        $this->ensureTable();
        $limit = max(1, min(100, $limit));

        $query = $this->db->query(
            'SELECT ch.user_id, ch.meta_json, ch.created_at
             FROM chat_histories ch
             INNER JOIN (
                SELECT user_id, MAX(id) AS max_id
                FROM chat_histories
                WHERE user_id IS NOT NULL
                GROUP BY user_id
             ) x ON x.max_id = ch.id
             ORDER BY ch.id DESC
             LIMIT :limit'
        )->bind(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->resultSet();
    }
}
