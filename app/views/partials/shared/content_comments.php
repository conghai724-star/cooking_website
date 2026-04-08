<?php
$commentsRootId = (string) ($commentsRootId ?? 'content-comments-section');
$commentsTitle = (string) ($commentsTitle ?? html_entity_decode('B&#236;nh lu&#7853;n c&#7897;ng &#273;&#7891;ng', ENT_QUOTES, 'UTF-8'));
$comments = is_array($comments ?? null) ? $comments : [];
$contentType = (string) ($contentType ?? 'recipe');
$contentId = (int) ($contentId ?? 0);
$redirectTo = (string) ($redirectTo ?? '');
$allowReply = (bool) ($allowReply ?? true);
$maxReplyDepth = (int) ($maxReplyDepth ?? 1);
$emptyText = (string) ($emptyText ?? html_entity_decode('Ch&#432;a c&#243; b&#236;nh lu&#7853;n n&#224;o.', ENT_QUOTES, 'UTF-8'));
$formPlaceholder = (string) ($formPlaceholder ?? html_entity_decode('Vi&#7871;t b&#236;nh lu&#7853;n c&#7911;a b&#7841;n...', ENT_QUOTES, 'UTF-8'));
$showCount = (bool) ($showCount ?? false);
$commentExtraHiddenFields = is_array($commentExtraHiddenFields ?? null) ? $commentExtraHiddenFields : [];
$commentNoticeMap = is_array($commentNoticeMap ?? null) ? $commentNoticeMap : [
    'comment_reported' => html_entity_decode('&#272;&#227; g&#7917;i b&#225;o c&#225;o b&#236;nh lu&#7853;n. C&#7843;m &#417;n b&#7841;n &#273;&#227; ph&#7843;n h&#7891;i.', ENT_QUOTES, 'UTF-8'),
    'comment_reported_exists' => html_entity_decode('B&#7841;n &#273;&#227; b&#225;o c&#225;o b&#236;nh lu&#7853;n n&#224;y tr&#432;&#7899;c &#273;&#243;.', ENT_QUOTES, 'UTF-8'),
    'comment_report_invalid' => html_entity_decode('B&#7841;n kh&#244;ng th&#7875; t&#7921; b&#225;o c&#225;o b&#236;nh lu&#7853;n c&#7911;a ch&#237;nh m&#236;nh.', ENT_QUOTES, 'UTF-8'),
    'comment_report_failed' => html_entity_decode('Kh&#244;ng th&#7875; g&#7917;i b&#225;o c&#225;o b&#236;nh lu&#7853;n l&#250;c n&#224;y.', ENT_QUOTES, 'UTF-8'),
    'comment_locked' => html_entity_decode('B&#7841;n &#273;ang b&#7883; kh&#243;a quy&#7873;n b&#236;nh lu&#7853;n.', ENT_QUOTES, 'UTF-8'),
];

$commentNotice = (string) ($_GET['notice'] ?? '');
$commentNoticeText = (string) ($commentNoticeMap[$commentNotice] ?? '');

$byId = [];
$roots = [];
$children = [];
foreach ($comments as $comment) {
    if (!is_array($comment)) {
        continue;
    }

    $commentId = (int) ($comment['id'] ?? 0);
    if ($commentId <= 0) {
        continue;
    }

    $byId[$commentId] = $comment;
    $parentId = (int) ($comment['parent_id'] ?? 0);
    if ($parentId > 0) {
        $children[$parentId][] = $comment;
    } else {
        $roots[] = $comment;
    }
}
?>

