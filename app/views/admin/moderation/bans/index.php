<?php
$rows = is_array($rows ?? null) ? $rows : [];
$keyword = (string) ($keyword ?? '');
$type = (string) ($type ?? '');
$status = (string) ($status ?? '');
$notice = (string) ($notice ?? '');

$noticeText = match ($notice) {
    'released' => 'Đã gờ ban thành công.',
    'release_failed' => 'Không thể gờ ban. Vui lòng thử lại.',
    default => '',
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Danh sách ban</h1>
        <p class="text-sm text-slate-500">Theo dõi và gờ các hình thức ban/khóa hiện hành.</p>
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
                placeholder="TĂ¬m ngA�°A�»i dĂ¹ng, email, lĂ½ do"
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
            <h2 class="font-semibold text-slate-900">Danh sÄ‚Â¡ch (<?= count($rows); ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">NgA�°A�»i dĂ¹ng</th>
                        <th class="px-4 py-3 font-semibold">LÄ‚Â½ do</th>
                        <th class="px-4 py-3 font-semibold">LoA�º¡i</th>
                        <th class="px-4 py-3 font-semibold">ThA�»i gian</th>
                        <th class="px-4 py-3 font-semibold">HA�º¿t hA�º¡n</th>
                        <th class="px-4 py-3 font-semibold">TrA�º¡ng thĂ¡i</th>
                        <th class="px-4 py-3 font-semibold">HÄ‚Â nh Ă„â€˜Ă¡Â»â„¢ng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Không cĂ³ dA�»¯ liA�»‡u ban.</td>
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
                                <div class="line-clamp-2 text-slate-700"><?= htmlspecialchars($reason !== '' ? $reason : 'KhÄ‚Â´ng cÄ‚Â³', ENT_QUOTES, 'UTF-8'); ?></div>
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
                                <form method="post" action="<?= URLROOT; ?>/admin/bans/release" onsubmit="return confirm('XĂ¡c nhA�º­n gA�»¡ ban/khóa?');">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="source" value="<?= htmlspecialchars((string) ($row['source'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="row_id" value="<?= (int) ($row['row_id'] ?? 0); ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) ($row['user_id'] ?? 0); ?>">
                                    <input type="hidden" name="penalty_action" value="<?= htmlspecialchars((string) ($row['penalty_action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="return_q" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="return_type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="rounded border border-sky-300 px-2 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-50">
                                        GA�»¡ ban
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


