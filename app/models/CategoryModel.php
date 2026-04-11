<?php

declare(strict_types=1);

class CategoryModel extends Model
{
    public function all(): array
    {
        $this->db->query('SELECT * FROM categories ORDER BY name ASC')->execute();
        return $this->db->resultSet();
    }

    public function count(): int
    {
        $this->db->query('SELECT COUNT(*) AS total FROM categories')->execute();
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function allPaged(int $limit, int $offset): array
    {
        $this->db->query('SELECT * FROM categories ORDER BY name ASC LIMIT :limit OFFSET :offset')
            ->bind(':limit', $limit)
            ->bind(':offset', $offset)
            ->execute();

        return $this->db->resultSet();
    }

    public function existsByNameAndType(string $name, string $type): bool
    {
        $this->db->query('SELECT 1 FROM categories WHERE name = :name AND type = :type LIMIT 1')
            ->bind(':name', $name)
            ->bind(':type', $type)
            ->execute();

        return (bool) $this->db->single();
    }

    public function create(string $name, string $type): bool
    {
        return $this->db->query('INSERT INTO categories (name, type) VALUES (:name, :type)')
            ->bind(':name', $name)
            ->bind(':type', $type)
            ->execute();
    }

    public function byType(string $type): array
    {
        $this->db->query('SELECT * FROM categories WHERE type = :type ORDER BY name ASC')
            ->bind(':type', $type)
            ->execute();

        return $this->db->resultSet();
    }
}
