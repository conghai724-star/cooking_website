<?php

declare(strict_types=1);

class TipsController extends Controller
{
    public function index(): void
    {
        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $perPage = 3;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = $tipModel->countByStatus('approved', $keyword !== '' ? $keyword : null);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $tips = $tipModel->allPaged('approved', $perPage, $offset, $keyword !== '' ? $keyword : null);

        $this->view('tips/index', [
            'title' => 'M?o v?t',
            'useRecipeHubLayout' => true,
            'tips' => $tips,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
        ]);
    }

    public function show(string $slug): void
    {
        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $tip = $tipModel->findBySlug($slug);
        if (!$tip) {
            $this->renderNotFound('Kh�ng t�m th?y m?o v?t.');
            return;
        }

        $viewerId = (int) (current_user_id() ?? 0);
        $isOwner = $viewerId > 0 && $viewerId === (int) ($tip['user_id'] ?? 0);

        if (($tip['status'] ?? 'approved') !== 'approved' && !is_admin() && !$isOwner) {
            $this->renderNotFound('Kh�ng t�m th?y m?o v?t.');
            return;
        }

        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');
        $comments = $commentModel->byTip((int) ($tip['id'] ?? 0));

        $authorUser = null;
        $isFollowingAuthor = false;
        $isSavedTip = false;

        $authorUserId = (int) ($tip['user_id'] ?? 0);
        if ($authorUserId > 0) {
            /** @var UserModel $userModel */
            $userModel = $this->model('UserModel');
            $authorUser = $userModel->findById($authorUserId) ?: null;

            if ($viewerId > 0 && $viewerId !== $authorUserId) {
                /** @var FollowModel $followModel */
                $followModel = $this->model('FollowModel');
                $isFollowingAuthor = $followModel->isFollowing($viewerId, $authorUserId);
            }
        }

        if ($viewerId > 0) {
            $isSavedTip = $tipModel->isSaved($viewerId, (int) ($tip['id'] ?? 0));
        }

        $this->view('tips/show', [
            'title' => $tip['title'] ?? 'M?o v?t',
            'useRecipeHubLayout' => true,
            'tip' => $tip,
            'comments' => $comments,
            'authorUser' => $authorUser,
            'isFollowingAuthor' => $isFollowingAuthor,
            'isSavedTip' => $isSavedTip,
        ]);
    }

    public function create(): void
    {
        require_login();
        $this->abortIfTipPostLocked('/tips');

        $error = '';
        $success = false;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
            $content = trim((string) ($_POST['content'] ?? ''));
            $title = trim(profanity_mask($title));
            $excerpt = trim(profanity_mask($excerpt));
            $content = trim(profanity_mask($content));

            if ($title === '' || $content === '') {
                $error = 'Vui l�ng nh?p d?y d? ti�u d? v� n?i dung.';
            } else {
                $slug = $this->slugify($title);
                /** @var TipModel $tipModel */
                $tipModel = $this->model('TipModel');
                $original = $slug;
                $suffix = 2;
                while ($tipModel->slugExists($slug)) {
                    $slug = $original . '-' . $suffix;
                    $suffix++;
                }

                $image = upload_image('cover_image', APPROOT . '/public/uploads');
                $author = (string) ((current_user()['name'] ?? '') ?: (current_user()['username'] ?? 'Nguoi dung'));
                $id = $tipModel->create(
                    (int) current_user_id(),
                    $title,
                    $slug,
                    $excerpt !== '' ? $excerpt : null,
                    $content,
                    $image,
                    $author,
                    'pending'
                );

                if ($id === false) {
                    $error = 'Kh�ng th? g?i m?o v?t.';
                } else {
                    $this->redirect('/tips/' . $slug . '?notice=created_success');
                }
            }
        }

