<?php
$tip = is_array($tip ?? null) ? $tip : [];
$comments = is_array($comments ?? null) ? $comments : [];
$authorUser = is_array($authorUser ?? null) ? $authorUser : null;
$isFollowingAuthor = (bool) ($isFollowingAuthor ?? false);
$isSavedTip = (bool) ($isSavedTip ?? false);

$tipId = (int) ($tip['id'] ?? 0);
$slug = (string) ($tip['slug'] ?? '');
$title = (string) ($tip['title'] ?? 'Mẹo vặt nA�º¥u A�ƒn');
$excerpt = (string) ($tip['excerpt'] ?? '');
$content = (string) ($tip['content'] ?? '');
$image = (string) ($tip['image'] ?? '');
$author = (string) (($tip['author_name'] ?? '') ?: 'TĂ¡c giA�º£');
$authorId = (int) ($tip['user_id'] ?? 0);
$viewCount = (int) ($tip['view_count'] ?? 0);
$categoryLabel = (string) ($tip['category_name'] ?? '');

$tipPath = '/tips' . ($slug !== '' ? '/' . rawurlencode($slug) : '');
$tipRedirectPath = $tipPath . '#tip-comments-section';

$tipNotice = (string) ($_GET['notice'] ?? '');
$tipNoticeText = match ($tipNotice) {
    'tip_reported' => 'A�¿½A? g?i bA?o cA?o m?o v?t. C?m on b?n dA? ph?n h?i.',
    'tip_reported_exists' => 'BA�º¡n A�‘Ă£ bĂ¡o cĂ¡o mA�º¹o vA�º·t nĂ y trA�°A�»›c A�‘Ă³.',
    'tip_saved' => 'ĐA� lưu mẹo vặt.',
    'tip_unsaved' => 'ĐA� bỏ lưu mẹo vặt.',
    default => '',
};
?>

<div class="w-full">
    <div class="mx-auto max-w-7xl px-2 py-4 sm:px-4">
        <?php
        $breadcrumbItems = [
            ['label' => 'M?o v?t', 'url' => URLROOT . '/tips'],
            ['label' => $title],
        ];
        require APPROOT . '/app/views/tips/partials/breadcrumb.php';

        $heroImage = $image;
        $heroTitle = $title;
        $heroCategory = $categoryLabel !== '' ? $categoryLabel : 'M?o n?u an';
        $heroAuthor = (string) (($authorUser['name'] ?? null) ?: $author);
        $heroDate = (string) substr((string) ($tip['created_at'] ?? date('Y-m-d')), 0, 10);
        require APPROOT . '/app/views/tips/partials/hero.php';

        $noticeText = $tipNoticeText;
        require APPROOT . '/app/views/tips/partials/notice_alert.php';
        ?>

        <style>.detail-layout{display:flex;gap:2rem;align-items:flex-start}.detail-main{flex:1 1 auto;min-width:0}.detail-side{flex:0 0 300px;max-width:300px}@media (max-width:700px){.detail-layout{display:block}.detail-side{max-width:none;width:auto}}</style>
        <div class="detail-layout">
            <div class="detail-main" style="min-width:0;">
                <?php if ($excerpt !== ''): ?>
                    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 text-slate-700">
                        <?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <article class="mb-10 rounded-2xl border border-slate-200 bg-white p-6 leading-7 text-slate-700">
                    <?= nl2br(htmlspecialchars($content !== '' ? $content : 'NA�»™i dung mA�º¹o vA�º·t A�‘ang A�‘A�°A�»£c cA�º­p nhA�º­t.', ENT_QUOTES, 'UTF-8')); ?>
                </article>
            </div>

            <?php
            $sidebarCategory = $categoryLabel;
            $sidebarViews = $viewCount;
            $sidebarAuthor = (string) (($authorUser['name'] ?? null) ?: $author);
            $sidebarAuthorId = $authorId;
            $sidebarIsFollowing = $isFollowingAuthor;
            $sidebarIsSaved = $isSavedTip;
            $sidebarTipId = $tipId;
            $sidebarTipPath = $tipPath;
            $sidebarTitle = $title;
            require APPROOT . '/app/views/tips/partials/sidebar.php';
            ?>
        </div>

        <?php
        $commentsRootId = 'tip-comments-section';
        $commentsTitle = 'Bình luận cA�»™ng A�‘A�»“ng';
        $contentType = 'tip';
        $contentId = $tipId;
        $redirectTo = $tipRedirectPath;
        $showCount = false;
        $allowReply = true;
        $maxReplyDepth = 1;
        $emptyText = 'ChA�°a cĂ³ bĂ¬nh luA�º­n nĂ o cho mA�º¹o vA�º·t nĂ y.';
        $formPlaceholder = 'ViA�º¿t bĂ¬nh luA�º­n cA�»§a bA�º¡n...';
        require APPROOT . '/app/views/partials/shared/content_comments.php';
        ?>
    </div>
</div>
<?php
$reportModalId = 'tip-report-modal';
$reportModalTitle = 'BĂ¡o cĂ¡o mA�º¹o vA�º·t';
$reportModalAction = URLROOT . '/tips/' . $tipId . '/report';
$reportModalReasonField = 'reason';
$reportModalDetailsField = 'details';
$reportModalSuccessToast = 'A�¿½A? g?i bA?o cA?o m?o v?t.';
$reportModalErrorToast = 'KhA�ng thA�»ƒ gA�»­i bĂ¡o cĂ¡o mA�º¹o vA�º·t.';
$reportModalHiddenFields = ['redirect_to' => $tipPath];
require APPROOT . '/app/views/partials/shared/content_report_modal.php';
?>













