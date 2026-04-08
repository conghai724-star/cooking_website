<?php
$ingredient = is_array($ingredient ?? null) ? $ingredient : [];
$nutrition = is_array($nutrition ?? null) ? $nutrition : [];
$status = (string) ($ingredient['status'] ?? 'approved');
$statusClass = $status === 'approved'
    ? 'bg-emerald-100 text-emerald-700'
    : ($status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-yellow-100 text-yellow-700');
$statusLabel = $status === 'approved'
    ? 'A�Ă£ duyA�»‡t'
    : ($status === 'rejected' ? 'TA�»« chA�»‘i' : 'ChA�» duyA�»‡t');
$image = (string) ($ingredient['image'] ?? '');
$imageUrl = '';
if ($image !== '') {
    $imageUrl = preg_match('/^https?:\/\//i', $image) ? $image : URLROOT . '/uploads/' . $image;
}
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars((string) ($ingredient['name'] ?? 'Chi tiA�º¿t nguyĂªn liA�»‡u'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="text-sm text-slate-500">Danh mA�»¥c: <?= htmlspecialchars((string) ($ingredient['category_name'] ?? 'ChA�°a phĂ¢n loA�º¡i'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a class="text-sm font-semibold text-slate-500 hover:text-slate-900" href="<?= URLROOT; ?>/admin/ingredients">Quay lA�º¡i</a>
    </div>

    <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass; ?>"><?= $statusLabel; ?></span>
            <?php if ($status !== 'approved'): ?>
                <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) ($ingredient['id'] ?? 0); ?>/approve">
                    <?= csrf_field(); ?>
                    <button class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" type="submit">DuyA�»‡t</button>
                </form>
                <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) ($ingredient['id'] ?? 0); ?>/reject" onsubmit="return confirm('TA�»« chA�»‘i nguyĂªn liA�»‡u nĂ y?');">
                    <?= csrf_field(); ?>
                    <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">TA�»« chA�»‘i</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($imageUrl !== ''): ?>
            <img class="mt-6 h-64 w-full rounded-xl object-cover" src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Ingredient image">
        <?php endif; ?>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">MĂ´ tA�º£</p>
                <p class="text-sm text-slate-700"><?= nl2br(htmlspecialchars((string) ($ingredient['description'] ?? 'Đang cập nhật.'), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">CĂ´ng dA�»¥ng</p>
                <p class="text-sm text-slate-700"><?= nl2br(htmlspecialchars((string) ($ingredient['usage'] ?? 'Đang cập nhật.'), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">CĂ¡ch sA�¡ chA�º¿</p>
                <p class="text-sm text-slate-700"><?= nl2br(htmlspecialchars((string) ($ingredient['preparation'] ?? 'Đang cập nhật.'), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">CĂ¡ch bA�º£o quA�º£n</p>
                <p class="text-sm text-slate-700"><?= nl2br(htmlspecialchars((string) ($ingredient['storage'] ?? 'Đang cập nhật.'), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">Dinh dA�°A�»¡ng (100g)</h3>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                Calories: <strong><?= htmlspecialchars((string) ($nutrition['calories'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                Protein: <strong><?= htmlspecialchars((string) ($nutrition['protein'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                Fat: <strong><?= htmlspecialchars((string) ($nutrition['fat'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                Carb: <strong><?= htmlspecialchars((string) ($nutrition['carb'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        </div>
    </div>
</div>

