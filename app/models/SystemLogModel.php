<?php

declare(strict_types=1);

class SystemLogModel extends Model
{
    private bool $ready = false;

    private function ensureTable(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS system_logs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(50) NOT NULL,
                action_key VARCHAR(120) NOT NULL,
                actor_id INT NULL,
                actor_role VARCHAR(50) NULL,
                target_type VARCHAR(50) NULL,
                target_id INT NULL,
                result VARCHAR(20) NOT NULL DEFAULT "success",
                reason VARCHAR(255) NULL,
                meta_json TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_system_logs_event (event_type),
                INDEX idx_system_logs_action (action_key),
                INDEX idx_system_logs_actor (actor_id),
                INDEX idx_system_logs_target (target_type, target_id),
                INDEX idx_system_logs_result (result),
                INDEX idx_system_logs_created_at (created_at)
            )')->execute();

        $this->ready = true;
    }

    public function create(array $payload): bool
    {
        $this->ensureTable();

        return $this->db
            ->query('INSERT INTO system_logs (
                        event_type, action_key, actor_id, actor_role, target_type, target_id,
                        result, reason, meta_json, ip_address, user_agent, created_at
                    ) VALUES (
                        :event_type, :action_key, :actor_id, :actor_role, :target_type, :target_id,
                        :result, :reason, :meta_json, :ip_address, :user_agent, NOW()
                    )')
            ->bind(':event_type', (string) ($payload['event_type'] ?? 'general'))
            ->bind(':action_key', (string) ($payload['action_key'] ?? 'unknown'))
            ->bind(':actor_id', isset($payload['actor_id']) ? (int) $payload['actor_id'] : null)
            ->bind(':actor_role', isset($payload['actor_role']) ? (string) $payload['actor_role'] : null)
            ->bind(':target_type', isset($payload['target_type']) ? (string) $payload['target_type'] : null)
            ->bind(':target_id', isset($payload['target_id']) ? (int) $payload['target_id'] : null)
            ->bind(':result', (string) ($payload['result'] ?? 'success'))
            ->bind(':reason', isset($payload['reason']) ? (string) $payload['reason'] : null)
            ->bind(':meta_json', isset($payload['meta_json']) ? (string) $payload['meta_json'] : null)
            ->bind(':ip_address', isset($payload['ip_address']) ? (string) $payload['ip_address'] : null)
            ->bind(':user_agent', isset($payload['user_agent']) ? (string) $payload['user_agent'] : null)
            ->execute();
    }

    private function buildFilter(array $filters): array
    {
        $where = [];
        $binds = [];

        $eventType = trim((string) ($filters['event_type'] ?? ''));
        if ($eventType !== '') {
            $where[] = 'event_type = :event_type';
            $binds[':event_type'] = $eventType;
        }

        $result = trim((string) ($filters['result'] ?? ''));
        if ($result !== '') {
            $where[] = 'result = :result';
            $binds[':result'] = $result;
        }

        $action = trim((string) ($filters['action_key'] ?? ''));
        if ($action !== '') {
            $where[] = 'action_key = :action_key';
            $binds[':action_key'] = $action;
        }

        $actorId = (int) ($filters['actor_id'] ?? 0);
        if ($actorId > 0) {
            $where[] = 'actor_id = :actor_id';
            $binds[':actor_id'] = $actorId;
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
            $where[] = '(action_key LIKE :kw OR reason LIKE :kw OR meta_json LIKE :kw OR ip_address LIKE :kw)';
            $binds[':kw'] = '%' . $keyword . '%';
        }

        $sqlWhere = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));
        return [$sqlWhere, $binds];
    }

    public function countFiltered(array $filters = []): int
    {
        $this->ensureTable();
        [$sqlWhere, $binds] = $this->buildFilter($filters);
        $query = $this->db->query('SELECT COUNT(*) AS total FROM system_logs ' . $sqlWhere);
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
             FROM system_logs
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
}
