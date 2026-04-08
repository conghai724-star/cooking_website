<?php

declare(strict_types=1);

class CommentAdminService
{
    public function buildManageCommentsData(array $query): array
    {
        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');

        $status = (string) ($query['status'] ?? '');
        if (!in_array($status, ['', 'active', 'hidden', 'deleted'], true)) {
            $status = '';
        }

        $keyword = trim((string) ($query['q'] ?? ''));
        $reportedOnly = (string) ($query['reported'] ?? '') === '1';

        $comments = $commentModel->allForAdmin(
            $status !== '' ? $status : null,
            $keyword !== '' ? $keyword : null,
            $reportedOnly
        );

        return [
            'comments' => $comments,
            'status' => $status,
            'keyword' => $keyword,
            'reportedOnly' => $reportedOnly,
            'notice' => (string) ($query['notice'] ?? ''),
        ];
    }

    public function hideComment(int $commentId, string $contentType, array $admin): void
    {
        if ($commentId <= 0) {
            return;
        }

        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');
        $commentModel->setStatusByType($commentId, $contentType, 'hidden');
        $this->logCommentAction('admin.comment.hide', $commentId, $contentType, $admin);
    }

    public function restoreComment(int $commentId, string $contentType, array $admin): void
    {
        if ($commentId <= 0) {
            return;
        }

        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');
        $commentModel->setStatusByType($commentId, $contentType, 'active');
        $this->logCommentAction('admin.comment.restore', $commentId, $contentType, $admin);
    }

    public function deleteComment(int $commentId, string $contentType, array $admin): void
    {
        if ($commentId <= 0) {
            return;
        }

        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');
        $commentModel->deleteByType($commentId, $contentType);
        $this->logCommentAction('admin.comment.delete', $commentId, $contentType, $admin);
    }

    private function logCommentAction(string $actionKey, int $commentId, string $contentType, array $admin): void
    {
        $adminId = (int) ($admin['id'] ?? 0);
        system_log_write('admin_action', $actionKey, 'success', null, 'comment', $commentId, [
            'content_type' => $contentType,
        ], $adminId > 0 ? $adminId : null, (string) ($admin['role'] ?? 'admin'));
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
}