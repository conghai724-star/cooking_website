<?php

declare(strict_types=1);

final class RecipeController extends Controller
{
    private const PAGE_SIZE = 15;

    public function manageRecipes(): void
    {
        require_admin_permission('admin.recipes.review');

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = self::PAGE_SIZE;

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $totalRecipes = $recipeModel->countForAdmin();
        $totalPages = max(1, (int) ceil($totalRecipes / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $recipes = $recipeModel->allForAdminPaged($perPage, $offset);

        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');
        $categories = $categoryModel->all();

        $this->adminView('admin/recipes/index', [
            'recipes' => $recipes,
            'categories' => $categories,
            'page' => $page,
            'perPage' => $perPage,
            'totalRecipes' => $totalRecipes,
            'totalPages' => $totalPages,
        ]);
    }

    public function showRecipe(string $id): void
    {
        require_admin_permission('admin.recipes.review');

        $recipeId = (int) $id;
        if ($recipeId <= 0) {
            $this->redirect('/admin/recipes');
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');

        $recipe = $recipeModel->findById($recipeId);
        if (!$recipe) {
            $this->redirect('/admin/recipes');
        }

        $ingredients = $recipeModel->ingredientsByRecipe($recipeId);
        $steps = $recipeModel->stepsByRecipe($recipeId);

        $this->adminView('admin/recipes/show', [
            'recipe' => $recipe,
            'ingredients' => $ingredients,
            'steps' => $steps,
        ]);
    }

    public function createRecipe(): void
    {
        require_admin_permission('admin.recipes.manage');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/admin/recipes');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $categoryId = trim((string) ($_POST['category_id'] ?? ''));
        $cookingTime = trim((string) ($_POST['cooking_time'] ?? ''));
        $difficulty = (string) ($_POST['difficulty'] ?? 'easy');

        if ($title === '' || $description === '') {
            $this->redirect('/admin/recipes?error=missing');
        }

        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'easy';
        }

        $categoryValue = $categoryId !== '' ? (int) $categoryId : null;
        $cookingTimeValue = $cookingTime !== '' ? (int) $cookingTime : null;
        $image = upload_image('image', APPROOT . '/public/uploads');
        $admin = current_admin();
        $adminId = (int) ($admin['id'] ?? 0);

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $recipeId = $recipeModel->create(
            $adminId,
            $title,
            $description,
            $categoryValue,
            $image,
            $cookingTimeValue,
            $difficulty,
            'approved'
        );

        if ($recipeId === false) {
            $this->redirect('/admin/recipes?error=save_failed');
        }

        $this->redirect('/admin/recipes?success=1');
    }

    public function approveRecipe(string $id): void
    {
        require_admin_permission('admin.recipes.review');
        $recipeId = (int) $id;
        if ($recipeId > 0) {
            /** @var RecipeModel $recipeModel */
            $recipeModel = $this->model('RecipeModel');
            $recipeModel->setStatus($recipeId, 'approved');
            $recipeModel->setUserState($recipeId, 'published');
        }
        $this->redirect('/admin/recipes');
    }

    public function rejectRecipe(string $id): void
    {
        require_admin_permission('admin.recipes.review');
        $recipeId = (int) $id;
        if ($recipeId > 0) {
            /** @var RecipeModel $recipeModel */
            $recipeModel = $this->model('RecipeModel');
            $recipeModel->setStatus($recipeId, 'rejected');
        }
        $this->redirect('/admin/recipes');
    }

    public function resubmitRecipe(string $id): void
    {
        require_admin_permission('admin.recipes.review');
        $recipeId = (int) $id;
        if ($recipeId > 0) {
            /** @var RecipeModel $recipeModel */
            $recipeModel = $this->model('RecipeModel');
            $recipeModel->setStatus($recipeId, 'pending');
            $recipeModel->setUserState($recipeId, 'completed');
        }
        $this->redirect('/admin/recipes');
    }

    public function deleteRecipe(string $id): void
    {
        require_admin_permission('admin.recipes.manage');
        $recipeId = (int) $id;
        if ($recipeId > 0) {
            /** @var RecipeModel $recipeModel */
            $recipeModel = $this->model('RecipeModel');
            if ($recipeModel->isUsedInMealPlan($recipeId)) {
                $this->redirect('/admin/recipes?error=used_in_meal_plan');
            }
            $recipeModel->deleteById($recipeId);
            $admin = current_admin();
            $adminId = (int) ($admin['id'] ?? 0);
            system_log_write('admin_action', 'admin.recipe.delete', 'success', null, 'recipe', $recipeId, null, $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));
        }
        $this->redirect('/admin/recipes');
    }

    public function manageTips(): void
    {
        require_admin_permission('admin.tips.review');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['status'] ?? '');
        if (!in_array($status, ['', 'approved', 'rejected', 'pending'], true)) {
            $status = '';
        }

        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $totalTips = $tipModel->countByStatus($status !== '' ? $status : null, $keyword !== '' ? $keyword : null);
        $totalPages = max(1, (int) ceil($totalTips / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $tips = $tipModel->allPaged($status !== '' ? $status : null, $perPage, $offset, $keyword !== '' ? $keyword : null);

        $this->adminView('admin/moderation/tips/index', [
            'tips' => $tips,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $totalTips,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'status' => $status,
        ]);
    }

    public function approveTip(string $id): void
    {
        require_admin_permission('admin.tips.review');
        $tipId = (int) $id;
        $page = max(1, (int) ($_POST['return_page'] ?? 1));
        if ($tipId <= 0) {
            $this->redirect('/admin/tips?page=' . $page);
        }
        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $tipModel->setStatus($tipId, 'approved', null);
        $this->redirect('/admin/tips?page=' . $page);
    }

    public function rejectTip(string $id): void
    {
        require_admin_permission('admin.tips.review');
        $tipId = (int) $id;
        $page = max(1, (int) ($_POST['return_page'] ?? 1));
        if ($tipId <= 0) {
            $this->redirect('/admin/tips?page=' . $page);
        }
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $reasonValue = $reason !== '' ? $reason : null;
        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $tipModel->setStatus($tipId, 'rejected', $reasonValue);
        $this->redirect('/admin/tips?page=' . $page);
    }

    public function deleteTip(string $id): void
    {
        require_admin_permission('admin.tips.manage');
        $tipId = (int) $id;
        $page = max(1, (int) ($_POST['return_page'] ?? 1));
        if ($tipId <= 0) {
            $this->redirect('/admin/tips?page=' . $page);
        }
        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $tipModel->delete($tipId);
        $admin = current_admin();
        $adminId = (int) ($admin['id'] ?? 0);
        system_log_write('admin_action', 'admin.tip.delete', 'success', null, 'tip', $tipId, null, $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));
        $this->redirect('/admin/tips?page=' . $page);
    }
}
