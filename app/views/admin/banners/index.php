<?php
$banner = is_array($banner ?? null) ? $banner : null;
$featuredIds = is_array($featuredIds ?? null) ? $featuredIds : [];
$recipeOfDay = is_array($recipeOfDay ?? null) ? $recipeOfDay : null;
$forDate = (string) ($forDate ?? date('Y-m-d'));
$recipes = is_array($recipes ?? null) ? $recipes : [];
$notice = (string) ($notice ?? '');
$bannerImageSrc = '';
if (!empty($banner['image_url'])) {
    $raw = (string) $banner['image_url'];
    $bannerImageSrc = (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://'))
        ? $raw
        : (URLROOT . '/' . ltrim($raw, '/'));
}

$noticeText = match ($notice) {
    'banner_saved' => 'ĐA� lưu banner trang chủ.',
    'featured_saved' => 'A�¿½A? c?p nh?t cA?ng th?c n?i b?t.',
    'today_saved' => 'A�¿½A? c?p nh?t cA?ng th?c hA?m nay.',
    'banner_save_failed' => 'LA�°u banner thA�º¥t bA�º¡i.',
    'featured_save_failed' => 'LA�°u cĂ´ng thA�»©c nA�»•i bA�º­t thA�º¥t bA�º¡i.',
    'today_save_failed' => 'LA�°u cĂ´ng thA�»©c hĂ´m nay thA�º¥t bA�º¡i.',
    default => '',
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">QuA�º£n lĂ½ banner vĂ  nA�»™i dung nA�»•i bA�º­t</h1>
        <p class="text-sm text-slate-500">CA�º­p nhA�º­t banner, ghim cĂ´ng thA�»©c vĂ  chA�»n A�€œCA�ng thức hĂ´m nayA�€.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="mb-4 font-semibold text-slate-800">Banner hiA�»‡n tA�º¡i</h3>
            <form method="post" action="<?= URLROOT; ?>/admin/banners/banner" enctype="multipart/form-data" class="space-y-3">
                <?= csrf_field(); ?>
                <input type="text" name="title" required maxlength="255" value="<?= htmlspecialchars((string) ($banner['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="TiĂªu A�‘A�» banner" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <textarea name="subtitle" rows="3" placeholder="MĂ´ tA�º£ ngA�º¯n" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= htmlspecialchars((string) ($banner['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                <input type="text" name="image_url" maxlength="255" value="<?= htmlspecialchars((string) ($banner['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="URL A�º£nh banner" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="text-xs text-slate-500">DĂ¡n link A�º£nh ngoĂ i (vĂ­ dA�»¥: <code>https://images.unsplash.com/...</code>).</p>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">HoA�º·c tA�º£i A�º£nh tA�»« thiA�º¿t bA�»‹</label>
                    <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,.gif" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                </div>
                <?php if ($bannerImageSrc !== ''): ?>
                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        <img src="<?= htmlspecialchars($bannerImageSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Banner preview" class="h-44 w-full object-cover">
                    </div>
                <?php endif; ?>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <input type="text" name="cta_text" maxlength="80" value="<?= htmlspecialchars((string) ($banner['cta_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="NĂ„â€Ă‚Âºt CTA" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="text" name="cta_url" maxlength="255" value="<?= htmlspecialchars((string) ($banner['cta_url'] ?? '/recipes'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Link CTA (vd: /recipes)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked>
                    KĂ­ch hoA�º¡t ngay
                </label>
                <div>
                    <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">LÄ‚â€ Ă‚Â°u banner</button>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="mb-4 font-semibold text-slate-800">CA�ng thức nA�»•i bA�º­t</h3>
            <form method="post" action="<?= URLROOT; ?>/admin/banners/featured" class="space-y-3">
                <?= csrf_field(); ?>
                <input type="text"
                       name="featured_recipe_ids"
                       value="<?= htmlspecialchars(implode(',', array_map('intval', $featuredIds)), ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="NhA�º­p ID cĂ´ng thA�»©c, cĂ¡ch nhau bA�»Ÿi dA�º¥u phA�º©y. VD: 10,12,25"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="text-xs text-slate-500">Danh sĂ¡ch hiA�»‡n tA�º¡i: <?= htmlspecialchars(implode(', ', array_map('intval', $featuredIds)), ENT_QUOTES, 'UTF-8'); ?></p>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">LA�°u cĂ´ng thA�»©c nA�»•i bA�º­t</button>
            </form>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="mb-4 font-semibold text-slate-800">CÄ‚Â´ng thĂ¡Â»Â©c hĂ„â€Ă‚Â´m nay</h3>
        <form method="post" action="<?= URLROOT; ?>/admin/banners/today" class="grid grid-cols-1 gap-3 md:grid-cols-[180px_1fr_auto]">
            <?= csrf_field(); ?>
            <input type="date" name="for_date" value="<?= htmlspecialchars($forDate, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="recipe_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">-- ChA�»n cĂ´ng thA�»©c approved --</option>
                <?php foreach ($recipes as $recipe): ?>
                    <?php $rid = (int) ($recipe['id'] ?? 0); ?>
                    <option value="<?= $rid; ?>" <?= ((int) ($recipeOfDay['id'] ?? 0) === $rid) ? 'selected' : ''; ?>>
                        #<?= $rid; ?> - <?= htmlspecialchars((string) ($recipe['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">LÄ‚â€ Ă‚Â°u</button>
        </form>
        <?php if ($recipeOfDay !== null): ?>
            <p class="mt-3 text-sm text-slate-600">
                A�¿½ang ch?n cho <?= htmlspecialchars((string) ($recipeOfDay['for_date'] ?? $forDate), ENT_QUOTES, 'UTF-8'); ?>:
                <strong><?= htmlspecialchars((string) ($recipeOfDay['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>
        <?php endif; ?>
    </div>
</div>


