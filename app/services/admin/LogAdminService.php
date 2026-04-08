<?php

declare(strict_types=1);

class LogAdminService
{
    public function buildManageLogsData(array $query): array
    {
        require_once APPROOT . '/app/models/SystemLogModel.php';

        $filters = [
            'event_type' => trim((string) ($query['event_type'] ?? '')),
            'result' => trim((string) ($query['result'] ?? '')),
            'action_key' => trim((string) ($query['action_key'] ?? '')),
            'actor_id' => (int) ($query['actor_id'] ?? 0),
            'from' => trim((string) ($query['from'] ?? '')),
            'to' => trim((string) ($query['to'] ?? '')),
            'q' => trim((string) ($query['q'] ?? '')),
        ];

        if (!in_array($filters['event_type'], ['', 'auth', 'user_action', 'content_action', 'admin_action'], true)) {
            $filters['event_type'] = '';
        }
        if (!in_array($filters['result'], ['', 'success', 'failed', 'blocked'], true)) {
            $filters['result'] = '';
        }
        if ($filters['actor_id'] < 0) {
            $filters['actor_id'] = 0;
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $logModel = new SystemLogModel();
        $total = $logModel->countFiltered($filters);
        $rows = $logModel->listFiltered($perPage, $offset, $filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'rows' => $rows,
            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
        ];
    }
}