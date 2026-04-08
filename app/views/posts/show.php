<?php
$post = is_array($post ?? null) ? $post : [];
$comments = is_array($comments ?? null) ? $comments : [];

$postId = (int) ($post['id'] ?? 0);
$title = (string) ($post['title'] ?? 'Bai viet');
$content = (string) ($post['content'] ?? '');
$image = trim((string) ($post['image'] ?? ''));
if ($image !== '' && !preg_match('/^https?:\/\//i', $image)) {
    $image = URLROOT . '/uploads/' . $image;
}
$notice = (string) ($_GET['notice'] ?? '');
$noticeText = match ($notice) {
    'post_reported' => 'Da gui bao cao bai viet. Cam on ban da phan hoi.',
    'post_reported_exists' => 'Ban da bao cao bai viet nay truoc do.',
    'post_report_invalid' => 'Ban khong the tu bao cao bai viet cua chinh minh.',
    'post_report_failed' => 'Khong the gui bao cao bai viet luc nay.',
    default => '',
};
?>

<div class="w-full">
    <div class="mx-auto max-w-4xl">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 md:p-8">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <span><?= htmlspecialchars((string) ($post['author_name'] ?? 'An danh'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span>•</span>
                    <span><?= htmlspecialchars((string) substr((string) ($post['created_at'] ?? ''), 0, 16), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <?php if (is_logged_in() && (int) current_user_id() === (int) ($post['user_id'] ?? 0)): ?>
                        <a href="<?= URLROOT; ?>/posts/<?= $postId; ?>/edit" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">Sua</a>
                        <form method="post" action="<?= URLROOT; ?>/posts/<?= $postId; ?>/delete" data-confirm="Xoa bai viet nay?">
                            <?= csrf_field(); ?>
                            <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700">Xoa</button>
                        </form>
                    <?php elseif (is_logged_in()): ?>
                        <button type="button" class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700" data-modal-open="#post-report-modal">Bao cao</button>
                    <?php endif; ?>
                </div>
            </div>

            <h1 class="text-3xl font-black leading-tight text-slate-900 md:text-4xl"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>

            <?php if ($noticeText !== ''): ?>
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                    <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($image !== ''): ?>
                <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="post-image" class="mt-5 max-h-[480px] w-full rounded-xl object-cover">
            <?php endif; ?>

            <div class="prose mt-5 max-w-none text-slate-700">
                <p class="whitespace-pre-line leading-7"><?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </article>

        <?php
        $commentsRootId = 'post-comments-section';
        $commentsTitle = 'Tra loi va thao luan';
        $contentType = 'post';
        $contentId = $postId;
        $redirectTo = '/posts/' . $postId . '#post-comments-section';
        $allowReply = true;
        $maxReplyDepth = 1;
        $showCount = true;
        $emptyText = 'Chưa có bình luận nào cho bài viết này.';
        $formPlaceholder = 'Viet cau tra loi cua ban...';
        require APPROOT . '/app/views/partials/shared/content_comments.php';
        ?>
    </div>
</div>

<?php
$reportModalId = 'post-report-modal';
$reportModalTitle = 'Bao cao bai viet';
$reportModalAction = URLROOT . '/posts/' . $postId . '/report';
$reportModalReasonField = 'reason';
$reportModalDetailsField = 'details';
$reportModalSuccessToast = 'Da gui bao cao bai viet.';
$reportModalErrorToast = 'Khong the gui bao cao bai viet luc nay.';
$reportModalHiddenFields = [];
require APPROOT . '/app/views/partials/shared/content_report_modal.php';
?>


