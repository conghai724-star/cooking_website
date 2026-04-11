<?php
$rows = is_array($rows ?? null) ? $rows : [];
$keyword = (string) ($keyword ?? '');
$userId = max(0, (int) ($userId ?? 0));
$fromDate = (string) ($fromDate ?? '');
$toDate = (string) ($toDate ?? '');
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));
$notice = (string) ($notice ?? '');
$canModerateMealPlans = (bool) ($canModerateMealPlans ?? false);

$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$noticeText = match ($notice) {
    'deleted' => 'Đã xóa mục thực đơn vi phạm.',
    'delete_failed' => 'Không thể xóa mục thực đơn.',
    default => '',
};

$mealTypeLabel = static fn(string $v): string => match ($v) {
    'breakfast' => 'Sáng',
    'lunch' => 'Trưa',
    'dinner' => 'Tối',
    default => $v,
};
$dishRoleLabel = static fn(string $v): string => match ($v) {
    'main' => 'Món chính',
    'side' => 'Món phụ',
    'soup' => 'Canh',
    'dessert' => 'Tráng miệng',
    'drink' => 'Đồ uống',
    'other' => 'Khác',
    default => $v,
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lý thực đơn</h1>
        <p class="text-sm text-slate-500">Xem và xử lý kế hoạch thực đơn của người dùng.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= $e($noticeText); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <form method="get" action="<?= URLROOT; ?>/admin/mealplans" class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Từ khóa
                <input type="text" name="q" value="<?= $e($keyword); ?>" class="w-72 rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal" placeholder="Tên user, email, tên công thức">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                User ID
                <input type="number" min="0" name="user_id" value="<?= $userId > 0 ? $userId : ''; ?>" class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal" placeholder="id">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Từ ngày
                <input type="date" name="from" value="<?= $e($fromDate); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Đến ngày
                <input type="date" name="to" value="<?= $e($toDate); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
            </label>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Lọc</button>
        </form>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="font-semibold text-slate-800">Danh sách thực đơn (<?= $total; ?>)</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-2 font-semibold">ID</th>
                        <th class="px-4 py-2 font-semibold">Người dùng</th>
                        <th class="px-4 py-2 font-semibold">Ngày</th>
                        <th class="px-4 py-2 font-semibold">Buổi</th>
                        <th class="px-4 py-2 font-semibold">Vai trò món</th>
                        <th class="px-4 py-2 font-semibold">Công thức</th>
                        <th class="px-4 py-2 font-semibold">Tạo lúc</th>
                        <th class="px-4 py-2 font-semibold">Xử lý</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Không có dữ liệu.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="px-4 py-2">#<?= (int) ($row['id'] ?? 0); ?></td>
                                <td class="px-4 py-2">
                                    <p class="font-semibold text-slate-800"><?= $e($row['user_name'] ?? 'N/A'); ?></p>
                                    <p class="text-xs text-slate-500"><?= $e($row['user_email'] ?? ''); ?> (ID: <?= (int) ($row['user_id'] ?? 0); ?>)</p>
                                </td>
                                <td class="px-4 py-2"><?= $e($row['plan_date'] ?? ''); ?></td>
                                <td class="px-4 py-2"><?= $e($mealTypeLabel((string) ($row['meal_type'] ?? ''))); ?></td>
                                <td class="px-4 py-2"><?= $e($dishRoleLabel((string) ($row['dish_role'] ?? 'main'))); ?></td>
                                <td class="px-4 py-2">
                                    <a href="<?= URLROOT; ?>/recipes/<?= (int) ($row['recipe_id'] ?? 0); ?>" class="hover:text-primary hover:underline">
                                        <?= $e($row['recipe_title'] ?? 'N/A'); ?>
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-slate-500"><?= $e($row['created_at'] ?? ''); ?></td>
                                <td class="px-4 py-2">
                                    <?php if ($canModerateMealPlans): ?>
                                        <form method="post" action="<?= URLROOT; ?>/admin/mealplans/<?= (int) ($row['id'] ?? 0); ?>/delete" onsubmit="return confirm('Xóa mục thực đơn này?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Xóa vi phạm</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Không có quyền xử lý</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm">
                <span class="text-slate-500">Trang <?= $page; ?> / <?= $totalPages; ?></span>
                <div class="flex items-center gap-2">
                    <?php $baseParams = $_GET; ?>
                    <?php if ($page > 1): ?>
                        <?php $baseParams['page'] = $page - 1; ?>
                        <a class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/admin/mealplans?<?= http_build_query($baseParams); ?>">Trước</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <?php $baseParams['page'] = $page + 1; ?>
                        <a class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/admin/mealplans?<?= http_build_query($baseParams); ?>">Tiếp</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

