<?php

declare(strict_types=1);

class CategoryAdminService
{
    public function getCategoriesPage(int $page, int $perPage): array
    {
        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');
        $total = $categoryModel->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $categories = $categoryModel->allPaged($perPage, $offset);

        return [
            'categories' => $categories,
            'total' => $total,
            'totalPages' => $totalPages,
        ];
    }

    public function createCategory(string $name, string $type): bool
    {
        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');
        if ($categoryModel->existsByNameAndType($name, $type)) {
            return false;
        }

        return $categoryModel->create($name, $type);
    }

    private function model(string $model): object
    {
        $modelPath = APPROOT . '/app/models/' . $model . '.php';
        if (!file_exists($modelPath)) {
            throw new RuntimeException('Không tìm thấy model: ' . $model);
        }

        require_once $modelPath;
        return new $model();
    }
}
