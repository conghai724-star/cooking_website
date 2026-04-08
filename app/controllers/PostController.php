<?php

declare(strict_types=1);

class PostController extends Controller
{
    public function index(): void
    {
        /** @var PostModel $postModel */
        $postModel = $this->model('PostModel');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $perPage = 10;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = $postModel->countApproved($keyword !== '' ? $keyword : null);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $posts = $postModel->allApprovedPaged($perPage, $offset, $keyword !== '' ? $keyword : null);

        $this->view('posts/index', [
            'title' => 'Cong dong hoi dap',
            'useRecipeHubLayout' => true,
            'posts' => $posts,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
        ]);
    }

    public function show(string $id): void
    {
        $postId = (int) $id;
        if ($postId <= 0) {
            $this->renderNotFound('Khong tim thay bai viet.');
            return;
        }

        /** @var PostModel $postModel */
        $postModel = $this->model('PostModel');
        $post = $postModel->findById($postId);
        if (!$post) {
            $this->renderNotFound('Khong tim thay bai viet.');
            return;
        }

        $viewerId = (int) (current_user_id() ?? 0);
        $isOwner = $viewerId > 0 && $viewerId === (int) ($post['user_id'] ?? 0);
        $status = (string) ($post['status'] ?? 'approved');
        if (!$isOwner && !is_admin() && $status !== 'approved') {
            $this->renderNotFound('Khong tim thay bai viet.');
            return;
        }

        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');
        $comments = $commentModel->byPost($postId);

        $this->view('posts/show', [
            'title' => (string) ($post['title'] ?? 'Bai viet'),
            'useRecipeHubLayout' => true,
            'post' => $post,
            'comments' => $comments,
        ]);
    }

    public function create(): void
    {
        require_login();
        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $content = trim((string) ($_POST['content'] ?? ''));
            $title = trim(profanity_mask($title));
            $content = trim(profanity_mask($content));
            if ($title === '' || $content === '') {
                $error = 'Vui long nhap tieu de va noi dung.';
            } else {
                $image = upload_image('image', APPROOT . '/public/uploads');

                /** @var PostModel $postModel */
                $postModel = $this->model('PostModel');
                $postId = $postModel->create((int) current_user_id(), $title, $content, $image !== '' ? $image : null);

                if ($postId === false) {
                    $error = 'Khong the dang bai viet luc nay.';
                } else {
                    system_log_write(
                        'content_action',
                        'post.create',
                        'success',
                        null,
                        'post',
                        (int) $postId,
                        [],
                        (int) current_user_id(),
                        (string) (current_user()['role'] ?? 'user')
                    );
                    $this->redirect('/posts/' . (int) $postId);
                }
            }
        }

        $this->view('posts/create', [
            'title' => 'Dang cau hoi',
            'useRecipeHubLayout' => true,
            'error' => $error,
        ]);
    }

    public function edit(string $id): void
    {
        require_login();

        $postId = (int) $id;
        if ($postId <= 0) {
            $this->redirect('/posts');
        }

        /** @var PostModel $postModel */
        $postModel = $this->model('PostModel');
        $post = $postModel->findById($postId);
        if (!$post || (int) ($post['user_id'] ?? 0) !== (int) current_user_id()) {
            $this->redirect('/posts');
        }

        $error = '';
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $content = trim((string) ($_POST['content'] ?? ''));
            $title = trim(profanity_mask($title));
            $content = trim(profanity_mask($content));
            if ($title === '' || $content === '') {
                $error = 'Vui long nhap tieu de va noi dung.';
            } else {
                $image = upload_image('image', APPROOT . '/public/uploads');
                $imageValue = $image !== '' ? $image : (string) ($post['image'] ?? '');
                $ok = $postModel->updateByOwner(
                    $postId,
                    (int) current_user_id(),
                    $title,
                    $content,
                    $imageValue !== '' ? $imageValue : null
                );
                if (!$ok) {
                    $error = 'Khong the cap nhat bai viet.';
                } else {
                    system_log_write(
                        'content_action',
                        'post.update',
                        'success',
                        null,
                        'post',
                        $postId,
                        [],
                        (int) current_user_id(),
                        (string) (current_user()['role'] ?? 'user')
                    );
                    $this->redirect('/posts/' . $postId);
                }
            }
        }

        $this->view('posts/edit', [
            'title' => 'Sua bai viet',
            'useRecipeHubLayout' => true,
            'post' => $post,
            'error' => $error,
        ]);
    }

    public function delete(string $id): void
    {
        require_login();

        $postId = (int) $id;
        if ($postId <= 0) {
            $this->redirect('/posts');
        }

        /** @var PostModel $postModel */
        $postModel = $this->model('PostModel');
        $ok = $postModel->deleteByOwner($postId, (int) current_user_id());

        if ($ok) {
            system_log_write(
                'content_action',
                'post.delete',
                'success',
                null,
                'post',
                $postId,
                [],
                (int) current_user_id(),
                (string) (current_user()['role'] ?? 'user')
            );
        }

        $this->redirect('/posts');
    }

    public function report(string $id): void
    {
        require_login();

        $postId = (int) $id;
        if ($postId <= 0) {
            $this->redirect('/posts');
        }

        /** @var PostModel $postModel */
        $postModel = $this->model('PostModel');
        $post = $postModel->findById($postId);
        if (!$post) {
            $this->redirect('/posts');
        }

        $redirectTo = '/posts/' . $postId;
        $currentUserId = (int) current_user_id();
        if ($currentUserId === (int) ($post['user_id'] ?? 0)) {
            $this->redirect($redirectTo . '?notice=post_report_invalid');
        }

        $reason = trim((string) ($_POST['reason'] ?? 'Noi dung khong phu hop'));
        $details = trim((string) ($_POST['details'] ?? ''));
        if ($reason === '') {
            $reason = 'Noi dung khong phu hop';
        }

        if ($postModel->hasReported($currentUserId, $postId)) {
            $this->redirect($redirectTo . '?notice=post_reported_exists');
        }

        $ok = $postModel->saveReport($currentUserId, $postId, $reason, $details !== '' ? $details : null);
        if (!$ok) {
            $this->redirect($redirectTo . '?notice=post_report_failed');
        }

        /** @var NotificationModel $notificationModel */
        $notificationModel = $this->model('NotificationModel');
        $ownerId = (int) ($post['user_id'] ?? 0);
        if ($ownerId > 0 && $ownerId !== $currentUserId) {
            $notificationModel->create(
                $ownerId,
                'report_post',
                'Bai viet cua ban da nhan mot bao cao tu cong dong.',
                $redirectTo
            );
        }

        $this->redirect($redirectTo . '?notice=post_reported');
    }
}
