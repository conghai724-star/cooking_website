<?php

declare(strict_types=1);

class ModerationAdminService
{
    public function buildManageReportsData(array $query): array
    {
        /** @var AdminService $adminService */
        $adminService = $this->adminService();
        return $adminService->buildManageReportsData($query);
    }

    public function handleReportAction(array $input): bool
    {
        /** @var AdminService $adminService */
        $adminService = $this->adminService();
        return $adminService->handleReportAction($input);
    }

    public function updateReportStatus(array $post): bool
    {
        $reportId = (int) ($post['report_id'] ?? 0);
        $kind = (string) ($post['kind'] ?? '');
        $contentType = (string) ($post['content_type'] ?? 'recipe');
        $status = (string) ($post['status'] ?? '');

        if ($reportId <= 0 || !in_array($status, ['pending', 'reviewed', 'resolved'], true)) {
            return false;
        }

        if ($kind === 'recipe') {
            /** @var RecipeModel $recipeModel */
            $recipeModel = $this->model('RecipeModel');
            return $recipeModel->updateReportStatus($reportId, $status);
        }

        if ($kind === 'comment') {
            /** @var CommentModel $commentModel */
            $commentModel = $this->model('CommentModel');
            return $commentModel->updateReportStatus($reportId, $contentType, $status);
        }

        if ($kind === 'account') {
            /** @var UserSafetyModel $userSafetyModel */
            $userSafetyModel = $this->model('UserSafetyModel');
            return $userSafetyModel->updateUserReportStatus($reportId, $status);
        }

        if ($kind === 'tip') {
            /** @var TipModel $tipModel */
            $tipModel = $this->model('TipModel');
            return $tipModel->updateReportStatus($reportId, $status);
        }

        if ($kind === 'ingredient') {
            /** @var IngredientModel $ingredientModel */
            $ingredientModel = $this->model('IngredientModel');
            return $ingredientModel->updateReportStatus($reportId, $status);
        }

        return false;
    }

    public function buildReportReturnQueryFromPost(array $post): array
    {
        $qs = [];
        $returnStatus = (string) ($post['return_status'] ?? '');
        $returnType = (string) ($post['return_type'] ?? '');
        $returnKeyword = trim((string) ($post['return_q'] ?? ''));
        $returnPage = max(1, (int) ($post['return_page'] ?? 1));

        if ($returnStatus !== '') {
            $qs['status'] = $returnStatus;
        }
        if ($returnType !== '') {
            $qs['type'] = $returnType;
        }
        if ($returnKeyword !== '') {
            $qs['q'] = $returnKeyword;
        }
        if ($returnPage > 1) {
            $qs['page'] = $returnPage;
        }

        return $qs;
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

    private function adminService(): AdminService
    {
        require_once APPROOT . '/app/services/AdminService.php';
        return new AdminService();
    }
}