        $this->view('tips/create', [
            'title' => 'G�p � m?o v?t',
            'useRecipeHubLayout' => true,
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function myTips(): void
    {
        require_login();

        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $tips = $tipModel->byUser((int) current_user_id());

        $this->view('tips/my', [
            'title' => 'M?o v?t c?a t�i',
            'useRecipeHubLayout' => true,
            'tips' => $tips,
        ]);
    }

    public function resubmit(string $id): void
    {
        require_login();
        $this->abortIfTipPostLocked('/tips/my');
        $tipId = (int) $id;
        if ($tipId <= 0) {
            $this->redirect('/tips/my');
        }

        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $tipModel->resubmit($tipId, (int) current_user_id());
        $this->redirect('/tips/my');
    }

    public function report(string $id): void
    {
        require_login();

        $isAjax = $this->isAjaxRequest();
        $tipId = (int) $id;
        if ($tipId <= 0) {
            if ($isAjax) {
                $this->jsonError('BAD_REQUEST', 'Kh�ng t�m th?y m?o v?t.', 400);
            }
            $this->redirect('/tips');
        }

        $reason = trim((string) ($_POST['reason'] ?? ''));
        $details = trim((string) (($_POST['details'] ?? '') ?: ($_POST['reason_other'] ?? '')));
        $normalizedReason = strtolower($reason);
        if ($reason !== '' && in_array($normalizedReason, ['kh�c', 'khac'], true) && $details !== '') {
            $reason = $details;
        }
        if ($reason === '') {
            $reason = 'N?i dung m?o v?t c� d?u hi?u vi ph?m.';
        }

        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $ok = $tipModel->saveReport((int) current_user_id(), $tipId, $reason);
        if ($ok) {
            /** @var NotificationModel $notificationModel */
            $notificationModel = $this->model('NotificationModel');
            $notificationModel->createForAdmins(
                'report_tip',
                'C� b�o c�o m?o v?t m?i (ID: ' . $tipId . ').'
            );
        }

        if ($isAjax) {
            if ($ok) {
                $this->jsonSuccess([], '�� g?i b�o c�o m?o v?t.', 201);
            }
            $this->jsonError('CONFLICT', 'B?n d� b�o c�o m?o v?t n�y.', 409);
        }

        $tip = $tipModel->findById($tipId);
        $slug = (string) ($tip['slug'] ?? '');
        $path = $slug !== '' ? '/tips/' . rawurlencode($slug) : '/tips';

        $this->redirect($path . '?notice=' . ($ok ? 'tip_reported' : 'tip_reported_exists'));
    }

    public function save(): void
    {
        require_login();

        $tipId = (int) ($_POST['tip_id'] ?? 0);
        $redirectTo = trim((string) ($_POST['redirect_to'] ?? '/tips'));
        if ($tipId <= 0) {
            $this->redirect($redirectTo);
        }

        /** @var TipModel $tipModel */
        $tipModel = $this->model('TipModel');
        $tipModel->toggleSave((int) current_user_id(), $tipId);
        $saved = $tipModel->isSaved((int) current_user_id(), $tipId);

        $glue = str_contains($redirectTo, '?') ? '&' : '?';
        $this->redirect($redirectTo . $glue . 'notice=' . ($saved ? 'tip_saved' : 'tip_unsaved'));
    }

    private function slugify(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/[^\pL\pN]+/u', '-', $text) ?? '';
        $text = trim($text, '-');
        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text, 'UTF-8');
        } else {
            $text = strtolower($text);
        }
        return $text !== '' ? $text : 'tip';
    }

    private function isAjaxRequest(): bool
    {
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        return strcasecmp($requestedWith, 'XMLHttpRequest') === 0 || str_contains($accept, 'application/json');
    }

    private function abortIfTipPostLocked(string $fallbackRedirect): void
    {
        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');
        $activeLock = $penaltyModel->getActiveTipPostLock((int) current_user_id());
        if (!$activeLock) {
            return;
        }

        $reason = trim((string) ($activeLock['reason'] ?? 'Vi ph?m n?i dung c?ng d?ng'));
        if ($reason === '') {
            $reason = 'Vi ph?m n?i dung c?ng d?ng';
        }
        $until = trim((string) ($activeLock['banned_until'] ?? ''));
        $notice = $until !== ''
            ? 'B?n dang b? kh�a dang m?o d?n ' . $until . '. L� do: ' . $reason
            : 'B?n dang b? kh�a dang m?o vinh vi?n. L� do: ' . $reason;

        $separator = str_contains($fallbackRedirect, '?') ? '&' : '?';
        $this->redirect($fallbackRedirect . $separator . 'notice=' . rawurlencode($notice));
    }
}
