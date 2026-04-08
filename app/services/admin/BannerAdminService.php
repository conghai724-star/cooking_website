<?php

declare(strict_types=1);

class BannerAdminService
{
    public function buildManageData(array $query): array
    {
        /** @var HomeContentModel $homeContentModel */
        $homeContentModel = $this->model('HomeContentModel');
        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');

        $recipes = [];
        try {
            $recipes = $recipeModel->all('approved');
        } catch (Throwable $e) {
            $recipes = [];
        }

        $forDate = trim((string) ($query['for_date'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $forDate)) {
            $forDate = date('Y-m-d');
        }

        return [
            'banner' => $homeContentModel->getActiveBanner(),
            'featuredIds' => $homeContentModel->listFeaturedRecipeIds(),
            'recipeOfDay' => $homeContentModel->getRecipeOfDay($forDate),
            'forDate' => $forDate,
            'recipes' => $recipes,
            'notice' => (string) ($query['notice'] ?? ''),
        ];
    }

    public function saveBanner(array $post, array $admin): bool
    {
        $title = trim((string) ($post['title'] ?? ''));
        $subtitle = trim((string) ($post['subtitle'] ?? ''));
        $imageUrl = trim((string) ($post['image_url'] ?? ''));
        $ctaText = trim((string) ($post['cta_text'] ?? ''));
        $ctaUrl = trim((string) ($post['cta_url'] ?? ''));
        $isActive = ((string) ($post['is_active'] ?? '1')) === '1';

        $uploaded = upload_image('image_file', APPROOT . '/public/uploads');
        if ($uploaded !== null) {
            $imageUrl = '/uploads/' . $uploaded;
        }

        if ($imageUrl !== '' && filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
            if (!str_starts_with($imageUrl, '/uploads/')) {
                return false;
            }
        }

        /** @var HomeContentModel $homeContentModel */
        $homeContentModel = $this->model('HomeContentModel');
        $ok = $homeContentModel->saveBanner(
            $title,
            $subtitle !== '' ? $subtitle : null,
            $imageUrl !== '' ? $imageUrl : null,
            $ctaText !== '' ? $ctaText : null,
            $ctaUrl !== '' ? $ctaUrl : null,
            $isActive
        );

        if ($ok) {
            $adminId = (int) ($admin['id'] ?? 0);
            system_log_write('admin_action', 'admin.home.banner.update', 'success', null, 'home_banner', null, [
                'is_active' => $isActive,
            ], $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));
        }

        return $ok;
    }

    public function saveFeatured(array $post, array $admin): bool
    {
        $raw = trim((string) ($post['featured_recipe_ids'] ?? ''));
        $ids = array_values(array_filter(array_map(static fn(string $v): int => (int) trim($v), explode(',', $raw))));

        /** @var HomeContentModel $homeContentModel */
        $homeContentModel = $this->model('HomeContentModel');
        $ok = $homeContentModel->syncFeaturedRecipes($ids);

        if ($ok) {
            $adminId = (int) ($admin['id'] ?? 0);
            system_log_write('admin_action', 'admin.home.featured.update', 'success', null, 'home_featured', null, [
                'recipe_ids' => $ids,
            ], $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));
        }

        return $ok;
    }

    public function saveToday(array $post, array $admin): array
    {
        $forDate = trim((string) ($post['for_date'] ?? date('Y-m-d')));
        $recipeId = (int) ($post['recipe_id'] ?? 0);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $forDate)) {
            $forDate = date('Y-m-d');
        }

        /** @var HomeContentModel $homeContentModel */
        $homeContentModel = $this->model('HomeContentModel');
        $ok = $homeContentModel->setRecipeOfDay($forDate, $recipeId);

        if ($ok) {
            $adminId = (int) ($admin['id'] ?? 0);
            system_log_write('admin_action', 'admin.home.recipe_of_day.update', 'success', null, 'recipe', $recipeId > 0 ? $recipeId : null, [
                'for_date' => $forDate,
            ], $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));
        }

        return ['ok' => $ok, 'forDate' => $forDate];
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