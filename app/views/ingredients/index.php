<?php
$ingredients = is_array($ingredients ?? null) ? $ingredients : [];
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$keyword = trim((string) ($keyword ?? ''));
$noticeText = trim((string) ($_GET['notice'] ?? ''));
$buildPageUrl = static function (int $targetPage) use ($keyword): string {
    $url = URLROOT . '/ingredients?page=' . $targetPage;
    if ($keyword !== '') {
        $url .= '&q=' . rawurlencode($keyword);
    }
    return $url;
};
?>

<div class="flex w-full flex-col gap-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-4xl font-black tracking-tight text-slate-900">Nguyên liệu</h1>
            <p class="mt-3 text-lg text-slate-600">
                Khám phá nguyên liệu tươi ngon, giá vị và thực phẩm theo mùa để truyền cảm hứng nấu ăn mỗi ngày.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <form method="get" action="<?= URLROOT; ?>/ingredients" class="hidden w-full max-w-sm flex-col sm:flex">
                <div class="flex w-full items-stretch overflow-hidden rounded-lg border border-primary/20 bg-primary/5 transition-colors hover:bg-primary/10">
                    <div class="flex items-center justify-center pl-4 text-primary">
                        <span class="material-symbols-outlined text-xl">search</span>
                    </div>
                    <input class="form-input w-full border-none bg-transparent px-4 text-sm font-medium text-slate-900 placeholder:text-primary/60 focus:outline-0 focus:ring-0" type="text" name="q" placeholder="Tìm nguyên liệu..." value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </form>
            <?php if (is_logged_in()): ?>
                <div class="flex items-center gap-2">
                    <a class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600" href="<?= URLROOT; ?>/ingredients/my">Nguyên liệu của tôi</a>
                    <a class="inline-flex items-center justify-center rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm" href="<?= URLROOT; ?>/ingredients/create">Thêm nguyên liệu</a>
                </div>
            <?php endif; ?>
        </div>
    <?php if ($noticeText !== ''): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    </div>

    <div class="flex gap-3 overflow-x-auto pb-2">
        <button class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full bg-primary px-6 text-white shadow-md shadow-primary/20">
            <span class="text-sm font-bold">Tất cả</span>
        </button>
        <button class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full border border-primary/20 bg-white px-6 text-slate-700 transition-colors hover:bg-primary/10">
            <span class="material-symbols-outlined text-sm">flatware</span>
            <span class="text-sm font-semibold">Meat</span>
        </button>
        <button class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full border border-primary/20 bg-white px-6 text-slate-700 transition-colors hover:bg-primary/10">
            <span class="material-symbols-outlined text-sm">eco</span>
            <span class="text-sm font-semibold">Vegetables</span>
        </button>
        <button class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full border border-primary/20 bg-white px-6 text-slate-700 transition-colors hover:bg-primary/10">
            <span class="material-symbols-outlined text-sm">set_meal</span>
            <span class="text-sm font-semibold">Seafood</span>
        </button>
        <button class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full border border-primary/20 bg-white px-6 text-slate-700 transition-colors hover:bg-primary/10">
            <span class="material-symbols-outlined text-sm">psychology</span>
            <span class="text-sm font-semibold">Spices</span>
        </button>
        <button class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full border border-primary/20 bg-white px-6 text-slate-700 transition-colors hover:bg-primary/10">
            <span class="material-symbols-outlined text-sm">nutrition</span>
            <span class="text-sm font-semibold">Fruits</span>
        </button>
        <button class="flex h-10 shrink-0 items-center justify-center gap-x-2 rounded-full border border-primary/20 bg-white px-6 text-slate-700 transition-colors hover:bg-primary/10">
            <span class="material-symbols-outlined text-sm">egg</span>
            <span class="text-sm font-semibold">Dairy</span>
        </button>
    </div>

    <?php if (empty($ingredients)): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">Chua c� nguy�n li?u du?c duy?t.</div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($ingredients as $ingredient): ?>
                <?php
                $image = (string) ($ingredient['image'] ?? '');
                if ($image !== '' && !preg_match('/^https?:\/\//i', $image)) {
                    $image = URLROOT . '/uploads/' . $image;
                }
                if ($image === '') {
                    $image = 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80';
                }
                ?>
                <article class="group flex flex-col overflow-hidden rounded-xl border border-primary/5 bg-white shadow-sm transition-all duration-300 hover:shadow-xl">
                    <div class="relative w-full overflow-hidden" style="aspect-ratio: 4/3;">
                        <div class="h-full w-full bg-cover bg-center transition-transform duration-500 group-hover:scale-110" style="background-image: url('<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>');"></div>
                        <div class="absolute right-3 top-3">
                            <button class="flex size-8 items-center justify-center rounded-full bg-white/80 text-primary backdrop-blur-sm transition-colors hover:bg-primary hover:text-white" type="button">
                                <span class="material-symbols-outlined text-lg">favorite</span>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="mb-2 text-lg font-bold text-slate-900"><?= htmlspecialchars((string) ($ingredient['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="mb-6 flex-1 text-sm leading-relaxed text-slate-500"><?= htmlspecialchars((string) ($ingredient['description'] ?? 'M� t? dang du?c c?p nh?t.'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <a class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary/10 py-3 text-sm font-bold text-primary transition-all hover:bg-primary hover:text-white" href="<?= URLROOT; ?>/ingredients/<?= (int) ($ingredient['id'] ?? 0); ?>">
                            <span>Xem chi tiết</span>
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
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
</div>
