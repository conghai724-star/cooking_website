<?php
$lists = is_array($lists ?? null) ? $lists : [];
$counts = is_array($counts ?? null) ? $counts : [];
$active = (string) ($active_group ?? 'all');
if (!in_array($active, ['all', 'published', 'completed', 'pending', 'saved', 'draft'], true)) {
    $active = 'all';
}

$perPage = 6;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

$labelClass = static function (string $label): string {
    return match ($label) {
        'Đã đăng' => 'bg-emerald-100 text-emerald-700',
        'Hoàn thiện' => 'bg-sky-100 text-sky-700',
        'Nháp' => 'bg-slate-200 text-slate-700',
        'Chờ duyệt' => 'bg-amber-100 text-amber-700',
        'Từ chối' => 'bg-rose-100 text-rose-700',
        'Đã lưu' => 'bg-purple-100 text-purple-700',
        'Chờ đăng' => 'bg-amber-100 text-amber-700',
        default => 'bg-slate-100 text-slate-600',
    };
};

$buildPageUrl = static function (string $group, int $page): string {
    $page = max(1, $page);
    return URLROOT . '/recipes/my?group=' . urlencode($group) . '&page=' . $page;
};
$notice = trim((string) ($_GET['notice'] ?? ''));
?>

<div class="w-full">
    <div class="mx-auto max-w-6xl py-4">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-900">Công thức của tôi</h1>
                <p class="mt-1 text-slate-500">Quản lý công thức đã đăng, hoàn thiện, nháp và các công thức bạn đã lưu.</p>
            </div>
            <a class="rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-primary/90" href="<?= URLROOT; ?>/recipes/create">Đăng công thức</a>
        </div>

        <?php if ($notice !== ''): ?>
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="mb-6 border-b border-slate-200">
            <div class="flex flex-wrap gap-6 text-sm font-semibold">
                <a class="border-b-2 pb-3 <?= $active === 'all' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-primary'; ?>" href="<?= URLROOT; ?>/recipes/my?group=all">Tất cả (<?= (int) ($counts['all'] ?? 0); ?>)</a>
                <a class="border-b-2 pb-3 <?= $active === 'published' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-primary'; ?>" href="<?= URLROOT; ?>/recipes/my?group=published">Đã đăng (<?= (int) ($counts['published'] ?? 0); ?>)</a>
                <a class="border-b-2 pb-3 <?= $active === 'completed' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-primary'; ?>" href="<?= URLROOT; ?>/recipes/my?group=completed">Hoàn thiện (<?= (int) ($counts['completed'] ?? 0); ?>)</a>
                <a class="border-b-2 pb-3 <?= $active === 'pending' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-primary'; ?>" href="<?= URLROOT; ?>/recipes/my?group=pending">Chờ đăng (<?= (int) ($counts['pending'] ?? 0); ?>)</a>
                <a class="border-b-2 pb-3 <?= $active === 'saved' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-primary'; ?>" href="<?= URLROOT; ?>/recipes/my?group=saved">Đã lưu (<?= (int) ($counts['saved'] ?? 0); ?>)</a>
                <a class="border-b-2 pb-3 <?= $active === 'draft' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-primary'; ?>" href="<?= URLROOT; ?>/recipes/my?group=draft">Công thức nháp (<?= (int) ($counts['draft'] ?? 0); ?>)</a>
            </div>
        </div>

        <?php
        $items = $lists[$active] ?? [];
        $totalItems = count($items);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
        $offset = ($currentPage - 1) * $perPage;
        $pagedItems = array_slice($items, $offset, $perPage);
        ?>
        <?php if (empty($pagedItems)): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-5 text-slate-500">Chưa có công thức nào trong mục này.</div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($pagedItems as $recipe): ?>
                    <?php
                    $recipeId = (int) ($recipe['id'] ?? 0);
                    $title = (string) ($recipe['title'] ?? '');
                    $desc = (string) ($recipe['description'] ?? '');
                    $image = (string) ($recipe['image'] ?? '');
                    $itemType = (string) ($recipe['item_type'] ?? 'own');
                    $label = (string) ($recipe['label'] ?? '');
                    $isOwn = $itemType === 'own';
                    $statusValue = (string) ($recipe['status'] ?? '');
                    $stateValue = (string) ($recipe['user_state'] ?? '');
                    $isRejected = in_array($statusValue, ['rejected', 'denied'], true) || $stateValue === 'rejected';
                    ?>
                    <article class="group flex flex-col overflow-hidden rounded-[12px] bg-white shadow-sm ring-1 ring-primary/5 transition-all hover:-translate-y-1 hover:shadow-xl">
                        <div class="relative aspect-video w-full overflow-hidden">
                            <?php if ($image !== ''): ?>
                                <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110" style="background-image: url('<?= URLROOT; ?>/uploads/<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>');"></div>
                            <?php else: ?>
                                <div class="absolute inset-0 bg-gradient-to-br from-amber-200 to-orange-300 transition-transform duration-500 group-hover:scale-110"></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <h3 class="text-lg font-bold leading-tight group-hover:text-primary">
                                    <a href="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>">
                                        <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h3>
                                <?php if ($label !== ''): ?>
                                    <span class="rounded-full px-2 py-1 text-[11px] font-semibold <?= $labelClass($label); ?>">
                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="mb-4 text-xs text-slate-500 line-clamp-2">
                                <?= htmlspecialchars(mb_substr($desc, 0, 120), ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                                <span class="text-xs text-slate-500">ID #<?= $recipeId; ?></span>

                                <div class="flex flex-wrap items-center gap-2">
                                    <a class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>">Xem</a>

                                    <?php if ($active === 'published' && $isOwn): ?>
                                        <a class="rounded-lg border border-primary px-3 py-1 text-xs font-semibold text-primary hover:bg-primary hover:text-white" href="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/edit">Sửa</a>
                                        <form method="post" action="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/move-to-draft" onsubmit="return confirm('Bạn chắc chắn muốn chuyển công thức này về nháp?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50" type="submit">Xóa</button>
                                        </form>
                                    <?php elseif ($active === 'pending' && $isOwn): ?>
                                        <?php if ($isRejected): ?>
                                            <form method="post" action="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/resubmit" onsubmit="return confirm('Gửi duyệt lại công thức này?');">
                                                <?= csrf_field(); ?>
                                                <button class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700" type="submit">Gửi duyệt lại</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-500">Đang chờ duyệt</span>
                                        <?php endif; ?>
                                        <form method="post" action="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/move-to-draft" onsubmit="return confirm('Hủy gửi duyệt và chuyển công thức này về nháp?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50" type="submit">Xóa</button>
                                        </form>
                                    <?php elseif ($active === 'completed' && $isOwn): ?>
                                        <a class="rounded-lg border border-primary px-3 py-1 text-xs font-semibold text-primary hover:bg-primary hover:text-white" href="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/edit">Sửa</a>
                                        <form method="post" action="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/submit" onsubmit="return confirm('Đăng công thức này để chờ duyệt?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-lg border border-emerald-300 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50" type="submit">Đăng chờ duyệt</button>
                                        </form>
                                        <form method="post" action="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/move-to-draft" onsubmit="return confirm('Bạn chắc chắn muốn chuyển công thức này về nháp?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50" type="submit">Xóa</button>
                                        </form>
                                    <?php elseif ($active === 'draft' && $isOwn): ?>
                                        <a class="rounded-lg border border-primary px-3 py-1 text-xs font-semibold text-primary hover:bg-primary hover:text-white" href="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/edit">Sửa</a>
                                        <form method="post" action="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/submit" onsubmit="return confirm('Đăng công thức này để chờ duyệt?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-lg border border-emerald-300 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50" type="submit">Đăng chờ duyệt</button>
                                        </form>
                                        <form method="post" action="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/delete" onsubmit="return confirm('Bạn chắc chắn muốn xóa công thức nháp này?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50" type="submit">Xóa</button>
                                        </form>
                                    <?php elseif ($active === 'saved'): ?>
                                        <span class="text-xs text-slate-500">Đã lưu từ người khác</span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-500">Xem chi tiết theo nhóm</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="mt-10 flex items-center justify-center">
                <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <a class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 <?= $currentPage === 1 ? 'pointer-events-none opacity-40' : ''; ?>" href="<?= $buildPageUrl($active, $currentPage - 1); ?>">
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                    </a>

                    <?php
                    $pages = [];
                    if ($totalPages <= 7) {
                        $pages = range(1, $totalPages);
                    } else {
                        $pages = [1];
                        if ($currentPage > 3) {
                            $pages[] = '...';
                        }
                        $start = max(2, $currentPage - 1);
                        $end = min($totalPages - 1, $currentPage + 1);
                        for ($i = $start; $i <= $end; $i++) {
                            $pages[] = $i;
                        }
                        if ($currentPage < $totalPages - 2) {
                            $pages[] = '...';
                        }
                        $pages[] = $totalPages;
                    }
                    ?>

                    <?php foreach ($pages as $page): ?>
                        <?php if ($page === '...'): ?>
                            <span class="px-2 text-sm font-semibold text-slate-400">...</span>
                        <?php else: ?>
                            <a class="flex h-9 min-w-[36px] items-center justify-center rounded-full px-3 text-sm font-semibold <?= $page === $currentPage ? 'bg-primary text-white shadow' : 'text-slate-700 hover:bg-slate-100'; ?>" href="<?= $buildPageUrl($active, (int) $page); ?>">
                                <?= (int) $page; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <a class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-40' : ''; ?>" href="<?= $buildPageUrl($active, $currentPage + 1); ?>">
                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
