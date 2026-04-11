<?php

declare(strict_types=1);

class DashboardAdminService
{
    public function buildDashboardData(array $query = []): array
    {
        require_once APPROOT . '/app/models/UserModel.php';
        require_once APPROOT . '/app/models/RecipeModel.php';
        require_once APPROOT . '/app/models/TipModel.php';
        require_once APPROOT . '/app/models/IngredientModel.php';
        require_once APPROOT . '/app/models/AdminStatsModel.php';
        require_once APPROOT . '/app/models/Database.php';

        $userModel = new UserModel();
        $recipeModel = new RecipeModel();
        $statsModel = new AdminStatsModel();

        $today = date('Y-m-d');
        $fromDate = date('Y-m-d', strtotime('-29 days'));
        $overviewStats = $statsModel->overview($fromDate, $today);

        return [
            'overview' => [
                'total_users' => $this->countTableRows('users'),
                'total_recipes' => $recipeModel->countApproved(),
                'total_comments' => $this->countTableRows('comments'),
                'total_reports' => $this->countTableRows('reports'),
                'new_users_last_30_days' => (int) ($overviewStats['users_new'] ?? 0),
                'pending_recipes' => (int) ($overviewStats['recipes']['pending'] ?? 0),
                'pending_ingredients' => (int) ($overviewStats['ingredients']['pending'] ?? 0),
                'pending_tips' => (int) ($overviewStats['tips']['pending'] ?? 0),
                'pending_reports' => (int) ($overviewStats['reports_new'] ?? 0),
            ],
            'latestUsers' => $this->listLatestUsers(4),
            'latestRecipes' => $this->listLatestRecipes(4),
        ];
    }

    private function countTableRows(string $table): int
    {
        $db = Database::getInstance();
        $db->query('SELECT COUNT(*) AS total FROM `' . str_replace('`', '', $table) . '`')->execute();
        $row = $db->single();
        return (int) ($row['total'] ?? 0);
    }

    private function listLatestUsers(int $limit): array
    {
        $userModel = new UserModel();
        $users = $userModel->all();
        return array_slice($users, 0, $limit);
    }

    private function listLatestRecipes(int $limit): array
    {
        $recipeModel = new RecipeModel();
        $recipes = $recipeModel->all();
        return array_slice($recipes, 0, $limit);
    }
}
