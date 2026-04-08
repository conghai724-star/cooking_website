<?php

declare(strict_types=1);

class CategoryModel extends Model
{
    public function all(): array
    {
        $this->db->query('SELECT * FROM categories ORDER BY name ASC')->execute();
        return $this->db->resultSet();
    }

    public function byType(string $type): array
    {
        $this->db->query('SELECT * FROM categories WHERE type = :type ORDER BY name ASC')
            ->bind(':type', $type)
            ->execute();

        return $this->db->resultSet();
    }
}
