<?php

declare(strict_types=1);

class MealPlanController extends Controller
{
    public function manageMealPlans(): void
    {
        require_admin_permission('admin.mealplans.view');

        /** @var MealPlanAdminService $service */
        $service = $this->service('admin/MealPlanAdminService');
        $data = $service->buildManageMealPlansData($_GET);

        $this->adminView('admin/manage_mealplans', [
            'rows' => $data['rows'],
            'keyword' => $data['keyword'],
            'userId' => $data['userId'],
            'fromDate' => $data['fromDate'],
            'toDate' => $data['toDate'],
            'page' => $data['page'],
            'perPage' => $data['perPage'],
            'total' => $data['total'],
            'totalPages' => $data['totalPages'],
            'notice' => (string) ($_GET['notice'] ?? ''),
            'canModerateMealPlans' => admin_has_permission('admin.mealplans.moderate'),
        ]);
    }

    public function deleteMealPlan(string $id): void
    {
        require_admin_permission('admin.mealplans.moderate');

        /** @var MealPlanAdminService $service */
        $service = $this->service('admin/MealPlanAdminService');
        $ok = $service->deleteMealPlanForAdmin((int) $id, current_admin());

        $this->redirect('/admin/mealplans?notice=' . ($ok ? 'deleted' : 'delete_failed'));
    }
}