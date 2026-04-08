<?php
$recipe = is_array($recipe ?? null) ? $recipe : [];
$ingredients = is_array($ingredients ?? null) ? $ingredients : [];
$steps = is_array($steps ?? null) ? $steps : [];
$status = (string) ($recipe['status'] ?? 'approved');
$statusClass = $status === 'approved'
    ? 'bg-emerald-100 text-emerald-700'
    : ($status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-yellow-100 text-yellow-700');
$statusLabel = $status === 'approved'
    ? 'Đã duyệt'
    : ($status === 'rejected' ? 'Từ chối' : 'Chờ duyệt');
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars((string) ($recipe['title'] ?? 'Chi tiết công thức'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="text-sm text-slate-500">Tác giả: <?= htmlspecialchars((string) ($recipe['author_name'] ?? 'Không rõ'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a class="text-sm font-semibold text-slate-500 hover:text-slate-900" href="<?= URLROOT; ?>/admin/recipes">Quay lại</a>
    </div>

    <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass; ?>"><?= $statusLabel; ?></span>
            <?php if ($status !== 'approved'): ?>
                <form method="post" action="<?= URLROOT; ?>/admin/recipes/<?= (int) ($recipe['id'] ?? 0); ?>/approve">
                    <?= csrf_field(); ?>
                    <button class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" type="submit">Duyệt</button>
                </form>
                <form method="post" action="<?= URLROOT; ?>/admin/recipes/<?= (int) ($recipe['id'] ?? 0); ?>/reject" onsubmit="return confirm('Từ chối công thức này?');">
                    <?= csrf_field(); ?>
                    <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">Từ chối</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Danh mục</p>
                <p class="text-sm text-slate-700"><?= htmlspecialchars((string) ($recipe['category_name'] ?? 'Chưa phân loại'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Độ khó</p>
                <p class="text-sm text-slate-700"><?= htmlspecialchars((string) ($recipe['difficulty'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Thời gian nấu</p>
                <p class="text-sm text-slate-700"><?= htmlspecialchars((string) ($recipe['cooking_time'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?> phút</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Ngày tạo</p>
                <p class="text-sm text-slate-700"><?= htmlspecialchars((string) ($recipe['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <?php if (!empty($recipe['image'])): ?>
            <img class="mt-6 h-64 w-full rounded-xl object-cover" src="<?= URLROOT; ?>/uploads/<?= htmlspecialchars((string) $recipe['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Recipe image">
        <?php endif; ?>

        <div class="mt-6">
            <h3 class="text-lg font-semibold text-slate-900">Mô tả</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600"><?= nl2br(htmlspecialchars((string) ($recipe['description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Nguyên liệu</h3>
            <?php if (empty($ingredients)): ?>
                <p class="mt-3 text-sm text-slate-500">Chưa có nguyên liệu.</p>
            <?php else: ?>
                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                    <?php foreach ($ingredients as $item): ?>
                        <li class="flex items-center justify-between">
                            <span><?= htmlspecialchars((string) ($item['ingredient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="text-slate-500"><?= htmlspecialchars((string) ($item['quantity'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars((string) ($item['unit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Các bước</h3>
            <?php if (empty($steps)): ?>
                <p class="mt-3 text-sm text-slate-500">Chưa có bước thực hiện.</p>
            <?php else: ?>
                <ol class="mt-3 space-y-3 text-sm text-slate-700">
                    <?php foreach ($steps as $step): ?>
                        <li>
                            <p class="font-semibold">Bước <?= (int) ($step['step_number'] ?? 0); ?></p>
                            <p class="text-slate-600"><?= nl2br(htmlspecialchars((string) ($step['content'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</div>
