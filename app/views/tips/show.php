<?php
$tip = is_array($tip ?? null) ? $tip : [];
$comments = is_array($comments ?? null) ? $comments : [];
$authorUser = is_array($authorUser ?? null) ? $authorUser : null;
$isFollowingAuthor = (bool) ($isFollowingAuthor ?? false);
$isSavedTip = (bool) ($isSavedTip ?? false);

$tipId = (int) ($tip['id'] ?? 0);
$slug = (string) ($tip['slug'] ?? '');
$title = (string) ($tip['title'] ?? 'Mẹo vặt nấu ăn');
$excerpt = (string) ($tip['excerpt'] ?? '');
$content = (string) ($tip['content'] ?? '');
$image = (string) ($tip['cover_image'] ?? '');
$author = (string) (($tip['author_name'] ?? '') ?: 'Tác giả');
$authorId = (int) ($tip['user_id'] ?? 0);
$viewCount = (int) ($tip['view_count'] ?? 0);
$categoryLabel = (string) ($tip['category_name'] ?? '');

$tipPath = '/tips' . ($slug !== '' ? '/' . rawurlencode($slug) : '');
$tipRedirectPath = $tipPath . '#tip-comments-section';

$tipNotice = (string) ($_GET['notice'] ?? '');
$tipNoticeText = match ($tipNotice) {
    'tip_reported' => 'Đã gửi báo cáo mẹo vặt. Cảm ơn bạn đã phản hồi.',
    'tip_reported_exists' => 'Bạn đã báo cáo mẹo vặt này trước đó.',
    'tip_saved' => 'Đã lưu mẹo vặt.',
    'tip_unsaved' => 'Đã bỏ lưu mẹo vặt.',
    default => '',
};
?>

<div class="w-full">
    <div class="mx-auto max-w-7xl px-2 py-4 sm:px-4">
        <?php
        $breadcrumbItems = [
            ['label' => 'Mẹo vặt', 'url' => URLROOT . '/tips'],
            ['label' => $title],
        ];
        require APPROOT . '/app/views/tips/partials/breadcrumb.php';

        $heroImage = $image;
        $heroTitle = $title;
        $heroCategory = $categoryLabel !== '' ? $categoryLabel : 'Mẹo nấu ăn';
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
                    <?= nl2br(htmlspecialchars($content !== '' ? $content : 'Nội dung mẹo vặt đang được cập nhật.', ENT_QUOTES, 'UTF-8')); ?>
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
        $commentsTitle = 'Bình luận cộng đồng';
        $contentType = 'tip';
        $contentId = $tipId;
        $redirectTo = $tipRedirectPath;
        $showCount = false;
        $allowReply = true;
        $maxReplyDepth = 1;
        $emptyText = 'Chưa có bình luận nào cho mẹo vặt này.';
        $formPlaceholder = 'Viết bình luận của bạn...';
        require APPROOT . '/app/views/partials/shared/content_comments.php';
        ?>
    </div>
</div>
<?php
$reportModalId = 'tip-report-modal';
$reportModalTitle = 'Báo cáo mẹo vặt';
$reportModalAction = URLROOT . '/tips/' . $tipId . '/report';
$reportModalReasonField = 'reason';
$reportModalDetailsField = 'details';
$reportModalSuccessToast = 'Đã gửi báo cáo mẹo vặt.';
$reportModalErrorToast = 'Không thể gửi báo cáo mẹo vặt.';
$reportModalHiddenFields = ['redirect_to' => $tipPath];
require APPROOT . '/app/views/partials/shared/content_report_modal.php';
?>
