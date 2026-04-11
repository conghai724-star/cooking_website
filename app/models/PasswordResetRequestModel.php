<?php

declare(strict_types=1);

class PasswordResetRequestModel extends Model
{
    private bool $tableEnsured = false;

    private function ensureTable(): void
    {
        if ($this->tableEnsured) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS password_reset_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uq_password_reset_token_hash (token_hash),
            KEY idx_password_reset_user_pending (user_id, used_at),
            KEY idx_password_reset_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci')->execute();

        $this->tableEnsured = true;
    }

    public function createOrReplace(int $userId, string $tokenHash, string $expiresAt, string $createdAt): bool
    {
        $this->ensureTable();

        $this->db
            ->query('DELETE FROM password_reset_requests WHERE user_id = :user_id AND used_at IS NULL')
            ->bind(':user_id', $userId)
            ->execute();

        return $this->db
            ->query('INSERT INTO password_reset_requests (user_id, token_hash, expires_at, created_at)
                     VALUES (:user_id, :token_hash, :expires_at, :created_at)')
            ->bind(':user_id', $userId)
            ->bind(':token_hash', $tokenHash)
            ->bind(':expires_at', $expiresAt)
            ->bind(':created_at', $createdAt)
            ->execute();
    }

    public function deletePendingRequestsByUserId(int $userId): bool
    {
        $this->ensureTable();

        return $this->db
            ->query('DELETE FROM password_reset_requests WHERE user_id = :user_id AND used_at IS NULL')
            ->bind(':user_id', $userId)
            ->execute();
    }

    public function findByTokenHash(string $tokenHash): array|false
    {
        $this->ensureTable();

        $this->db
            ->query('SELECT id, user_id, expires_at, UNIX_TIMESTAMP(expires_at) AS expires_at_ts, used_at
                     FROM password_reset_requests
                     WHERE token_hash = :token_hash
                     LIMIT 1')
            ->bind(':token_hash', $tokenHash)
            ->execute();

        return $this->db->single();
    }

    public function findLatestPendingRequestByUserId(int $userId): array|false
    {
        $this->ensureTable();

        $this->db
            ->query('SELECT id, user_id, token_hash, created_at, UNIX_TIMESTAMP(created_at) AS created_at_ts, expires_at, UNIX_TIMESTAMP(expires_at) AS expires_at_ts, used_at
                     FROM password_reset_requests
                     WHERE user_id = :user_id AND used_at IS NULL
                     ORDER BY created_at DESC
                     LIMIT 1')
            ->bind(':user_id', $userId)
            ->execute();

        return $this->db->single();
    }

    public function markUsed(int $id): bool
    {
        $this->ensureTable();

        return $this->db
            ->query('UPDATE password_reset_requests SET used_at = NOW() WHERE id = :id')
            ->bind(':id', $id)
            ->execute();
    }
}
