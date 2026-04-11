<?php
$rows = is_array($rows ?? null) ? $rows : [];
$page = max(1, (int) ($page ?? 1));
$total = max(0, (int) ($total ?? count($rows)));
$totalPages = max(1, (int) ($totalPages ?? 1));
$page = min($page, $totalPages);
$keyword = (string) ($keyword ?? '');
$type = (string) ($type ?? '');
$status = (string) ($status ?? '');
$notice = (string) ($notice ?? '');

$buildPageUrl = static function (int $targetPage) use ($keyword, $type, $status): string {
    $params = ['page' => max(1, $targetPage)];
    if ($keyword !== '') {
        $params['q'] = $keyword;
    }
    if ($type !== '') {
        $params['type'] = $type;
    }
    if ($status !== '') {
        $params['status'] = $status;
    }
    return URLROOT . '/admin/bans?' . http_build_query($params);
};

$noticeText = match ($notice) {
    'released' => 'Đã gỡ ban thành công.',
    'release_failed' => 'Không thể gỡ ban. Vui lòng thử lại.',
    default => '',
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Danh sách ban</h1>
        <p class="text-sm text-slate-500">Theo dõi và gỡ các hình thức ban/khóa hiện hành.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <form method="get" action="<?= URLROOT; ?>/admin/bans" class="flex flex-wrap items-center gap-3">
            <input
                type="text"
                name="q"
                value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Tìm người dùng, email, lý do"
                class="w-80 max-w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            >
            <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="" <?= $type === '' ? 'selected' : ''; ?>>Tất cả loại</option>
                <option value="account" <?= $type === 'account' ? 'selected' : ''; ?>>Ban tài khoản</option>
                <option value="comment" <?= $type === 'comment' ? 'selected' : ''; ?>>Khóa bình luận</option>
                <option value="recipe" <?= $type === 'recipe' ? 'selected' : ''; ?>>Khóa đăng bài</option>
            </select>
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="" <?= $status === '' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Đang hiệu lực</option>
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
                        <th class="px-4 py-3 font-semibold">Lý do</th>
                        <th class="px-4 py-3 font-semibold">Loại</th>
                        <th class="px-4 py-3 font-semibold">Thời gian</th>
                        <th class="px-4 py-3 font-semibold">Hết hạn</th>
                        <th class="px-4 py-3 font-semibold">Trạng thái</th>
                        <th class="px-4 py-3 font-semibold">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Không có dữ liệu ban.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $userName = (string) ($row['user_name'] ?? 'N/A');
                        $userEmail = (string) ($row['user_email'] ?? '');
                        $reason = (string) ($row['reason'] ?? '');
                        $typeLabel = (string) ($row['type_label'] ?? '');
                        $startedAt = (string) ($row['started_at'] ?? '');
                        $expiresAt = (string) ($row['expires_at'] ?? '');
                        $rowStatus = (string) ($row['status'] ?? 'active');
                        ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td class="px-4 py-3 max-w-[320px]">
                                <div class="line-clamp-2 text-slate-700"><?= htmlspecialchars($reason !== '' ? $reason : 'Không có', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                                    <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($startedAt, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3 text-slate-600">
                                <?= htmlspecialchars($expiresAt !== '' ? $expiresAt : 'Vĩnh viễn', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                    <?= htmlspecialchars($rowStatus, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <form method="post" action="<?= URLROOT; ?>/admin/bans/release" onsubmit="return confirm('Xác nhận gỡ ban/khóa?');">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="source" value="<?= htmlspecialchars((string) ($row['source'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="row_id" value="<?= (int) ($row['row_id'] ?? 0); ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) ($row['user_id'] ?? 0); ?>">
                                    <input type="hidden" name="penalty_action" value="<?= htmlspecialchars((string) ($row['penalty_action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="return_q" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="return_type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="return_page" value="<?= $page; ?>">
                                    <button type="submit" class="rounded border border-sky-300 px-2 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-50">
                                        Gỡ ban
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($totalPages > 1): ?>
                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-4 py-4">
                    <div class="text-sm text-slate-500">Trang <?= $page; ?> / <?= $totalPages; ?> · Tổng <?= $total; ?> ban/khoá</div>
                    <div class="flex items-center gap-2">
                        <a class="rounded border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>" href="<?= $buildPageUrl(max(1, $page - 1)); ?>">Trước</a>
                        <a class="rounded border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>" href="<?= $buildPageUrl(min($totalPages, $page + 1)); ?>">Sau</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
