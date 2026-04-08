<?php

declare(strict_types=1);

class IngredientController extends Controller
{
    public function manageIngredients(): void
    {
        require_admin_permission('admin.ingredients.review');
        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');

        $ingredients = $ingredientModel->all();
        $categories = $categoryModel->byType('ingredient');

        $this->adminView('admin/ingredients/index', [
            'ingredients' => $ingredients,
            'categories' => $categories,
        ]);
    }

    public function showIngredient(string $id): void
    {
        require_admin_permission('admin.ingredients.review');

        $ingredientId = (int) $id;
        if ($ingredientId <= 0) {
            $this->redirect('/admin/ingredients');
        }

        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');

        $ingredient = $ingredientModel->findById($ingredientId);
        if (!$ingredient) {
            $this->redirect('/admin/ingredients');
        }

        $nutrition = $ingredientModel->getNutrition($ingredientId) ?: [];

        $this->adminView('admin/ingredients/show', [
            'ingredient' => $ingredient,
            'nutrition' => $nutrition,
        ]);
    }

    public function createIngredient(): void
    {
        require_admin_permission('admin.ingredients.manage');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/admin/ingredients');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $categoryId = trim((string) ($_POST['category_id'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $usage = trim((string) ($_POST['usage'] ?? ''));
        $preparation = trim((string) ($_POST['preparation'] ?? ''));
        $storage = trim((string) ($_POST['storage'] ?? ''));
        $calories = trim((string) ($_POST['calories'] ?? ''));
        $protein = trim((string) ($_POST['protein'] ?? ''));
        $fat = trim((string) ($_POST['fat'] ?? ''));
        $carb = trim((string) ($_POST['carb'] ?? ''));
        $image = upload_image('image', APPROOT . '/public/uploads');

        if ($name === '') {
            $this->redirect('/admin/ingredients?error=missing_name');
        }

        $categoryValue = $categoryId !== '' ? (int) $categoryId : null;
        $descriptionValue = $description !== '' ? $description : null;
        $usageValue = $usage !== '' ? $usage : null;
        $preparationValue = $preparation !== '' ? $preparation : null;
        $storageValue = $storage !== '' ? $storage : null;
        $caloriesValue = $calories !== '' ? (float) $calories : null;
        $proteinValue = $protein !== '' ? (float) $protein : null;
        $fatValue = $fat !== '' ? (float) $fat : null;
        $carbValue = $carb !== '' ? (float) $carb : null;

        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        $ingredientId = $ingredientModel->create(
            $name,
            $categoryValue,
            $descriptionValue,
            $usageValue,
            $preparationValue,
            $storageValue,
            'approved',
            'library',
            $image
        );
        if ($ingredientId === false) {
            $this->redirect('/admin/ingredients?error=save_failed');
        }
        $ingredientModel->upsertNutrition($ingredientId, $caloriesValue, $proteinValue, $fatValue, $carbValue);

        $this->redirect('/admin/ingredients?success=1');
    }

    public function approveIngredient(string $id): void
    {
        require_admin_permission('admin.ingredients.review');
        $ingredientId = (int) $id;
        if ($ingredientId > 0) {
            /** @var IngredientModel $ingredientModel */
            $ingredientModel = $this->model('IngredientModel');
            $ingredientModel->setStatus($ingredientId, 'approved', null);
        }
        $this->redirect('/admin/ingredients');
    }

    public function rejectIngredient(string $id): void
    {
        require_admin_permission('admin.ingredients.review');
        $ingredientId = (int) $id;
        if ($ingredientId > 0) {
            $reason = trim((string) ($_POST['reason'] ?? ''));
            $reasonValue = $reason !== '' ? $reason : null;
            /** @var IngredientModel $ingredientModel */
            $ingredientModel = $this->model('IngredientModel');
            $ingredientModel->setStatus($ingredientId, 'rejected', $reasonValue);
        }
        $this->redirect('/admin/ingredients');
    }

    public function deleteIngredient(string $id): void
    {
        require_admin_permission('admin.ingredients.manage');
        $ingredientId = (int) $id;
        if ($ingredientId > 0) {
            /** @var IngredientModel $ingredientModel */
            $ingredientModel = $this->model('IngredientModel');
            $ingredientModel->delete($ingredientId);
            $admin = current_admin();
            $adminId = (int) ($admin['id'] ?? 0);
            system_log_write('admin_action', 'admin.ingredient.delete', 'success', null, 'ingredient', $ingredientId, null, $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));
        }
        $this->redirect('/admin/ingredients');
    }

    public function editIngredient(string $id): void
    {
        require_admin_permission('admin.ingredients.manage');
        $ingredientId = (int) $id;
        if ($ingredientId <= 0) {
            $this->redirect('/admin/ingredients');
        }

        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');

        $ingredient = $ingredientModel->findById($ingredientId);
        if (!$ingredient) {
            $this->redirect('/admin/ingredients');
        }

        $nutrition = $ingredientModel->getNutrition($ingredientId) ?: [];
        $categories = $categoryModel->byType('ingredient');

        $error = '';
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $categoryId = trim((string) ($_POST['category_id'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $usage = trim((string) ($_POST['usage'] ?? ''));
            $preparation = trim((string) ($_POST['preparation'] ?? ''));
            $storage = trim((string) ($_POST['storage'] ?? ''));
            $calories = trim((string) ($_POST['calories'] ?? ''));
            $protein = trim((string) ($_POST['protein'] ?? ''));
            $fat = trim((string) ($_POST['fat'] ?? ''));
            $carb = trim((string) ($_POST['carb'] ?? ''));
            $image = upload_image('image', APPROOT . '/public/uploads');

            if ($name === '') {
                $error = 'Vui lòng nhập tên nguyên liệu.';
            } else {
                $categoryValue = $categoryId !== '' ? (int) $categoryId : null;
                $descriptionValue = $description !== '' ? $description : null;
                $usageValue = $usage !== '' ? $usage : null;
                $preparationValue = $preparation !== '' ? $preparation : null;
                $storageValue = $storage !== '' ? $storage : null;
                $imageValue = $image ?: (isset($ingredient['image']) ? (string) $ingredient['image'] : null);
                $caloriesValue = $calories !== '' ? (float) $calories : null;
                $proteinValue = $protein !== '' ? (float) $protein : null;
                $fatValue = $fat !== '' ? (float) $fat : null;
                $carbValue = $carb !== '' ? (float) $carb : null;

                $ingredientModel->update(
                    $ingredientId,
                    $name,
                    $categoryValue,
                    $descriptionValue,
                    $usageValue,
                    $preparationValue,
                    $storageValue,
                    $imageValue
                );
                $ingredientModel->upsertNutrition($ingredientId, $caloriesValue, $proteinValue, $fatValue, $carbValue);

                $this->redirect('/admin/ingredients');
            }
        }

        $this->adminView('admin/ingredients/edit', [
            'ingredient' => $ingredient,
            'nutrition' => $nutrition,
            'categories' => $categories,
            'error' => $error,
        ]);
    }
}
