<div class="w-full">
    <div class="mx-auto max-w-6xl px-2 py-4 sm:px-4">
        <?php require APPROOT . '/app/views/recipes/partials/hero.php'; ?>

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
            <?php require APPROOT . '/app/views/recipes/partials/content_main.php'; ?>
            <?php require APPROOT . '/app/views/recipes/partials/quick_actions.php'; ?>
        </div>

        <?php
        $commentsRootId = 'recipe-comments-section';
        $commentsTitle = 'B�nh lu?n c?ng d?ng';
        $contentType = 'recipe';
        $contentId = (int) ($recipe['id'] ?? 0);
        $comments = is_array($comments ?? null) ? $comments : [];
        $showCount = false;
        $allowReply = true;
        $maxReplyDepth = 50;
        $emptyText = 'Chua c� b�nh lu?n n�o.';
        $formPlaceholder = 'Vi?t b�nh lu?n c?a b?n...';
        $commentExtraHiddenFields = ['recipe_id' => (string) ((int) ($recipe['id'] ?? 0))];
        require APPROOT . '/app/views/partials/shared/content_comments.php';
        ?>
    </div>
</div>

<?php
$reportModalId = 'recipe-report-modal';
$reportModalTitle = 'B�o c�o c�ng th?c';
$reportModalAction = URLROOT . '/recipes/report';
$reportModalReasonField = 'reason';
$reportModalDetailsField = 'reason_other';
$reportModalSuccessToast = '�� g?i b�o c�o c�ng th?c.';
$reportModalErrorToast = 'Kh�ng th? g?i b�o c�o c�ng th?c l�c n�y.';
$reportModalHiddenFields = ['recipe_id' => (string) ((int) ($recipe['id'] ?? 0))];
require APPROOT . '/app/views/partials/shared/content_report_modal.php';
?>

