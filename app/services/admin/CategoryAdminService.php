<?php

declare(strict_types=1);

class CategoryAdminService
{
    public function getAllCategories(): array
    {
        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');
        return $categoryModel->all();
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