<?php

declare(strict_types=1);

class UserModel extends Model
{
    public function createWithRole(string $name, string $email, string $password, string $role): bool
    {
        $baseUsername = trim((string) preg_replace('/[^a-z0-9_]+/i', '_', strstr($email, '@', true) ?: $email), '_');
        if ($baseUsername === '') {
            $baseUsername = 'user';
        }

        $username = $baseUsername;
        $suffix = 1;
        while ($this->findByUsername($username)) {
            $suffix++;
            $username = $baseUsername . '_' . $suffix;
        }

        return $this->db
            ->query('INSERT INTO users (username, name, email, password, role, status, created_at)
                     VALUES (:username, :name, :email, :password, :role, :status, NOW())')
            ->bind(':username', $username)
            ->bind(':name', $name)
            ->bind(':email', $email)
            ->bind(':password', password_hash($password, PASSWORD_DEFAULT))
            ->bind(':role', $role)
            ->bind(':status', 'active')
            ->execute();
    }

    public function updateRoleById(int $id, string $role): bool
    {
        return $this->db
            ->query('UPDATE users SET role = :role WHERE id = :id')
            ->bind(':role', $role)
            ->bind(':id', $id)
            ->execute();
    }

    public function listRoleNames(): array
    {
        try {
            $this->db->query('SELECT role_name FROM roles ORDER BY role_name ASC')->execute();
            $rows = $this->db->resultSet();
            $roles = [];
            foreach ($rows as $row) {
                $name = (string) ($row['role_name'] ?? '');
                if ($name !== '') {
                    $roles[] = $name;
                }
            }
            if ($roles !== []) {
                return $roles;
            }
        } catch (Throwable $e) {
            // fallback below
        }

        return ['user', 'super_admin', 'mod', 'support'];
    }

    public function create(string $username, string $email, string $password): bool
    {
        $sql = 'INSERT INTO users (username, name, email, password, role, status, created_at)
                VALUES (:username, :name, :email, :password, :role, :status, NOW())';
        return $this->db
            ->query($sql)
            ->bind(':username', $username)
            ->bind(':name', $username)
            ->bind(':email', $email)
            ->bind(':password', password_hash($password, PASSWORD_DEFAULT))
            ->bind(':role', 'user')
            ->bind(':status', 'active')
            ->execute();
    }

    public function findByUsername(string $username): array|false
    {
        $this->db->query('SELECT * FROM users WHERE username = :username LIMIT 1')
            ->bind(':username', $username)
            ->execute();

        return $this->db->single();
    }

    public function findByEmail(string $email): array|false
    {
        $this->db->query('SELECT * FROM users WHERE email = :email LIMIT 1')
            ->bind(':email', $email)
            ->execute();

        return $this->db->single();
    }

    public function findByEmailExceptId(string $email, int $excludeId): array|false
    {
        $this->db->query('SELECT * FROM users WHERE email = :email AND id <> :exclude_id LIMIT 1')
            ->bind(':email', $email)
            ->bind(':exclude_id', $excludeId)
            ->execute();

        return $this->db->single();
    }

    public function findById(int $id): array|false
    {
        $this->db->query('SELECT id, username, name, full_name, email, avatar, bio, role, created_at FROM users WHERE id = :id LIMIT 1')
            ->bind(':id', $id)
            ->execute();

        return $this->db->single();
    }

    public function updateProfile(int $id, string $name, string $email): bool
    {
        return $this->db
            ->query('UPDATE users SET name = :name, email = :email WHERE id = :id')
            ->bind(':name', $name)
            ->bind(':email', $email)
            ->bind(':id', $id)
            ->execute();
    }

    public function findAuthById(int $id): array|false
    {
        $this->db->query('SELECT id, email, password FROM users WHERE id = :id LIMIT 1')
            ->bind(':id', $id)
            ->execute();

        return $this->db->single();
    }

    public function updateEmail(int $id, string $email): bool
    {
        return $this->db
            ->query('UPDATE users SET email = :email WHERE id = :id')
            ->bind(':email', $email)
            ->bind(':id', $id)
            ->execute();
    }

    public function updateProfileDetails(int $id, string $name, string $email, string $bio, ?string $avatar): bool
    {
        return $this->db
            ->query('UPDATE users
                     SET name = :name,
                         email = :email,
                         bio = :bio,
                         avatar = :avatar
                     WHERE id = :id')
            ->bind(':name', $name)
            ->bind(':email', $email)
            ->bind(':bio', $bio)
            ->bind(':avatar', $avatar)
            ->bind(':id', $id)
            ->execute();
    }

    public function updatePassword(int $id, string $password): bool
    {
        return $this->db
            ->query('UPDATE users SET password = :password WHERE id = :id')
            ->bind(':password', password_hash($password, PASSWORD_DEFAULT))
            ->bind(':id', $id)
            ->execute();
    }

    public function all(): array
    {
        $this->db->query('SELECT id, name, email, role, created_at FROM users ORDER BY id DESC')->execute();
        return $this->db->resultSet();
    }

    public function countForAdmin(?string $keyword = null, ?string $state = null): int
    {
        $this->clearExpiredBans();
        $where = [];
        if ($keyword !== null && $keyword !== '') {
            $where[] = '(u.name LIKE :kw_name OR u.email LIKE :kw_email)';
        }
        if ($state === 'active') {
            $where[] = 'u.deleted_at IS NULL AND ub.id IS NULL';
        } elseif ($state === 'banned') {
            $where[] = 'u.deleted_at IS NULL AND ub.id IS NOT NULL';
        } elseif ($state === 'deleted') {
            $where[] = 'u.deleted_at IS NOT NULL';
        }

        $sql = "SELECT COUNT(*) AS total
                FROM users u
                LEFT JOIN user_bans ub ON ub.user_id = u.id AND ub.is_active = 1";
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $query = $this->db->query($sql);
        if ($keyword !== null && $keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->bind(':kw_name', $like)->bind(':kw_email', $like);
        }
        $query->execute();
        $row = $query->single();
        return (int) ($row['total'] ?? 0);
    }

    public function allForAdminPaged(int $limit, int $offset, ?string $keyword = null, ?string $state = null): array
    {
        $this->clearExpiredBans();
        $where = [];
        if ($keyword !== null && $keyword !== '') {
            $where[] = '(u.name LIKE :kw_name OR u.email LIKE :kw_email)';
        }
        if ($state === 'active') {
            $where[] = 'u.deleted_at IS NULL AND ub.id IS NULL';
        } elseif ($state === 'banned') {
            $where[] = 'u.deleted_at IS NULL AND ub.id IS NOT NULL';
        } elseif ($state === 'deleted') {
            $where[] = 'u.deleted_at IS NOT NULL';
        }

        $sql = "SELECT u.id, u.name, u.email, u.role, u.status, u.deleted_at,
                       ub.reason AS ban_reason, ub.ban_until AS banned_until, ub.ban_type,
                       u.created_at,
                       CASE
                           WHEN u.deleted_at IS NOT NULL THEN 'deleted'
                           WHEN ub.id IS NOT NULL THEN 'banned'
                           ELSE 'active'
                       END AS account_state
                FROM users u
                LEFT JOIN user_bans ub ON ub.user_id = u.id AND ub.is_active = 1";
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY u.id DESC LIMIT :limit OFFSET :offset';

        $query = $this->db->query($sql);
        if ($keyword !== null && $keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->bind(':kw_name', $like)->bind(':kw_email', $like);
        }
        $query->bind(':limit', $limit)->bind(':offset', $offset)->execute();

        return $query->resultSet();
    }

    public function banById(int $id, int $bannedBy, ?string $reason = null, ?string $bannedUntil = null): bool
    {
        $this->db
            ->query('UPDATE user_bans SET is_active = 0 WHERE user_id = :user_id AND is_active = 1')
            ->bind(':user_id', $id)
            ->execute();

        $banType = $bannedUntil === null ? 'permanent' : 'temporary';
        $inserted = $this->db
            ->query("INSERT INTO user_bans (user_id, banned_by, reason, ban_type, ban_until, is_active)
                     SELECT u.id, :banned_by, :reason, :ban_type, :ban_until, 1
                     FROM users u
                     WHERE u.id = :id AND u.role = 'user' AND u.deleted_at IS NULL")
            ->bind(':banned_by', $bannedBy > 0 ? $bannedBy : null)
            ->bind(':reason', $reason)
            ->bind(':ban_type', $banType)
            ->bind(':ban_until', $bannedUntil)
            ->bind(':id', $id)
            ->execute();

        if (!$inserted) {
            return false;
        }

        return $this->db
            ->query("UPDATE users SET status = 'banned' WHERE id = :id AND role = 'user' AND deleted_at IS NULL")
            ->bind(':id', $id)
            ->execute();
    }

    public function unbanById(int $id): bool
    {
        $this->db
            ->query('UPDATE user_bans SET is_active = 0 WHERE user_id = :user_id AND is_active = 1')
            ->bind(':user_id', $id)
            ->execute();

        return $this->db
            ->query("UPDATE users
                     SET status = 'active',
                         ban_reason = NULL,
                         banned_until = NULL
                     WHERE id = :id AND deleted_at IS NULL")
            ->bind(':id', $id)
            ->execute();
    }

    public function softDeleteById(int $id): bool
    {
        return $this->db
            ->query("UPDATE users SET deleted_at = NOW() WHERE id = :id AND role = 'user' AND deleted_at IS NULL")
            ->bind(':id', $id)
            ->execute();
    }

    public function restoreById(int $id): bool
    {
        $this->db
            ->query('UPDATE user_bans SET is_active = 0 WHERE user_id = :user_id AND is_active = 1')
            ->bind(':user_id', $id)
            ->execute();

        return $this->db
            ->query("UPDATE users
                     SET deleted_at = NULL,
                     status = 'active',
                     ban_reason = NULL,
                         banned_until = NULL
                     WHERE id = :id AND deleted_at IS NOT NULL")
            ->bind(':id', $id)
            ->execute();
    }

    public function clearExpiredBans(): bool
    {
        $this->db
            ->query("UPDATE user_bans
                     SET is_active = 0
                     WHERE is_active = 1
                       AND ban_type = 'temporary'
                       AND ban_until IS NOT NULL
                       AND ban_until <= NOW()")
            ->execute();

        return $this->db
            ->query("UPDATE users
                     SET status = 'active',
                         ban_reason = NULL,
                         banned_until = NULL
                     WHERE status = 'banned'
                       AND id NOT IN (SELECT user_id FROM user_bans WHERE is_active = 1)")
            ->execute();
    }

    public function listActiveAccountBans(?string $keyword = null): array
    {
        $this->clearExpiredBans();
        $sql = "SELECT ub.id,
                       ub.user_id,
                       u.name AS user_name,
                       u.email AS user_email,
                       ub.reason,
                       ub.ban_type,
                       ub.created_at AS started_at,
                       ub.ban_until AS expires_at,
                       CASE
                           WHEN ub.is_active = 1 THEN 'active'
                           ELSE 'released'
                       END AS status,
                       'user_ban' AS source
                FROM user_bans ub
                INNER JOIN users u ON u.id = ub.user_id
                WHERE ub.is_active = 1";
        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= " AND (u.name LIKE :kw_name OR u.email LIKE :kw_email OR ub.reason LIKE :kw_reason)";
        }
        $sql .= ' ORDER BY ub.created_at DESC, ub.id DESC';

        $query = $this->db->query($sql);
        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . trim($keyword) . '%';
            $query->bind(':kw_name', $like)
                ->bind(':kw_email', $like)
                ->bind(':kw_reason', $like);
        }
        $query->execute();
        return $query->resultSet();
    }

    public function hasActiveBan(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $this->clearExpiredBans();
        $this->db->query('SELECT 1 FROM user_bans WHERE user_id = :user_id AND is_active = 1 LIMIT 1')
            ->bind(':user_id', $userId)
            ->execute();
        return (bool) $this->db->single();
    }
}