<div id="<?= htmlspecialchars($commentsRootId, ENT_QUOTES, 'UTF-8'); ?>" class="mt-16 border-t border-slate-200 pt-10">
    <h3 class="mb-6 text-2xl font-black">
        <?= htmlspecialchars($commentsTitle, ENT_QUOTES, 'UTF-8'); ?>
        <?php if ($showCount): ?>
            (<?= count($comments); ?>)
        <?php endif; ?>
    </h3>

    <?php if ($commentNoticeText !== ''): ?>
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
            <?= htmlspecialchars($commentNoticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (is_logged_in() && function_exists('user_has_permission') && user_has_permission('user.comments.create')): ?>
        <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-4">
            <form method="post" action="<?= URLROOT; ?>/comments/store" class="js-comment-form space-y-3" data-comment-ajax data-comments-root="#<?= htmlspecialchars($commentsRootId, ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrf_field(); ?>
                <input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="content_id" value="<?= $contentId; ?>">
                <?php if ($redirectTo !== ''): ?>
                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                <?php foreach ($commentExtraHiddenFields as $fieldName => $fieldValue): ?>
                    <input type="hidden" name="<?= htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?= htmlspecialchars((string) $fieldValue, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endforeach; ?>
                <textarea class="min-h-[110px] w-full rounded-xl border border-slate-200 p-3 focus:border-primary focus:ring-primary" name="content" placeholder="<?= htmlspecialchars($formPlaceholder, ENT_QUOTES, 'UTF-8'); ?>" required></textarea>
                <div class="flex justify-end">
                    <button class="rounded-xl bg-primary px-5 py-2 font-bold text-white hover:bg-primary/90" type="submit"><?= html_entity_decode('&#272;&#259;ng b&#236;nh lu&#7853;n', ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if (!empty($roots)): ?>
        <?php
        $renderComment = null;
        $renderComment = static function (array $item, int $depth = 0) use (&$renderComment, $children, $byId, $contentType, $contentId, $allowReply, $maxReplyDepth, $commentsRootId, $redirectTo): void {
            $commentId = (int) ($item['id'] ?? 0);
            $authorName = (string) (($item['name'] ?? '') ?: html_entity_decode('&#7848;n danh', ENT_QUOTES, 'UTF-8'));
            $parentId = (int) ($item['parent_id'] ?? 0);
            $parentName = '';

            if ($parentId > 0 && isset($byId[$parentId])) {
                $parentName = (string) ($byId[$parentId]['name'] ?? '');
            }

            $rawContent = (string) ($item['content'] ?? '');
            if (preg_match('/^@\[(.+?)\]\s*/u', $rawContent, $matchReplyTo) === 1) {
                $parentName = (string) ($matchReplyTo[1] ?? $parentName);
                $rawContent = preg_replace('/^@\[(.+?)\]\s*/u', '', $rawContent, 1) ?? $rawContent;
            }
            ?>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 <?= $depth > 0 ? 'ml-6' : ''; ?>">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm"><?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span class="text-xs text-slate-400"><?= htmlspecialchars((string) substr((string) ($item['created_at'] ?? ''), 0, 16), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php if (is_logged_in() && function_exists('user_has_permission') && user_has_permission('user.comments.report') && (int) current_user_id() !== (int) ($item['user_id'] ?? 0)): ?>
                        <?php
                        $reportCommentId = $commentId;
                        $reportContentType = $contentType;
                        $reportHiddenFields = $redirectTo !== '' ? ['redirect_to' => $redirectTo] : [];
                        require APPROOT . '/app/views/partials/shared/comment_report_dropdown.php';
                        ?>
                    <?php endif; ?>
                </div>

                <?php if ($parentName !== ''): ?>
                    <p class="mb-1 text-xs font-semibold text-primary"><?= html_entity_decode('Tr&#7843; l&#7901;i', ENT_QUOTES, 'UTF-8'); ?> @<?= htmlspecialchars($parentName, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <p class="text-sm leading-6 text-slate-700"><?= nl2br(htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8')); ?></p>

                <div class="mt-2 flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:border-primary hover:text-primary"
                        data-comment-vote
                        data-comment-id="<?= $commentId; ?>"
                        data-vote-url="<?= URLROOT; ?>/comments/<?= $commentId; ?>/vote"
                        data-csrf-token="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        Upvote
                    </button>
                    <span class="text-xs text-slate-500" data-comment-like-count="<?= $commentId; ?>"><?= (int) ($item['like_count'] ?? 0); ?></span>
                </div>

                <?php if (is_logged_in() && function_exists('user_has_permission') && user_has_permission('user.comments.reply') && $allowReply && $depth < $maxReplyDepth): ?>
                    <details class="mt-3">
                        <summary class="cursor-pointer text-xs font-semibold text-primary"><?= html_entity_decode('Tr&#7843; l&#7901;i b&#236;nh lu&#7853;n', ENT_QUOTES, 'UTF-8'); ?></summary>
                        <form method="post" action="<?= URLROOT; ?>/comments/store" class="js-comment-form mt-2 space-y-2" data-comment-ajax data-comments-root="#<?= htmlspecialchars($commentsRootId, ENT_QUOTES, 'UTF-8'); ?>">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="content_id" value="<?= $contentId; ?>">
                            <input type="hidden" name="parent_id" value="<?= $commentId; ?>">
                            <input type="hidden" name="reply_to_name" value="<?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if ($redirectTo !== ''): ?>
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php endif; ?>
                            <textarea class="w-full rounded-lg border border-slate-200 p-2 text-sm" name="content" rows="2" required></textarea>
                            <button class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-bold text-white" type="submit"><?= html_entity_decode('G&#7917;i tr&#7843; l&#7901;i', ENT_QUOTES, 'UTF-8'); ?></button>
                        </form>
                    </details>
                <?php endif; ?>

                <?php if (!empty($children[$commentId]) && $depth < $maxReplyDepth): ?>
                    <div class="mt-3 space-y-2 border-l border-slate-200 pl-3">
                        <?php foreach ($children[$commentId] as $reply): ?>
                            <?php $renderComment($reply, $depth + 1); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
            <?php
        };
        ?>

        <div class="space-y-4">
            <?php foreach ($roots as $comment): ?>
                <?php $renderComment($comment, 0); ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-500"><?= htmlspecialchars($emptyText, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
</div>




