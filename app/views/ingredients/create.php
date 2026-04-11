<?php
$categories = is_array($categories ?? null) ? $categories : [];
$error = (string) ($error ?? '');
$success = (bool) ($success ?? false);
?>

<section class="w-full">
    <div class="mx-auto w-full max-w-3xl rounded-2xl border border-slate-200 bg-white p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Góp ý nguyên liệu mới</h1>
            <p class="mt-2 text-sm text-slate-500">Nguyên liệu sẽ ở trạng thái chờ duyệt trước khi hiển thị cho mọi người.</p>
        </div>

        <?php if ($success): ?>
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Cảm ơn bạn! Nguyên liệu đã được gửi và đang chờ duyệt.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form class="grid grid-cols-1 gap-4 md:grid-cols-2" method="post" enctype="multipart/form-data">
            <?= csrf_field(); ?>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Tên nguyên liệu *</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="name" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Danh mục</label>
                <select class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="category_id">
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id']; ?>"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Mô tả</label>
                <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="description" rows="3"></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Công dụng</label>
                <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="usage" rows="2"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Cách sơ chế</label>
                <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="preparation" rows="2"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Cách bảo quản</label>
                <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="storage" rows="2"></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Hình ảnh</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="file" name="image" accept="image/*">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Calories (kcal)</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="calories">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Protein (g)</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="protein">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Fat (g)</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="fat">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Carb (g)</label>
                <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="carb">
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white" type="submit">Gửi duyệt</button>
            </div>
        </form>
    </div>
</section>
