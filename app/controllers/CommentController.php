<?php

declare(strict_types=1);

class CommentController extends Controller
{
    private function isAjaxRequest(): bool
    {
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        return strcasecmp($requestedWith, 'XMLHttpRequest') === 0 || str_contains($accept, 'application/json');
    }

    private function respondStoreResult(bool $ok, string $targetPath, string $message): void
    {
        if ($this->isAjaxRequest()) {
            if ($ok) {
                $this->jsonSuccess([
                    'redirect' => URLROOT . $targetPath,
                ], $message, 201);
            }
            $this->jsonError('VALIDATION_ERROR', $message, 422);
        }
    }

    public function store(): void
    {
        require_login();

        $contentType = (string) ($_POST['content_type'] ?? 'recipe');
        if (!in_array($contentType, ['recipe', 'tip', 'ingredient', 'post'], true)) {
            $contentType = 'recipe';
        }

        $contentId = (int) ($_POST['content_id'] ?? 0);
        if ($contentId <= 0) {
            $contentId = match ($contentType) {
                'tip' => (int) ($_POST['tip_id'] ?? 0),
                'ingredient' => (int) ($_POST['ingredient_id'] ?? 0),
                'post' => (int) ($_POST['post_id'] ?? 0),
                default => (int) ($_POST['recipe_id'] ?? 0),
            };
        }
        $parentId = (int) ($_POST['parent_id'] ?? 0);
        $content = trim((string) ($_POST['content'] ?? ''));
        $content = trim(profanity_mask($content));

        $targetPath = match ($contentType) {
            'tip' => (string) ($_POST['redirect_to'] ?? '/tips'),
            'ingredient' => '/ingredients/' . $contentId,
            'post' => '/posts/' . $contentId,
            default => '/recipes/' . $contentId,
        };

        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');
        $activeLock = $penaltyModel->getActiveCommentLock((int) current_user_id());
        if ($activeLock) {
            $until = (string) ($activeLock['banned_until'] ?? '');
            $reason = trim((string) ($activeLock['reason'] ?? 'Vi ph?m n?i dung c?ng d?ng'));
            $message = $until !== ''
                ? ('B?n dang b? kh�a quy?n b�nh lu?n d?n ' . $until . '. L� do: ' . $reason)
                : ('B?n dang b? kh�a quy?n b�nh lu?n vinh vi?n. L� do: ' . $reason);

            $this->respondStoreResult(false, $targetPath, $message);
            $this->redirect($targetPath . '?notice=comment_locked');
        }

        $ok = false;
        if ($contentId > 0 && $content !== '') {
            /** @var CommentModel $commentModel */
            $commentModel = $this->model('CommentModel');
            $userId = (int) current_user_id();
            $replyToName = trim((string) ($_POST['reply_to_name'] ?? ''));
            $isReplyToChild = false;

            if ($contentType === 'recipe') {
                if ($parentId > 0) {
                    $parent = $commentModel->findById($parentId);
                    if ($parent && (int) ($parent['recipe_id'] ?? 0) === $contentId) {
                        $grandParentId = (int) ($parent['parent_id'] ?? 0);
                        if ($grandParentId > 0) {
                            $parentId = $grandParentId;
                            $isReplyToChild = true;
                        }
                    } else {
                        $parentId = 0;
                    }
                }

                if ($isReplyToChild && $replyToName !== '' && !str_starts_with($content, '@[')) {
                    $content = '@[' . $replyToName . '] ' . $content;
                }

                if ($parentId > 0) {
                    $ok = $commentModel->createReply($userId, $contentId, $parentId, $content);
                } else {
                    $ok = $commentModel->create($userId, $contentId, $content);
                }
            } elseif ($contentType === 'tip') {
                $safeParentId = null;
                if ($parentId > 0) {
                    $parent = $commentModel->findTipCommentById($parentId);
                    if ($parent && (int) ($parent['tip_id'] ?? 0) === $contentId) {
                        $grandParentId = (int) ($parent['parent_id'] ?? 0);
                        if ($grandParentId > 0) {
                            $safeParentId = $grandParentId;
                        } else {
                            $safeParentId = $parentId;
                        }
                    }
                }

                $ok = $commentModel->createTip($userId, $contentId, $content, $safeParentId);
            } elseif ($contentType === 'post') {
                $safeParentId = null;
                if ($parentId > 0) {
                    $parent = $commentModel->findPostCommentById($parentId);
                    if ($parent && (int) ($parent['post_id'] ?? 0) === $contentId) {
                        $grandParentId = (int) ($parent['parent_id'] ?? 0);
                        if ($grandParentId > 0) {
                            $safeParentId = $grandParentId;
                            $isReplyToChild = true;
                        } else {
                            $safeParentId = $parentId;
                        }
                    }
                }
                if ($isReplyToChild && $replyToName !== '' && !str_starts_with($content, '@[')) {
                    $content = '@[' . $replyToName . '] ' . $content;
                }
                $ok = $commentModel->createPost($userId, $contentId, $content, $safeParentId);
            } else {
                // Nguyen lieu: khong cho reply.
                $ok = $commentModel->createIngredient($userId, $contentId, $content);
            }

            if ($ok) {
                system_log_write(
                    'content_action',
                    $parentId > 0 ? 'comment.reply' : 'comment.create',
                    'success',
                    null,
                    $contentType,
                    $contentId,
                    [
                        'content_type' => $contentType,
                        'content_id' => $contentId,
                        'parent_id' => $parentId > 0 ? $parentId : null,
                    ],
                    $userId,
                    (string) (current_user()['role'] ?? 'user')
                );
                $targetUserId = 0;
                if ($contentType === 'recipe') {
                    if ($parentId > 0) {
                        $parentComment = $commentModel->findById($parentId);
                        $targetUserId = (int) ($parentComment['user_id'] ?? 0);
                    }
                    if ($targetUserId <= 0) {
                        /** @var RecipeModel $recipeModel */
                        $recipeModel = $this->model('RecipeModel');
                        $recipe = $recipeModel->findById($contentId);
                        if (is_array($recipe)) {
                            $targetUserId = (int) ($recipe['user_id'] ?? 0);
                        }
                    }
                } elseif ($contentType === 'tip') {
                    /** @var TipModel $tipModel */
                    $tipModel = $this->model('TipModel');
                    $tip = $tipModel->findById($contentId);
                    if (is_array($tip)) {
                        $targetUserId = (int) ($tip['user_id'] ?? 0);
                    }
                } elseif ($contentType === 'post') {
                    /** @var PostModel $postModel */
                    $postModel = $this->model('PostModel');
                    $post = $postModel->findById($contentId);
                    if (is_array($post)) {
                        $targetUserId = (int) ($post['user_id'] ?? 0);
                    }
                } else {
                    $db = Database::getInstance();
                    $db->query('SELECT user_id FROM ingredients WHERE id = :id LIMIT 1')
                        ->bind(':id', $contentId)
                        ->execute();
                    $ingredient = $db->single();
                    if (is_array($ingredient)) {
                        $targetUserId = (int) ($ingredient['user_id'] ?? 0);
                    }
                }

                if ($targetUserId > 0 && $targetUserId !== $userId) {
                    /** @var NotificationModel $notificationModel */
                    $notificationModel = $this->model('NotificationModel');
                    $actorName = (string) (current_user()['name'] ?? ('User #' . $userId));
                    $notificationModel->create(
                        $targetUserId,
                        $parentId > 0 ? 'comment_reply' : 'comment',
                        $parentId > 0
                            ? ($actorName . ' da tra loi binh luan cua ban.')
                            : ($actorName . ' da binh luan bai viet cua ban.'),
                        $targetPath
                    );
                }
            }
        }

        $this->respondStoreResult($ok, $targetPath, $ok ? '�� dang b�nh lu?n' : 'Kh�ng th? dang b�nh lu?n');
        $this->redirect($targetPath);
    }

