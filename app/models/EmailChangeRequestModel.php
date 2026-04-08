<?php

declare(strict_types=1);

class EmailChangeRequestModel extends Model
{
    private bool $tableEnsured = false;

    private function ensureTable(): void
    {
        if ($this->tableEnsured) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS email_change_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            new_email VARCHAR(255) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_email_change_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uq_email_change_token_hash (token_hash),
            KEY idx_email_change_user_pending (user_id, used_at),
            KEY idx_email_change_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci')->execute();

        $this->tableEnsured = true;
    }

    public function createOrReplace(int $userId, string $newEmail, string $tokenHash, string $expiresAt): bool
    {
        $this->ensureTable();

        $this->db
            ->query('DELETE FROM email_change_requests WHERE user_id = :user_id AND used_at IS NULL')
            ->bind(':user_id', $userId)
            ->execute();

        return $this->db
            ->query('INSERT INTO email_change_requests (user_id, new_email, token_hash, expires_at, created_at)
                     VALUES (:user_id, :new_email, :token_hash, :expires_at, NOW())')
            ->bind(':user_id', $userId)
            ->bind(':new_email', $newEmail)
            ->bind(':token_hash', $tokenHash)
            ->bind(':expires_at', $expiresAt)
            ->execute();
    }

    public function findByTokenHash(string $tokenHash): array|false
    {
        $this->ensureTable();

        $this->db->query('SELECT id, user_id, new_email, expires_at, used_at
                          FROM email_change_requests
                          WHERE token_hash = :token_hash
                          LIMIT 1')
            ->bind(':token_hash', $tokenHash)
            ->execute();

        return $this->db->single();
    }

    public function markUsed(int $id): bool
    {
        $this->ensureTable();

        return $this->db
            ->query('UPDATE email_change_requests SET used_at = NOW() WHERE id = :id')
            ->bind(':id', $id)
            ->execute();
    }
}
