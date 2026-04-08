<?php
$ingredients = is_array($ingredients ?? null) ? $ingredients : [];
$categories = is_array($categories ?? null) ? $categories : [];

$success = isset($_GET['success']) && $_GET['success'] === '1';
$error = (string) ($_GET['error'] ?? '');
$errorMessage = '';
if ($error === 'missing_name') {
    $errorMessage = 'Vui lòng nhập tên nguyên liệu.';
} elseif ($error === 'save_failed') {
    $errorMessage = 'Không thể lưu nguyên liệu. Vui lòng thử lại.';
}
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Thư viện nguyên liệu</h1>
        <p class="text-sm text-slate-500">Admin có thể tạo, sửa, xóa. Người dùng gửi nguyên liệu sẽ ở trạng thái chờ duyệt.</p>
    </div>

    <?php if ($success): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Đã thêm nguyên liệu mới.
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Thêm nguyên liệu (Admin)</h3>
            <button id="toggle-ingredient-form" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600" type="button">Thêm mới</button>
        </div>
        <div id="ingredient-form" class="hidden">
            <form class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2" method="post" action="<?= URLROOT; ?>/admin/ingredients/create" enctype="multipart/form-data">
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
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Sơ chế</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="preparation" rows="2"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Bảo quản</label>
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
                    <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Lưu nguyên liệu</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Danh sách nguyên liệu</h3>
        </div>
        <?php if (empty($ingredients)): ?>
            <div class="p-6 text-sm text-slate-500">Chưa có nguyên liệu nào.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-background-light text-slate-500">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">Tên</th>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">Danh mục</th>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">Trạng thái</th>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">Hành động</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php foreach ($ingredients as $ingredient): ?>
                        <?php
                        $status = $ingredient['status'] ?? 'approved';
                        $statusClass = $status === 'approved'
                            ? 'bg-emerald-100 text-emerald-700'
                            : ($status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-yellow-100 text-yellow-700');
                        $statusLabel = $status === 'approved'
                            ? 'Đã duyệt'
                            : ($status === 'rejected' ? 'Từ chối' : 'Chờ duyệt');
                        ?>
                        <tr>
                            <td class="px-6 py-4 font-semibold text-slate-900"><?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($ingredient['category_name'] ?? 'Chưa phân loại', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass; ?>"><?= $statusLabel; ?></span>
                                    <?php if ($status === 'rejected' && !empty($ingredient['rejection_reason'])): ?>
                                        <span class="text-xs text-rose-600">Lý do: <?= htmlspecialchars((string) $ingredient['rejection_reason'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a class="rounded-md border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" href="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>">Xem</a>
                                    <?php if ($status !== 'approved'): ?>
                                        <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/approve">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" type="submit">Duyệt</button>
                                        </form>
                                        <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/reject" class="flex items-center gap-2" onsubmit="return confirm('Từ chối nguyên liệu này?');">
                                            <?= csrf_field(); ?>
                                            <input class="w-40 rounded-md border border-slate-200 px-2 py-1 text-xs" name="reason" placeholder="Lý do từ chối">
                                            <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">Từ chối</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status === 'approved'): ?>
                                        <a class="rounded-md border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" href="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/edit">Sửa</a>
                                        <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/delete" onsubmit="return confirm('Xóa nguyên liệu này?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">Xóa</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    (function () {
        var toggle = document.getElementById('toggle-ingredient-form');
        var form = document.getElementById('ingredient-form');
        if (!toggle || !form) {
            return;
        }
        toggle.addEventListener('click', function () {
            form.classList.toggle('hidden');
        });
    })();
</script>


