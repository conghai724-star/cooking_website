<?php

declare(strict_types=1);

class ChatHistoryAdminService
{
    public function buildManageChatHistoriesData(array $query): array
    {
        require_once APPROOT . '/app/models/ChatHistoryModel.php';
        require_once APPROOT . '/app/models/UserModel.php';

        $filters = [
            'user_id' => max(0, (int) ($query['user_id'] ?? 0)),
            'intent' => trim((string) ($query['intent'] ?? '')),
            'code' => trim((string) ($query['code'] ?? '')),
            'from' => trim((string) ($query['from'] ?? '')),
            'to' => trim((string) ($query['to'] ?? '')),
            'q' => trim((string) ($query['q'] ?? '')),
        ];

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from'])) {
            $filters['from'] = '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to'])) {
            $filters['to'] = '';
        }

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $historyModel = new ChatHistoryModel();
        $total = $historyModel->countFiltered($filters);
        $rows = $historyModel->listFiltered($perPage, $offset, $filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $intentOptions = [];
        $codeOptions = [];
        foreach ($rows as $row) {
            $intent = trim((string) ($row['matched_intent'] ?? ''));
            if ($intent !== '') {
                $intentOptions[$intent] = true;
            }
            $code = trim((string) ($row['result_code'] ?? ''));
            if ($code !== '') {
                $codeOptions[$code] = true;
            }
        }

        $stateRows = $historyModel->latestStateByUser(20);
        $userModel = new UserModel();
        $stateUserIds = [];
        foreach ($stateRows as $stateRow) {
            $uid = (int) ($stateRow['user_id'] ?? 0);
            if ($uid > 0) {
                $stateUserIds[$uid] = $uid;
            }
        }
        $usersById = $userModel->mapBasicByIds(array_values($stateUserIds));
        $states = [];
        foreach ($stateRows as $stateRow) {
            $uid = (int) ($stateRow['user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }

            $user = $usersById[$uid] ?? null;
            $meta = json_decode((string) ($stateRow['meta_json'] ?? ''), true);
            $chatState = is_array($meta['chat_state'] ?? null) ? $meta['chat_state'] : null;
            if (!is_array($chatState)) {
                continue;
            }

            $states[] = [
                'user_id' => $uid,
                'user_name' => (string) ($user['name'] ?? ('User #' . $uid)),
                'chat_state' => $chatState,
                'updated_at' => (string) ($stateRow['created_at'] ?? ''),
            ];
        }

        return [
            'rows' => $rows,
            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'intentOptions' => array_keys($intentOptions),
            'codeOptions' => array_keys($codeOptions),
            'states' => $states,
        ];
    }
}
