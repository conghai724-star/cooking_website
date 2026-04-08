<?php
$ingredient = is_array($ingredient ?? null) ? $ingredient : [];
$nutrition = is_array($nutrition ?? null) ? $nutrition : [];
$categories = is_array($categories ?? null) ? $categories : [];
$error = (string) ($error ?? '');

$image = (string) ($ingredient['image'] ?? '');
$imageUrl = '';
if ($image !== '') {
    $imageUrl = preg_match('/^https?:\\/\\//i', $image) ? $image : URLROOT . '/uploads/' . $image;
}
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Chỉnh sửa nguyên liệu</h1>
            <p class="text-sm text-slate-500">Cập nhật thông tin nguyên liệu và dữ liệu dinh dưỡng.</p>
        </div>
        <a class="text-sm font-semibold text-slate-500 hover:text-slate-900" href="<?= URLROOT; ?>/admin/ingredients">Quay lại</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm" method="post" enctype="multipart/form-data">
        <?= csrf_field(); ?>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Tên nguyên liệu *</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="name" value="<?= htmlspecialchars((string) ($ingredient['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Danh mục</label>
                <select class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="category_id">
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id']; ?>" <?= (int) ($ingredient['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Mô tả</label>
                <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="description" rows="3"><?= htmlspecialchars((string) ($ingredient['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Công dụng</label>
                <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="usage" rows="2"><?= htmlspecialchars((string) ($ingredient['usage'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Cách sơ chế</label>
                <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="preparation" rows="2"><?= htmlspecialchars((string) ($ingredient['preparation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Cách bảo quản</label>
                <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="storage" rows="2"><?= htmlspecialchars((string) ($ingredient['storage'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Hình ảnh</label>
                <?php if ($imageUrl !== ''): ?>
                    <img class="mb-3 h-40 w-full rounded-lg object-cover" src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Ingredient image">
                <?php endif; ?>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="file" name="image" accept="image/*">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Calories (kcal)</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="calories" value="<?= htmlspecialchars((string) ($nutrition['calories'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Protein (g)</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="protein" value="<?= htmlspecialchars((string) ($nutrition['protein'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Fat (g)</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="fat" value="<?= htmlspecialchars((string) ($nutrition['fat'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Carb (g)</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="carb" value="<?= htmlspecialchars((string) ($nutrition['carb'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Lưu thay đổi</button>
            </div>
        </div>
    </form>
</div>

