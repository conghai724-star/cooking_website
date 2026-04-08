<?php
$tips = is_array($tips ?? null) ? $tips : [];
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$keyword = trim((string) ($keyword ?? ''));
$noticeText = trim((string) ($_GET['notice'] ?? ''));
$buildPageUrl = static function (int $targetPage) use ($keyword): string {
    $url = URLROOT . '/tips?page=' . $targetPage;
    if ($keyword !== '') {
        $url .= '&q=' . rawurlencode($keyword);
    }
    return $url;
};
?>

<div class="w-full">
    <div class="mx-auto flex max-w-[1000px] flex-1 flex-col">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-4xl font-black leading-tight tracking-tight text-slate-900 md:text-5xl">Mẹo vặt nấu ăn</h1>
            <?php if (is_logged_in()): ?>
                <div class="flex items-center gap-2">
                    <a class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600" href="<?= URLROOT; ?>/tips/my">Mẹo vặt của tôi</a>
                    <a class="rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm" href="<?= URLROOT; ?>/tips/create">Thêm mẹo vặt</a>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($noticeText !== ''): ?>
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="get" action="<?= URLROOT; ?>/tips" class="mb-6 flex flex-wrap items-center gap-3">
            <input
                type="text"
                name="q"
                value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Tìm mẹo vặt..."
                class="w-full max-w-md rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30"
            >
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Tìm kiếm</button>
            <?php if ($keyword !== ''): ?>
                <a href="<?= URLROOT; ?>/tips" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Xóa lọc</a>
            <?php endif; ?>
        </form>

        <?php if (empty($tips)): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">Chưa có mẹo vặt được duyệt.</div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach ($tips as $tip): ?>
                    <?php
                    $cover = (string) ($tip['cover_image'] ?? '');
                    if ($cover !== '' && !preg_match('/^https?:\/\//i', $cover)) {
                        $cover = URLROOT . '/uploads/' . $cover;
                    }
                    if ($cover === '') {
                        $cover = 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=80';
                    }
                    ?>
                    <article class="@container">
                        <div class="flex flex-col items-stretch justify-start overflow-hidden rounded-xl border border-primary/5 bg-white shadow-lg transition-transform hover:scale-[1.005] @3xl:flex-row @3xl:items-stretch">
                            <div class="aspect-video w-full bg-cover bg-center bg-no-repeat @3xl:aspect-auto @3xl:w-1/2" style='background-image: url("<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8'); ?>");'></div>

                            <div class="flex w-full flex-col justify-between p-6 md:p-10 @3xl:w-1/2">
                                <div>
                                    <div class="mb-4 flex items-center gap-2">
                                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-primary">Kỹ thuật</span>
                                        <span class="text-xs text-slate-400">≈ 5 phút đọc</span>
                                    </div>
                                    <h2 class="mb-4 text-2xl font-bold leading-tight text-slate-900 md:text-3xl">
                                        <?= htmlspecialchars((string) ($tip['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </h2>
                                    <p class="mb-8 text-base font-normal leading-relaxed text-slate-600 md:text-lg">
                                        <?= htmlspecialchars((string) ($tip['excerpt'] ?? 'Một mẹo vặt hữu ích cho gian bếp của bạn.'), ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-primary/5 pt-6">
                                        <div class="flex items-center gap-3">
                                            <div class="size-12 rounded-full bg-cover bg-center ring-2 ring-primary/20" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCLYFpIw51h0h8kulmRseDdMcqlG9jEo3wTTp0Q_4GHhZ7GPRBihFvkAZ4da3n9bPeUMXc0A28hLhia2aT4NAclsuxQCC8diLGC--lbNtHmWsnc77WbBUnsc67Ik733wNZKSEnRmktsAPIx4wk9-Or18jg1JZoM6zBBFjS7ok1th-QxMdMtS7p9_ru4FGDJy0gkva3EtKdNTHlMV3BKZJuWTJxywLVpNn1FbgCW00Rgks3vpQEROHFdGkHWQDQIVDq2392p-eYpD6nB");'></div>
                                            <div class="flex flex-col">
                                                <p class="mb-1 text-sm font-bold leading-none text-slate-900"><?= htmlspecialchars((string) ($tip['author_name'] ?? 'Người đăng'), ENT_QUOTES, 'UTF-8'); ?></p>
                                                <p class="text-xs font-medium leading-none text-primary">Chia sẻ mẹo vặt</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <button class="group flex items-center gap-1.5" type="button">
                                                <span class="material-symbols-outlined text-primary transition-all group-hover:fill-current">favorite</span>
                                                <span class="text-sm font-bold text-slate-600">124</span>
                                            </button>
                                            <button class="group flex items-center gap-1.5" type="button">
                                                <span class="material-symbols-outlined text-primary transition-all group-hover:fill-current">chat_bubble</span>
                                                <span class="text-sm font-bold text-slate-600">18</span>
                                            </button>
                                        </div>
                                    </div>

                                    <a class="flex h-14 w-full items-center justify-center gap-2 overflow-hidden rounded-xl bg-primary px-8 text-base font-bold text-white shadow-md shadow-primary/20 transition-all hover:bg-primary/90" href="<?= URLROOT; ?>/tips/<?= htmlspecialchars((string) ($tip['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <span>Đọc hướng dẫn</span>
                                        <span class="material-symbols-outlined">arrow_forward</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-center py-6">
                <div class="flex items-center rounded-full border border-primary/10 bg-white p-1 shadow-sm">
                    <a class="flex size-10 items-center justify-center rounded-full text-slate-700 transition-colors hover:bg-primary/10 <?= $page <= 1 ? 'pointer-events-none opacity-40' : ''; ?>" href="<?= $buildPageUrl(max(1, $page - 1)); ?>">
                        <span class="material-symbols-outlined text-xl">chevron_left</span>
                    </a>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    if ($start > 1) {
                        $end = min($totalPages, $start + 4);
                    } elseif ($end < $totalPages) {
                        $start = max(1, $end - 4);
                    }
                    if ($start > 1): ?>
                        <a class="flex size-10 items-center justify-center rounded-full text-sm font-semibold text-slate-700 transition-colors hover:bg-primary/10" href="<?= $buildPageUrl(1); ?>">1</a>
                        <?php if ($start > 2): ?>
                            <span class="flex size-10 items-center justify-center text-sm font-semibold text-slate-400">…</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="flex size-10 items-center justify-center rounded-full bg-primary text-sm font-bold text-white shadow-lg shadow-primary/30"><?= $i; ?></span>
                        <?php else: ?>
                            <a class="flex size-10 items-center justify-center rounded-full text-sm font-semibold text-slate-700 transition-colors hover:bg-primary/10" href="<?= $buildPageUrl($i); ?>"><?= $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages): ?>
                        <?php if ($end < $totalPages - 1): ?>
                            <span class="flex size-10 items-center justify-center text-sm font-semibold text-slate-400">…</span>
                        <?php endif; ?>
                        <a class="flex size-10 items-center justify-center rounded-full text-sm font-semibold text-slate-700 transition-colors hover:bg-primary/10" href="<?= $buildPageUrl($totalPages); ?>"><?= $totalPages; ?></a>
                    <?php endif; ?>

                    <a class="flex size-10 items-center justify-center rounded-full text-slate-700 transition-colors hover:bg-primary/10 <?= $page >= $totalPages ? 'pointer-events-none opacity-40' : ''; ?>" href="<?= $buildPageUrl(min($totalPages, $page + 1)); ?>">
                        <span class="material-symbols-outlined text-xl">chevron_right</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-12 flex flex-col items-center justify-between gap-6 border-t border-primary/5 py-10 md:flex-row">
            <p class="text-sm text-slate-400">© 2024 CookMaster. All rights reserved.</p>
            <div class="flex gap-6">
                <a class="text-slate-400 transition-colors hover:text-primary" href="#"><span class="material-symbols-outlined">rss_feed</span></a>
                <a class="text-slate-400 transition-colors hover:text-primary" href="#"><span class="material-symbols-outlined">share</span></a>
                <a class="text-slate-400 transition-colors hover:text-primary" href="#"><span class="material-symbols-outlined">mail</span></a>
            </div>
        </div>
    </div>
</div>

