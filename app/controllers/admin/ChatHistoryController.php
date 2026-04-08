<?php

declare(strict_types=1);

final class ChatHistoryController extends Controller
{
    public function manage(): void
    {
        require_admin_permission('admin.logs.view');

        /** @var ChatHistoryAdminService $service */
        $service = $this->service('admin/ChatHistoryAdminService');
        $data = $service->buildManageChatHistoriesData($_GET);

        $this->adminView('admin/chat_histories/index', [
            'rows' => $data['rows'],
            'filters' => $data['filters'],
            'page' => $data['page'],
            'perPage' => $data['perPage'],
            'total' => $data['total'],
            'totalPages' => $data['totalPages'],
            'intentOptions' => $data['intentOptions'],
            'codeOptions' => $data['codeOptions'],
            'states' => $data['states'],
        ]);
    }
}
