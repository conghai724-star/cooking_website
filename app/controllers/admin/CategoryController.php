<?php

declare(strict_types=1);

class CategoryController extends Controller
{
    private const PAGE_SIZE = 15;
    private const CATEGORY_TYPES = ['recipe' => 'Công thức', 'ingredient' => 'Nguyên liệu', 'knowledge' => 'Kiến thức'];

    public function manageCategories(): void
    {
        require_admin_permission('admin.categories.manage');

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = self::PAGE_SIZE;

        /** @var CategoryAdminService $service */
        $service = $this->service('admin/CategoryAdminService');
        $data = $service->getCategoriesPage($page, $perPage);

        $this->adminView('admin/categories/index', array_merge($data, [
            'page' => $page,
            'perPage' => $perPage,
            'typeLabels' => self::CATEGORY_TYPES,
            'notice' => (string) ($_GET['notice'] ?? ''),
            'error' => (string) ($_GET['error'] ?? ''),
        ]));
    }

    public function createCategory(): void
    {
        require_admin_permission('admin.categories.manage');

        $name = trim((string) ($_POST['name'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? 'recipe'));
        $allowedTypes = array_keys(self::CATEGORY_TYPES);
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'recipe';
        }

        if ($name === '') {
            $this->redirect('/admin/categories?error=missing_name');
        }

        /** @var CategoryAdminService $service */
        $service = $this->service('admin/CategoryAdminService');
        if (!$service->createCategory($name, $type)) {
            $this->redirect('/admin/categories?error=duplicate_or_failed');
        }

        $this->redirect('/admin/categories?notice=created');
    }
}
