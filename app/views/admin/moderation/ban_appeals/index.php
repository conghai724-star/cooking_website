<?php
$rows = is_array($rows ?? null) ? $rows : [];
$page = max(1, (int) ($page ?? 1));
$total = max(0, (int) ($total ?? count($rows)));
$totalPages = max(1, (int) ($totalPages ?? 1));
$page = min($page, $totalPages);
$status = (string) ($status ?? '');
$keyword = (string) ($keyword ?? '');
$notice = (string) ($notice ?? '');

$buildPageUrl = static function (int $targetPage) use ($keyword, $status): string {
    $params = ['page' => max(1, $targetPage)];
    if ($keyword !== '') {
        $params['q'] = $keyword;
    }
    if ($status !== '') {
        $params['status'] = $status;
    }
    return URLROOT . '/admin/ban-appeals?' . http_build_query($params);
};

$noticeText = match ($notice) {
    'reviewed' => 'Đã cập nhật trạng thái khiếu nại.',
    'review_failed' => 'Không thể xử lý khiếu nại. Vui lòng thử lại.',
    default => '',
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Khiếu nại ban/quyền</h1>
        <p class="text-sm text-slate-500">Theo dõi và xử lý khiếu nại từ người dùng.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <form method="get" action="<?= URLROOT; ?>/admin/ban-appeals" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tìm user, email, nội dung" class="w-80 max-w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="" <?= $status === '' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Đang chờ</option>
                <option value="reviewing" <?= $status === 'reviewing' ? 'selected' : ''; ?>>Đang xem xét</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : ''; ?>>Đã chấp nhận</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : ''; ?>>Đã từ chối</option>
            </select>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Lọc</button>
        </form>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="font-semibold text-slate-900">Danh sách (<?= $total; ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Người dùng</th>
                    <th class="px-4 py-3 font-semibold">Mục tiêu</th>
                    <th class="px-4 py-3 font-semibold">Lý do khiếu nại</th>
                    <th class="px-4 py-3 font-semibold">Trạng thái</th>
                    <th class="px-4 py-3 font-semibold">Hành động</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Không có khiếu nại.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars((string) ($row['user_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars((string) ($row['user_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <?= htmlspecialchars((string) ($row['target_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                #<?= (int) ($row['target_id'] ?? 0); ?>
                            </td>
                            <td class="px-4 py-3 max-w-[360px]">
                                <div class="line-clamp-3 text-slate-700"><?= htmlspecialchars((string) ($row['appeal_reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                    <?= htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <form method="post" action="<?= URLROOT; ?>/admin/ban-appeals/review" class="space-y-2">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="appeal_id" value="<?= (int) ($row['id'] ?? 0); ?>">
                                    <input type="hidden" name="return_q" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="return_page" value="<?= $page; ?>">
                                    <select name="decision" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                        <option value="reviewing">Đang xem xét</option>
                                        <option value="approved">Chấp nhận</option>
                                        <option value="rejected">Từ chối</option>
                                    </select>
                                    <textarea name="admin_note" rows="2" class="w-full rounded border border-slate-300 px-2 py-1 text-xs" placeholder="Ghi chú cho user (không bắt buộc)"></textarea>
                                    <button type="submit" class="rounded border border-sky-300 px-2 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-50">Cập nhật</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($totalPages > 1): ?>
                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-4 py-4">
                    <div class="text-sm text-slate-500">Trang <?= $page; ?> / <?= $totalPages; ?> · Tổng <?= $total; ?> khiếu nại</div>
                    <div class="flex items-center gap-2">
                        <a class="rounded border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>" href="<?= $buildPageUrl(max(1, $page - 1)); ?>">Trước</a>
                        <a class="rounded border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>" href="<?= $buildPageUrl(min($totalPages, $page + 1)); ?>">Sau</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


