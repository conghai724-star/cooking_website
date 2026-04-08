<?php

declare(strict_types=1);

class CommentController extends Controller
{
    public function manageComments(): void
    {
        require_admin_permission('admin.comments.moderate');

        /** @var CommentAdminService $service */
        $service = $this->service('admin/CommentAdminService');
        $data = $service->buildManageCommentsData($_GET);

        $this->adminView('admin/comments/index', $data);
    }

    public function hideComment(string $id): void
    {
        require_admin_permission('admin.comments.moderate');

        /** @var CommentAdminService $service */
        $service = $this->service('admin/CommentAdminService');
        $service->hideComment((int) $id, (string) ($_POST['content_type'] ?? 'recipe'), current_admin());

        $this->redirect('/admin/comments?notice=hidden');
    }

    public function restoreComment(string $id): void
    {
        require_admin_permission('admin.comments.moderate');

        /** @var CommentAdminService $service */
        $service = $this->service('admin/CommentAdminService');
        $service->restoreComment((int) $id, (string) ($_POST['content_type'] ?? 'recipe'), current_admin());

        $this->redirect('/admin/comments?notice=restored');
    }

    public function deleteComment(string $id): void
    {
        require_admin_permission('admin.comments.moderate');

        /** @var CommentAdminService $service */
        $service = $this->service('admin/CommentAdminService');
        $service->deleteComment((int) $id, (string) ($_POST['content_type'] ?? 'recipe'), current_admin());

        $this->redirect('/admin/comments?notice=deleted');
    }
}