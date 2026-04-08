<?php

declare(strict_types=1);

class CategoryController extends Controller
{
    public function manageCategories(): void
    {
        require_admin_permission('admin.categories.manage');

        /** @var CategoryAdminService $service */
        $service = $this->service('admin/CategoryAdminService');
        $categories = $service->getAllCategories();

        $this->adminView('admin/categories/index', ['categories' => $categories]);
    }
}