    public function report(string $id): void
    {
        require_login();

        $commentId = (int) $id;
        $contentType = (string) ($_POST['content_type'] ?? 'recipe');
        if (!in_array($contentType, ['recipe', 'tip', 'ingredient', 'post'], true)) {
            $contentType = 'recipe';
        }

        $reason = trim((string) ($_POST['reason'] ?? 'Nội dung không phù hợp'));
        $reasonOther = trim((string) ($_POST['reason_other'] ?? ''));

        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');
        $comment = match ($contentType) {
            'tip' => $commentModel->findTipCommentById($commentId),
            'ingredient' => $commentModel->findIngredientCommentById($commentId),
            'post' => $commentModel->findPostCommentById($commentId),
            default => $commentModel->findById($commentId),
        };
        if (!$comment) {
            $this->redirect('/recipes');
        }

        $targetPath = '/recipes';
        if ($contentType === 'tip') {
            $tipId = (int) ($comment['tip_id'] ?? 0);
            if ($tipId <= 0) {
                $this->redirect('/tips');
            }
            $redirectTo = trim((string) ($_POST['redirect_to'] ?? ''));
            $targetPath = $redirectTo !== '' ? $redirectTo : '/tips';
        } elseif ($contentType === 'post') {
            $postId = (int) ($comment['post_id'] ?? 0);
            if ($postId <= 0) {
                $this->redirect('/posts');
            }
            $targetPath = '/posts/' . $postId;
        } elseif ($contentType === 'ingredient') {
            $ingredientId = (int) ($comment['ingredient_id'] ?? 0);
            if ($ingredientId <= 0) {
                $this->redirect('/ingredients');
            }
            $targetPath = '/ingredients/' . $ingredientId;
        } else {
            $recipeId = (int) ($comment['recipe_id'] ?? 0);
            if ($recipeId <= 0) {
                $this->redirect('/recipes');
            }
            $targetPath = '/recipes/' . $recipeId;
        }

        $currentUserId = (int) current_user_id();
        $commentOwnerId = (int) ($comment['user_id'] ?? 0);
        if ($currentUserId === $commentOwnerId) {
            $this->redirect($targetPath . '?notice=comment_report_invalid');
        }

        if ($reason === 'Khác' && $reasonOther !== '') {
            $reason = 'Khác: ' . $reasonOther;
        }
        if ($reason === '' || $reason === 'Khác') {
            $reason = 'Nội dung không phù hợp';
        }

        $hasReported = match ($contentType) {
            'tip' => $commentModel->hasReportedTip($currentUserId, $commentId),
            'ingredient' => $commentModel->hasReportedIngredient($currentUserId, $commentId),
            'post' => $commentModel->hasReported($currentUserId, $commentId),
            default => $commentModel->hasReported($currentUserId, $commentId),
        };
        if ($hasReported) {
            $this->redirect($targetPath . '?notice=comment_reported_exists');
        }

        $ok = match ($contentType) {
            'tip' => $commentModel->reportTip($currentUserId, $commentId, $reason),
            'ingredient' => $commentModel->reportIngredient($currentUserId, $commentId, $reason),
            'post' => $commentModel->report($currentUserId, $commentId, $reason),
            default => $commentModel->report($currentUserId, $commentId, $reason),
        };

        if ($ok) {
            /** @var NotificationModel $notificationModel */
            $notificationModel = $this->model('NotificationModel');
            $notificationModel->createForAdmins(
                'report_comment',
                'Có báo cáo bình luận mới (' . $contentType . ', comment ID: ' . $commentId . ').'
            );
        }

        $this->redirect($targetPath . '?notice=' . ($ok ? 'comment_reported' : 'comment_report_failed'));
    }

    public function vote(string $id): void
    {
        require_login();

        $commentId = (int) $id;
        if ($commentId <= 0) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('VALIDATION_ERROR', 'Binh luan khong hop le.', 422);
            }
            $this->redirect('/recipes');
        }

        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');
        $result = $commentModel->toggleVote($commentId, (int) current_user_id());
        if ($result === false) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('NOT_FOUND', 'Khong tim thay binh luan.', 404);
            }
            $this->redirect('/recipes');
        }

        if ($this->isAjaxRequest()) {
            $this->jsonSuccess($result, 'Da cap nhat vote.');
        }

        $back = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
        if ($back !== '' && str_starts_with($back, URLROOT)) {
            $path = substr($back, strlen(URLROOT));
            $this->redirect($path !== '' ? $path : '/recipes');
        }
        $this->redirect('/recipes');
    }
}
