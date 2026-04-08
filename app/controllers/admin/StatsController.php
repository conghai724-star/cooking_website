<?php

declare(strict_types=1);

class StatsController extends Controller
{
    public function manageStats(): void
    {
        require_admin_permission('admin.stats.view');

        /** @var StatsAdminService $service */
        $service = $this->service('admin/StatsAdminService');
        $data = $service->buildManageStatsData($_GET);

        $this->adminView('admin/manage_stats', $data);
    }

    public function exportStatsCsv(): void
    {
        require_admin_permission('admin.stats.view');

        /** @var StatsAdminService $service */
        $service = $this->service('admin/StatsAdminService');
        $service->exportStatsCsv($_GET);
    }
}