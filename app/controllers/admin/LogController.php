<?php

declare(strict_types=1);

final class LogController extends Controller
{
    public function manage(): void
    {
        require_admin_permission('admin.logs.view');

        /** @var LogAdminService $service */
        $service = $this->service('admin/LogAdminService');
        $data = $service->buildManageLogsData($_GET);

        $this->adminView('admin/manage_logs', [
            'rows' => $data['rows'],
            'filters' => $data['filters'],
            'page' => $data['page'],
            'perPage' => $data['perPage'],
            'total' => $data['total'],
            'totalPages' => $data['totalPages'],
        ]);
    }
}