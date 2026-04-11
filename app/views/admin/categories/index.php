<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lý danh mục</h1>
        <p class="text-sm text-slate-500">Tổ chức nhóm món ăn và bộ sưu tập.</p>
    </div>

    <?php if (!empty($notice) || !empty($error)): ?>
        <div class="space-y-3">
            <?php if (!empty($notice)): ?>
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700">
                    <?= $notice === 'created' ? 'Danh mục mới đã được thêm thành công.' : htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-700">
                    <?= $error === 'missing_name' ? 'Vui lòng nhập tên danh mục.' : 'Danh mục đã tồn tại hoặc không thể tạo. Vui lòng kiểm tra lại.'; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <section class="bg-white rounded shadow-sm border border-slate-100 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-slate-900">Thêm danh mục mới</h2>
                <p class="text-sm text-slate-500">Tạo danh mục mới để tổ chức nội dung.</p>
            </div>

            <form action="/admin/categories/create" method="post" class="space-y-4">
                <div>
                    <label for="category-name" class="block text-sm font-medium text-slate-700">Tên danh mục</label>
                    <input id="category-name" name="name" type="text" class="mt-1 block w-full rounded-md border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" required />
                </div>

                <div>
                    <label for="category-type" class="block text-sm font-medium text-slate-700">Loại danh mục</label>
                    <select id="category-type" name="type" class="mt-1 block w-full rounded-md border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <?php foreach ($typeLabels as $typeKey => $label): ?>
                            <option value="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">Thêm danh mục</button>
            </form>
        </section>

        <section class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-800">Danh sách danh mục</h3>
                    <p class="text-sm text-slate-500">Hiển thị <?= (int) count($categories); ?> trên tổng số <?= (int) ($total ?? 0); ?> danh mục.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Trang <?= (int) ($page ?? 1); ?>/<?= (int) ($totalPages ?? 1); ?></span>
            </div>

            <?php if (!empty($categories)): ?>
                <div class="min-w-full divide-y divide-slate-100 text-sm text-slate-700">
                    <?php foreach ($categories as $category): ?>
                        <div class="grid grid-cols-[auto_1fr_auto] gap-4 px-6 py-4 items-center">
                            <span class="font-semibold text-slate-900">#<?= (int) ($category['id'] ?? 0); ?></span>
                            <div>
                                <div class="font-medium text-slate-800"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-xs uppercase tracking-[0.15em] text-slate-500"><?= htmlspecialchars($typeLabels[$category['type']] ?? $category['type'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="p-6 text-sm text-slate-500">Không tìm thấy danh mục nào.</div>
            <?php endif; ?>

            <?php if (!empty($totalPages) && $totalPages > 1): ?>
                <div class="border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <nav class="flex items-center justify-between gap-2 text-sm">
                        <?php $prevDisabled = $page <= 1; ?>
                        <a class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:bg-slate-100<?= $prevDisabled ? ' pointer-events-none opacity-50' : ''; ?>" href="/admin/categories?page=<?= max(1, (int) ($page - 1)); ?>" <?= $prevDisabled ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>Trước</a>
                        <div class="flex items-center gap-2">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a class="inline-flex h-9 min-w-[2rem] items-center justify-center rounded-lg border px-3 text-slate-700 <?= $i === $page ? 'bg-primary text-white border-primary' : 'bg-white hover:bg-slate-100'; ?>" href="/admin/categories?page=<?= $i; ?>"><?= $i; ?></a>
                            <?php endfor; ?>
                        </div>
                        <?php $nextDisabled = $page >= $totalPages; ?>
                        <a class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:bg-slate-100<?= $nextDisabled ? ' pointer-events-none opacity-50' : ''; ?>" href="/admin/categories?page=<?= min($totalPages, (int) ($page + 1)); ?>" <?= $nextDisabled ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>Tiếp</a>
                    </nav>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

