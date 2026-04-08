<?php

declare(strict_types=1);

class MealPlanAdminService
{
    public function buildManageMealPlansData(array $query): array
    {
        require_once APPROOT . '/app/models/MealPlanModel.php';

        $keyword = trim((string) ($query['q'] ?? ''));
        $userId = max(0, (int) ($query['user_id'] ?? 0));
        $fromDate = trim((string) ($query['from'] ?? ''));
        $toDate = trim((string) ($query['to'] ?? ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            $fromDate = '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $toDate = '';
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $mealPlanModel = new MealPlanModel();
        $total = $mealPlanModel->countForAdmin(
            $keyword !== '' ? $keyword : null,
            $userId > 0 ? $userId : null,
            $fromDate !== '' ? $fromDate : null,
            $toDate !== '' ? $toDate : null
        );

        $rows = $mealPlanModel->listForAdmin(
            $perPage,
            $offset,
            $keyword !== '' ? $keyword : null,
            $userId > 0 ? $userId : null,
            $fromDate !== '' ? $fromDate : null,
            $toDate !== '' ? $toDate : null
        );

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'rows' => $rows,
            'keyword' => $keyword,
            'userId' => $userId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
        ];
    }

    public function deleteMealPlanForAdmin(int $mealPlanId, array $admin): bool
    {
        if ($mealPlanId <= 0) {
            return false;
        }

        require_once APPROOT . '/app/models/MealPlanModel.php';

        $mealPlanModel = new MealPlanModel();
        $ok = $mealPlanModel->deleteForAdmin($mealPlanId);
        if (!$ok) {
            return false;
        }

        $adminId = (int) ($admin['id'] ?? 0);
        system_log_write(
            'admin_action',
            'admin.mealplan.delete',
            'success',
            null,
            'meal_plan',
            $mealPlanId,
            null,
            $adminId > 0 ? $adminId : null,
            (string) ($admin['role'] ?? 'admin')
        );

        return true;
    }
}