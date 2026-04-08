<?php

declare(strict_types=1);

class AdminNotificationCampaignModel extends Model
{
    private bool $ready = false;

    private function ensureTable(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS admin_notification_campaigns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                created_by INT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                action_url VARCHAR(255) NULL,
                target_scope ENUM('all','role','users') NOT NULL DEFAULT 'all',
                target_value TEXT NULL,
                sent_count INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_campaigns_created_at (created_at),
                INDEX idx_campaigns_scope (target_scope)
            )")->execute();

        $this->ready = true;
    }

    public function create(
        ?int $createdBy,
        string $title,
        string $message,
        ?string $actionUrl,
        string $targetScope,
        ?string $targetValue,
        int $sentCount
    ): int {
        $this->ensureTable();

        $ok = $this->db
            ->query('INSERT INTO admin_notification_campaigns
                     (created_by, title, message, action_url, target_scope, target_value, sent_count, created_at)
                     VALUES
                     (:created_by, :title, :message, :action_url, :target_scope, :target_value, :sent_count, NOW())')
            ->bind(':created_by', $createdBy)
            ->bind(':title', mb_substr($title, 0, 255))
            ->bind(':message', mb_substr($message, 0, 2000))
            ->bind(':action_url', $actionUrl !== null ? mb_substr($actionUrl, 0, 255) : null)
            ->bind(':target_scope', $targetScope)
            ->bind(':target_value', $targetValue)
            ->bind(':sent_count', max(0, $sentCount))
            ->execute();

        return $ok ? (int) $this->db->lastInsertId() : 0;
    }

    public function recent(int $limit = 20): array
    {
        $this->ensureTable();
        $this->db->query('SELECT c.*, u.name AS created_by_name, u.email AS created_by_email
                          FROM admin_notification_campaigns c
                          LEFT JOIN users u ON u.id = c.created_by
                          ORDER BY c.created_at DESC, c.id DESC
                          LIMIT :limit')
            ->bind(':limit', max(1, $limit), PDO::PARAM_INT)
            ->execute();
        return $this->db->resultSet();
    }
}

