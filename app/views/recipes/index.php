<?php
$recipes = is_array($recipes ?? null) ? $recipes : [];
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$keyword = trim((string) ($keyword ?? ''));
$difficulty = trim((string) ($difficulty ?? ''));
$maxTime = max(0, (int) ($max_time ?? 0));
$healthy = (bool) ($healthy ?? false);
$buildPageUrl = static function (int $targetPage) use ($keyword, $difficulty, $maxTime, $healthy): string {
    $params = ['page' => $targetPage];
    if ($keyword !== '') {
        $params['q'] = $keyword;
    }
    if ($difficulty !== '') {
        $params['difficulty'] = $difficulty;
    }
    if ($maxTime > 0) {
        $params['max_time'] = $maxTime;
    }
    if ($healthy) {
        $params['healthy'] = '1';
    }
    return URLROOT . '/recipes?' . http_build_query($params);
};

$difficultyLabels = [
    'easy' => 'Dễ',
    'medium' => 'Trung bình',
    'hard' => 'Khó',
];
?>

<div class="w-full">
    <div class="mx-auto max-w-6xl py-4">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-900">Khám phá công thức</h1>
                <p class="mt-1 text-slate-500">Danh sách công thức với giao diện đồng bộ như mục Công thức của tôi.</p>
            </div>
            <?php if (is_logged_in()): ?>
                <a class="rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-primary/90" href="<?= URLROOT; ?>/recipes/create">Đăng công thức</a>
            <?php endif; ?>
        </div>

        <form method="get" action="<?= URLROOT; ?>/recipes" class="mb-6 flex flex-wrap items-center gap-3">
            <input
                type="text"
                name="q"
                value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Tìm công thức theo tên hoặc mô tả..."
                class="w-full max-w-md rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30"
            >
            <input type="hidden" name="difficulty" value="<?= htmlspecialchars($difficulty, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="max_time" value="<?= $maxTime; ?>">
            <?php if ($healthy): ?>
                <input type="hidden" name="healthy" value="1">
            <?php endif; ?>
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Tìm kiếm</button>
            <?php if ($keyword !== '' || $difficulty !== '' || $maxTime > 0 || $healthy): ?>
                <a href="<?= URLROOT; ?>/recipes" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Xóa lọc</a>
            <?php endif; ?>
        </form>

        <?php if (!empty($recipes)): ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($recipes as $recipe): ?>
                    <article class="group flex flex-col overflow-hidden rounded-[12px] bg-white shadow-sm ring-1 ring-primary/5 transition-all hover:-translate-y-1 hover:shadow-xl">
                        <div class="relative aspect-video w-full overflow-hidden">
                            <?php if (!empty($recipe['image'])): ?>
                                <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110" style="background-image: url('<?= URLROOT; ?>/uploads/<?= htmlspecialchars((string) $recipe['image'], ENT_QUOTES, 'UTF-8'); ?>');"></div>
                            <?php else: ?>
                                <div class="absolute inset-0 bg-gradient-to-br from-amber-200 to-orange-300 transition-transform duration-500 group-hover:scale-110"></div>
                                <span class="absolute left-4 top-4 z-10 rounded-full bg-white/90 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-primary">
                                    <?= htmlspecialchars($difficultyLabels[strtolower((string) ($recipe['difficulty'] ?? ''))] ?? (string) ($recipe['difficulty'] ?? 'Dễ'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <h3 class="mb-1 text-lg font-bold leading-tight group-hover:text-primary">
                                <a href="<?= URLROOT; ?>/recipes/<?= (int) $recipe['id']; ?>">
                                    <?= htmlspecialchars((string) $recipe['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </h3>
                            <p class="mb-4 text-xs text-slate-500 line-clamp-2">
                                <?= htmlspecialchars(substr((string) ($recipe['description'] ?? ''), 0, 120), ENT_QUOTES, 'UTF-8'); ?>...
                            </p>
                            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3">
                                <span class="text-xs text-slate-500">ID #<?= (int) $recipe['id']; ?></span>
                                <a class="rounded-lg border border-primary px-3 py-1 text-xs font-semibold text-primary hover:bg-primary hover:text-white" href="<?= URLROOT; ?>/recipes/<?= (int) $recipe['id']; ?>">Xem chi tiết</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="rounded-xl border border-slate-200 bg-white p-5 text-slate-500">Chưa có công thức nào.</div>
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
    </div>
</div>
