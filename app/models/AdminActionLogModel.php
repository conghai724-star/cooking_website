<?php

declare(strict_types=1);

class AdminActionLogModel extends Model
{
    private bool $ready = false;

    private function ensureTable(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS admin_action_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NULL,
                action_key VARCHAR(100) NOT NULL,
                target_type VARCHAR(50) NOT NULL,
                target_id INT NULL,
                details TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_action_logs_admin (admin_id),
                INDEX idx_admin_action_logs_action (action_key),
                INDEX idx_admin_action_logs_target (target_type, target_id),
                INDEX idx_admin_action_logs_created_at (created_at)
            )')->execute();

        $this->ready = true;
    }

    public function create(?int $adminId, string $actionKey, string $targetType, ?int $targetId = null, ?string $details = null): bool
    {
        $this->ensureTable();
        return $this->db
            ->query('INSERT INTO admin_action_logs (admin_id, action_key, target_type, target_id, details, created_at)
                     VALUES (:admin_id, :action_key, :target_type, :target_id, :details, NOW())')
            ->bind(':admin_id', $adminId)
            ->bind(':action_key', $actionKey)
            ->bind(':target_type', $targetType)
            ->bind(':target_id', $targetId)
            ->bind(':details', $details)
            ->execute();
    }
}
