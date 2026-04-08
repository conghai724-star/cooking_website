<?php

declare(strict_types=1);

class FollowModel extends Model
{
    public function countForAdmin(?string $keyword = null, ?int $userId = null, string $side = 'all', string $risk = 'all'): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM follows f
                INNER JOIN users follower ON follower.id = f.follower_id
                INNER JOIN users following ON following.id = f.following_id
                WHERE 1=1';

        if ($keyword !== null && $keyword !== '') {
            $sql .= ' AND (
                        follower.name LIKE :kw
                        OR follower.email LIKE :kw
                        OR following.name LIKE :kw
                        OR following.email LIKE :kw
                      )';
        }

        if (($userId ?? 0) > 0) {
            if ($side === 'as_follower') {
                $sql .= ' AND f.follower_id = :user_id_follower';
            } elseif ($side === 'as_following') {
                $sql .= ' AND f.following_id = :user_id_following';
            } else {
                $sql .= ' AND (f.follower_id = :user_id_follower OR f.following_id = :user_id_following)';
            }
        }

        $riskWhere = $this->riskWhereClause($risk);
        if ($riskWhere !== '') {
            $sql .= ' AND ' . $riskWhere;
        }

        $query = $this->db->query($sql);

        if ($keyword !== null && $keyword !== '') {
            $query->bind(':kw', '%' . $keyword . '%');
        }
        if (($userId ?? 0) > 0) {
            $query->bind(':user_id_follower', (int) $userId);
            $query->bind(':user_id_following', (int) $userId);
        }

        $query->execute();
        $row = $query->single();
        return (int) ($row['total'] ?? 0);
    }

    public function listForAdmin(int $limit, int $offset, ?string $keyword = null, ?int $userId = null, string $side = 'all', string $risk = 'all'): array
    {
        $sql = 'SELECT f.follower_id,
                       f.following_id,
                       f.created_at,
                       follower.name AS follower_name,
                       follower.email AS follower_email,
                       following.name AS following_name,
                       following.email AS following_email,
                       (SELECT COUNT(*)
                        FROM follows f1
                        WHERE f1.follower_id = f.follower_id
                          AND f1.created_at >= (NOW() - INTERVAL 1 HOUR)) AS follows_last_hour,
                       (SELECT COUNT(*)
                        FROM follows f24
                        WHERE f24.follower_id = f.follower_id
                          AND f24.created_at >= (NOW() - INTERVAL 1 DAY)) AS follows_last_24h
                FROM follows f
                INNER JOIN users follower ON follower.id = f.follower_id
                INNER JOIN users following ON following.id = f.following_id
                WHERE 1=1';

        if ($keyword !== null && $keyword !== '') {
            $sql .= ' AND (
                        follower.name LIKE :kw
                        OR follower.email LIKE :kw
                        OR following.name LIKE :kw
                        OR following.email LIKE :kw
                      )';
        }

        if (($userId ?? 0) > 0) {
            if ($side === 'as_follower') {
                $sql .= ' AND f.follower_id = :user_id_follower';
            } elseif ($side === 'as_following') {
                $sql .= ' AND f.following_id = :user_id_following';
            } else {
                $sql .= ' AND (f.follower_id = :user_id_follower OR f.following_id = :user_id_following)';
            }
        }

        $riskWhere = $this->riskWhereClause($risk);
        if ($riskWhere !== '') {
            $sql .= ' AND ' . $riskWhere;
        }

        $sql .= ' ORDER BY f.created_at DESC LIMIT :limit OFFSET :offset';
        $query = $this->db->query($sql);

        if ($keyword !== null && $keyword !== '') {
            $query->bind(':kw', '%' . $keyword . '%');
        }
        if (($userId ?? 0) > 0) {
            $query->bind(':user_id_follower', (int) $userId);
            $query->bind(':user_id_following', (int) $userId);
        }

        $query->bind(':limit', max(1, (int) $limit), PDO::PARAM_INT);
        $query->bind(':offset', max(0, (int) $offset), PDO::PARAM_INT);
        $query->execute();

        return $query->resultSet();
    }

    public function listTopFollowersBy24h(int $limit = 10): array
    {
        $query = $this->db->query(
            'SELECT f.follower_id,
                    u.name AS follower_name,
                    u.email AS follower_email,
                    COUNT(*) AS follows_last_24h
             FROM follows f
             INNER JOIN users u ON u.id = f.follower_id
             WHERE f.created_at >= (NOW() - INTERVAL 1 DAY)
             GROUP BY f.follower_id, u.name, u.email
             ORDER BY follows_last_24h DESC
             LIMIT :limit'
        );
        $query->bind(':limit', max(1, (int) $limit), PDO::PARAM_INT);
        $query->execute();
        return $query->resultSet();
    }

    public function forceRemove(int $followerId, int $followingId): bool
    {
        return $this->db
            ->query('DELETE FROM follows WHERE follower_id = :follower_id AND following_id = :following_id')
            ->bind(':follower_id', $followerId)
            ->bind(':following_id', $followingId)
            ->execute();
    }

    private function riskWhereClause(string $risk): string
    {
        return match ($risk) {
            'high_risk' => "((SELECT COUNT(*) FROM follows f1 WHERE f1.follower_id = f.follower_id AND f1.created_at >= (NOW() - INTERVAL 1 HOUR)) >= 20
                             OR
                             (SELECT COUNT(*) FROM follows f2 WHERE f2.follower_id = f.follower_id AND f2.created_at >= (NOW() - INTERVAL 1 DAY)) >= 80)",
            'suspicious' => "((SELECT COUNT(*) FROM follows f1 WHERE f1.follower_id = f.follower_id AND f1.created_at >= (NOW() - INTERVAL 1 HOUR)) >= 10
                              OR
                              (SELECT COUNT(*) FROM follows f2 WHERE f2.follower_id = f.follower_id AND f2.created_at >= (NOW() - INTERVAL 1 DAY)) >= 30)",
            default => '',
        };
    }

    public function follow(int $followerId, int $followingId): bool
    {
        $sql = 'INSERT IGNORE INTO follows (follower_id, following_id, created_at)
                VALUES (:follower_id, :following_id, NOW())';

        return $this->db
            ->query($sql)
            ->bind(':follower_id', $followerId)
            ->bind(':following_id', $followingId)
            ->execute();
    }

    public function countFollowers(int $userId): int
    {
        $this->db->query('SELECT COUNT(*) AS total
                          FROM follows f
                          INNER JOIN users u ON u.id = f.follower_id
                          WHERE f.following_id = :user_id')
            ->bind(':user_id', $userId)
            ->execute();

        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function countFollowing(int $userId): int
    {
        $this->db->query('SELECT COUNT(*) AS total
                          FROM follows f
                          INNER JOIN users u ON u.id = f.following_id
                          WHERE f.follower_id = :user_id')
            ->bind(':user_id', $userId)
            ->execute();

        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function isFollowing(int $followerId, int $followingId): bool
    {
        $this->db->query('SELECT 1 FROM follows WHERE follower_id = :follower_id AND following_id = :following_id LIMIT 1')
            ->bind(':follower_id', $followerId)
            ->bind(':following_id', $followingId)
            ->execute();

        return (bool) $this->db->single();
    }

    public function unfollow(int $followerId, int $followingId): bool
    {
        return $this->db
            ->query('DELETE FROM follows WHERE follower_id = :follower_id AND following_id = :following_id')
            ->bind(':follower_id', $followerId)
            ->bind(':following_id', $followingId)
            ->execute();
    }

    public function removeFollower(int $userId, int $followerId): bool
    {
        return $this->db
            ->query('DELETE FROM follows WHERE follower_id = :follower_id AND following_id = :user_id')
            ->bind(':follower_id', $followerId)
            ->bind(':user_id', $userId)
            ->execute();
    }

    public function followersOf(int $userId, ?int $viewerId = null): array
    {
        $sql = 'SELECT u.id, u.username, u.name, u.avatar,
                       CASE WHEN :viewer_id_1 > 0 AND EXISTS(
                           SELECT 1 FROM follows vf
                           WHERE vf.follower_id = :viewer_id_2 AND vf.following_id = u.id
                       ) THEN 1 ELSE 0 END AS is_following_by_viewer,
                       CASE WHEN :viewer_id_3 > 0 AND EXISTS(
                           SELECT 1 FROM follows rv
                           WHERE rv.follower_id = u.id AND rv.following_id = :viewer_id_4
                       ) THEN 1 ELSE 0 END AS follows_viewer
                FROM follows f
                INNER JOIN users u ON u.id = f.follower_id
                WHERE f.following_id = :user_id
                ORDER BY f.created_at DESC';

        $viewer = (int) ($viewerId ?? 0);
        $this->db->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':viewer_id_1', $viewer)
            ->bind(':viewer_id_2', $viewer)
            ->bind(':viewer_id_3', $viewer)
            ->bind(':viewer_id_4', $viewer)
            ->execute();

        return $this->db->resultSet();
    }

    public function followingOf(int $userId, ?int $viewerId = null): array
    {
        $sql = 'SELECT u.id, u.username, u.name, u.avatar,
                       CASE WHEN :viewer_id_1 > 0 AND EXISTS(
                           SELECT 1 FROM follows vf
                           WHERE vf.follower_id = :viewer_id_2 AND vf.following_id = u.id
                       ) THEN 1 ELSE 0 END AS is_following_by_viewer,
                       CASE WHEN :viewer_id_3 > 0 AND EXISTS(
                           SELECT 1 FROM follows rv
                           WHERE rv.follower_id = u.id AND rv.following_id = :viewer_id_4
                       ) THEN 1 ELSE 0 END AS follows_viewer
                FROM follows f
                INNER JOIN users u ON u.id = f.following_id
                WHERE f.follower_id = :user_id
                ORDER BY f.created_at DESC';

        $viewer = (int) ($viewerId ?? 0);
        $this->db->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':viewer_id_1', $viewer)
            ->bind(':viewer_id_2', $viewer)
            ->bind(':viewer_id_3', $viewer)
            ->bind(':viewer_id_4', $viewer)
            ->execute();

        return $this->db->resultSet();
    }
}
