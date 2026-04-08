<?php

declare(strict_types=1);

class LoginAttemptModel extends Model
{
    private bool $tableReady = false;

    private function ensureTable(): void
    {
        if ($this->tableReady) {
            return;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS login_attempts (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    credential VARCHAR(255) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    failed_count INT NOT NULL DEFAULT 0,
                    lock_until DATETIME NULL,
                    last_attempt_at DATETIME NULL,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_login_attempt_credential_ip (credential, ip_address),
                    KEY idx_login_attempt_lock_until (lock_until)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $this->db->query($sql)->execute();
        $this->tableReady = true;
    }

    public function getLockInfo(string $credential, string $ipAddress): array
    {
        $this->ensureTable();

        $this->db->query('SELECT failed_count, lock_until
                          FROM login_attempts
                          WHERE credential = :credential AND ip_address = :ip_address
                          LIMIT 1')
            ->bind(':credential', $credential)
            ->bind(':ip_address', $ipAddress)
            ->execute();

        $row = $this->db->single();
        return is_array($row) ? $row : [];
    }

    public function registerFailure(string $credential, string $ipAddress, int $maxAttempts = 5, int $lockMinutes = 15): void
    {
        $this->ensureTable();

        $this->db->query('INSERT INTO login_attempts (credential, ip_address, failed_count, last_attempt_at)
                          VALUES (:credential, :ip_address, 1, NOW())
                          ON DUPLICATE KEY UPDATE
                              failed_count = failed_count + 1,
                              last_attempt_at = NOW()')
            ->bind(':credential', $credential)
            ->bind(':ip_address', $ipAddress)
            ->execute();

        $this->db->query('UPDATE login_attempts
                          SET lock_until = CASE
                                WHEN failed_count >= :max_attempts THEN DATE_ADD(NOW(), INTERVAL :lock_minutes MINUTE)
                                ELSE lock_until
                              END
                          WHERE credential = :credential AND ip_address = :ip_address')
            ->bind(':max_attempts', $maxAttempts, PDO::PARAM_INT)
            ->bind(':lock_minutes', $lockMinutes, PDO::PARAM_INT)
            ->bind(':credential', $credential)
            ->bind(':ip_address', $ipAddress)
            ->execute();
    }

    public function clear(string $credential, string $ipAddress): void
    {
        $this->ensureTable();

        $this->db->query('DELETE FROM login_attempts WHERE credential = :credential AND ip_address = :ip_address')
            ->bind(':credential', $credential)
            ->bind(':ip_address', $ipAddress)
            ->execute();
    }
}
