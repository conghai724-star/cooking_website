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
    'banner_saved' => 'Đã lưu banner trang chủ.',
    'featured_saved' => 'Đã cập nhật công thức nổi bật.',
    'today_saved' => 'Đã cập nhật công thức hôm nay.',
    'banner_save_failed' => 'Lưu banner thất bại.',
    'featured_save_failed' => 'Lưu công thức nổi bật thất bại.',
    'today_save_failed' => 'Lưu công thức hôm nay thất bại.',
    default => '',
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lý banner và nội dung nổi bật</h1>
        <p class="text-sm text-slate-500">Cập nhật banner, ghim công thức và chọn "Công thức hôm nay".</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="mb-4 font-semibold text-slate-800">Banner hiện tại</h3>
            <form method="post" action="<?= URLROOT; ?>/admin/banners/banner" enctype="multipart/form-data" class="space-y-3">
                <?= csrf_field(); ?>
                <input type="text" name="title" required maxlength="255" value="<?= htmlspecialchars((string) ($banner['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tiêu đề banner" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <textarea name="subtitle" rows="3" placeholder="Mô tả ngắn" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= htmlspecialchars((string) ($banner['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                <input type="text" name="image_url" maxlength="255" value="<?= htmlspecialchars((string) ($banner['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="URL ảnh banner" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="text-xs text-slate-500">Dán link ảnh ngoài (ví dụ: <code>https://images.unsplash.com/...</code>).</p>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Hoặc tải ảnh từ thiết bị</label>
                    <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,.gif" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                </div>
                <?php if ($bannerImageSrc !== ''): ?>
                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        <img src="<?= htmlspecialchars($bannerImageSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Banner preview" class="h-44 w-full object-cover">
                    </div>
                <?php endif; ?>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <input type="text" name="cta_text" maxlength="80" value="<?= htmlspecialchars((string) ($banner['cta_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nút CTA" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="text" name="cta_url" maxlength="255" value="<?= htmlspecialchars((string) ($banner['cta_url'] ?? '/recipes'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Link CTA (vd: /recipes)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Kích hoạt ngay
                </label>
                <div>
                    <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Lưu banner</button>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="mb-4 font-semibold text-slate-800">Công thức nổi bật</h3>
            <form method="post" action="<?= URLROOT; ?>/admin/banners/featured" class="space-y-3">
                <?= csrf_field(); ?>
                <input type="text"
                       name="featured_recipe_ids"
                       value="<?= htmlspecialchars(implode(',', array_map('intval', $featuredIds)), ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Nhập ID công thức, cách nhau bởi dấu phẩy. VD: 10,12,25"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="text-xs text-slate-500">Danh sách hiện tại: <?= htmlspecialchars(implode(', ', array_map('intval', $featuredIds)), ENT_QUOTES, 'UTF-8'); ?></p>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Lưu công thức nổi bật</button>
            </form>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="mb-4 font-semibold text-slate-800">Công thức hôm nay</h3>
        <form method="post" action="<?= URLROOT; ?>/admin/banners/today" class="grid grid-cols-1 gap-3 md:grid-cols-[180px_1fr_auto]">
            <?= csrf_field(); ?>
            <input type="date" name="for_date" value="<?= htmlspecialchars($forDate, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="recipe_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">-- Chọn công thức approved --</option>
                <?php foreach ($recipes as $recipe): ?>
                    <?php $rid = (int) ($recipe['id'] ?? 0); ?>
                    <option value="<?= $rid; ?>" <?= ((int) ($recipeOfDay['id'] ?? 0) === $rid) ? 'selected' : ''; ?>>
                        #<?= $rid; ?> - <?= htmlspecialchars((string) ($recipe['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Lưu</button>
        </form>
        <?php if ($recipeOfDay !== null): ?>
            <p class="mt-3 text-sm text-slate-600">
                Đang chọn cho <?= htmlspecialchars((string) ($recipeOfDay['for_date'] ?? $forDate), ENT_QUOTES, 'UTF-8'); ?>:
                <strong><?= htmlspecialchars((string) ($recipeOfDay['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>
        <?php endif; ?>
    </div>
</div>


