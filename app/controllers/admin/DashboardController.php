<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        require_admin_permission('admin.dashboard.view');

        /** @var DashboardAdminService $service */
        $service = $this->service('admin/DashboardAdminService');
        $data = $service->buildDashboardData($_GET);

        $this->adminView('admin/dashboard/index', $data);
    }
}
