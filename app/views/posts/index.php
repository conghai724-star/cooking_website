<?php
$posts = is_array($posts ?? null) ? $posts : [];
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$keyword = trim((string) ($keyword ?? ''));
$buildPageUrl = static function (int $targetPage) use ($keyword): string {
    $url = URLROOT . '/posts?page=' . $targetPage;
    if ($keyword !== '') {
        $url .= '&q=' . rawurlencode($keyword);
    }
    return $url;
};
?>

<div class="w-full">
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-4xl font-black text-slate-900 md:text-5xl">Cong dong hoi dap</h1>
                <p class="mt-2 text-sm text-slate-500">Dang cau hoi, chia se kinh nghiem bep va nhan giai dap tu moi nguoi.</p>
            </div>
            <?php if (is_logged_in()): ?>
                <a href="<?= URLROOT; ?>/posts/create" class="rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white">Dang cau hoi</a>
            <?php endif; ?>
        </div>

        <form method="get" action="<?= URLROOT; ?>/posts" class="flex flex-wrap items-center gap-3">
            <input
                type="text"
                name="q"
                value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Tim cau hoi..."
                class="w-full max-w-md rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary"
            >
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Tim kiem</button>
            <?php if ($keyword !== ''): ?>
                <a href="<?= URLROOT; ?>/posts" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Xoa loc</a>
            <?php endif; ?>
        </form>

        <?php if (empty($posts)): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500">Chưa có bài đăng nào.</div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($posts as $item): ?>
                    <?php
                    $postId = (int) ($item['id'] ?? 0);
                    $title = (string) ($item['title'] ?? 'Bai dang');
                    $content = trim((string) ($item['content'] ?? ''));
                    $excerpt = function_exists('mb_substr') ? mb_substr($content, 0, 180, 'UTF-8') : substr($content, 0, 180);
                    $contentLen = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
                    if ($contentLen > 180) {
                        $excerpt .= '...';
                    }
                    ?>
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
                            <span><?= htmlspecialchars((string) ($item['author_name'] ?? 'An danh'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>•</span>
                            <span><?= htmlspecialchars((string) substr((string) ($item['created_at'] ?? ''), 0, 16), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>•</span>
                            <span><?= (int) ($item['comment_count'] ?? 0); ?> binh luan</span>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">
                            <a href="<?= URLROOT; ?>/posts/<?= $postId; ?>" class="hover:text-primary">
                                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-700"><?= nl2br(htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8')); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-center gap-2 pt-2">
                <a href="<?= $buildPageUrl(max(1, $page - 1)); ?>" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : ''; ?>">Truoc</a>
                <span class="text-sm text-slate-500">Trang <?= $page; ?>/<?= $totalPages; ?></span>
                <a href="<?= $buildPageUrl(min($totalPages, $page + 1)); ?>" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm <?= $page >= $totalPages ? 'pointer-events-none opacity-40' : ''; ?>">Sau</a>
            </div>
        <?php endif; ?>
    </div>
</div>


