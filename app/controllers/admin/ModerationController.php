<?php

declare(strict_types=1);

final class ModerationController extends Controller
{
    public function manageReports(): void
    {
        require_admin_permission('admin.reports.view');

        /** @var ModerationAdminService $service */
        $service = $this->service('admin/ModerationAdminService');
        $data = $service->buildManageReportsData($_GET);

        $this->adminView('admin/manage_reports', [
            'status' => $data['status'],
            'type' => $data['type'],
            'keyword' => $data['keyword'],
            'rows' => $data['rows'],
            'notice' => (string) ($_GET['notice'] ?? ''),
        ]);
    }

    public function handleReportAction(): void
    {
        require_admin_permission('admin.reports.resolve');

        /** @var ModerationAdminService $service */
        $service = $this->service('admin/ModerationAdminService');

        $ok = $service->handleReportAction($_POST);
        $qs = $service->buildReportReturnQueryFromPost($_POST);
        $qs['notice'] = $ok ? 'updated' : 'update_failed';

        $this->redirect('/admin/reports?' . http_build_query($qs));
    }

    public function updateReportStatus(): void
    {
        require_admin_permission('admin.reports.resolve');

        /** @var ModerationAdminService $service */
        $service = $this->service('admin/ModerationAdminService');

        $ok = $service->updateReportStatus($_POST);
        $qs = $service->buildReportReturnQueryFromPost($_POST);
        $qs['notice'] = $ok ? 'updated' : 'update_failed';

        $this->redirect('/admin/reports?' . http_build_query($qs));
    }
}