<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lý danh mục</h1>
        <p class="text-sm text-slate-500">Tổ chức nhóm món ăn và bộ sưu tập.</p>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">
                <span>Danh sách danh mục</span>
            </h3>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="button">Thêm danh mục</button>
        </div>

        <?php if (!empty($categories)): ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($categories as $category): ?>
                    <div class="p-4 flex items-center justify-between">
                        <span class="font-medium text-slate-800"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="text-xs text-slate-500">#<?= (int) ($category['id'] ?? 0); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-6 text-sm text-slate-500">Không tìm thấy danh mục nào.</div>
        <?php endif; ?>
    </div>
</div>
