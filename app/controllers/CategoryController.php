<?php

declare(strict_types=1);

class CategoryController extends Controller
{
    public function index(): void
    {
        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');
        $categories = $categoryModel->all();
        $this->jsonSuccess(['categories' => $categories], 'Lấy danh mục thành công', 200);
    }
}
