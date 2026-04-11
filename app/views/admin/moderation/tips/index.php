<?php
$tips = is_array($tips ?? null) ? $tips : [];
$page = max(1, (int) ($page ?? 1));
$total = max(0, (int) ($total ?? count($tips)));
$totalPages = max(1, (int) ($totalPages ?? 1));
$page = min($page, $totalPages);
$keyword = (string) ($keyword ?? '');
$status = (string) ($status ?? '');

$buildPageUrl = static function (int $targetPage) use ($keyword, $status): string {
    $params = ['page' => max(1, $targetPage)];
    if ($keyword !== '') {
        $params['q'] = $keyword;
    }
    if ($status !== '') {
        $params['status'] = $status;
    }
    return URLROOT . '/admin/tips?' . http_build_query($params);
};

$badgeClass = static function (string $status): array {
    return match ($status) {
        'approved' => ['Đã duyệt', 'bg-emerald-100 text-emerald-700'],
        'rejected' => ['Từ chối', 'bg-rose-100 text-rose-700'],
        default => ['Chờ duyệt', 'bg-yellow-100 text-yellow-700'],
    };
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lý mẹo vặt</h1>
        <p class="text-sm text-slate-500">Tạo, duyệt và cập nhật mẹo vặt do người dùng gửi.</p>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Danh sách mẹo vặt (<?= $total; ?>)</h3>
        </div>
        <?php if (empty($tips)): ?>
            <div class="p-6 text-sm text-slate-500">Chưa có mẹo vặt nào.</div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($tips as $tip): ?>
                    <?php
                    $status = (string) ($tip['status'] ?? 'pending');
                    [$statusLabel, $statusClass] = $badgeClass($status);
                    ?>
                    <div class="p-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($tip['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="text-xs text-slate-500">Tác giả: <?= htmlspecialchars((string) ($tip['author_name'] ?? 'Người dùng'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php if ($status === 'rejected' && !empty($tip['rejection_reason'])): ?>
                                <p class="mt-2 text-xs text-rose-600">Lý do: <?= htmlspecialchars((string) $tip['rejection_reason'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass; ?>"><?= $statusLabel; ?></span>
                            <?php if ($status !== 'approved'): ?>
                                <form method="post" action="<?= URLROOT; ?>/admin/tips/<?= (int) $tip['id']; ?>/approve">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="return_page" value="<?= $page; ?>">
                                    <button class="rounded-md bg-emerald-500 px-3 py-1 text-xs font-semibold text-white" type="submit">Duyệt</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($status === 'pending'): ?>
                                <form method="post" action="<?= URLROOT; ?>/admin/tips/<?= (int) $tip['id']; ?>/reject" class="flex items-center gap-2">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="return_page" value="<?= $page; ?>">
                                    <input class="w-40 rounded-md border border-slate-200 px-2 py-1 text-xs" name="reason" placeholder="Lý do từ chối">
                                    <button class="rounded-md bg-rose-500 px-3 py-1 text-xs font-semibold text-white" type="submit">Từ chối</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= URLROOT; ?>/admin/tips/<?= (int) $tip['id']; ?>/delete" onsubmit="return confirm('Xóa mẹo vặt này?');">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="return_page" value="<?= $page; ?>">
                                <button class="rounded-md border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-600" type="submit">Xóa</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-6 py-4">
                <div class="text-sm text-slate-500">Trang <?= $page; ?> / <?= $totalPages; ?> · Tổng <?= $total; ?> mẹo vặt</div>
                <div class="flex items-center gap-2">
                    <a class="rounded border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>" href="<?= $buildPageUrl(max(1, $page - 1)); ?>">Trước</a>
                    <a class="rounded border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>" href="<?= $buildPageUrl(min($totalPages, $page + 1)); ?>">Sau</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>


