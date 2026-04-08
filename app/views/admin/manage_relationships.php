<?php
$rows = is_array($rows ?? null) ? $rows : [];
$keyword = (string) ($keyword ?? '');
$userId = (int) ($userId ?? 0);
$side = (string) ($side ?? 'all');
$risk = (string) ($risk ?? 'all');
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));
$canModerate = (bool) ($canModerate ?? false);
$top24h = is_array($top24h ?? null) ? $top24h : [];
$notice = (string) ($notice ?? '');

$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$noticeText = match ($notice) {
    'removed' => '�� g? m?i quan h? follow.',
    'remove_failed' => 'Kh�ng thể gỡ mối quan hệ follow.',
    'lock_updated' => '�� c?p nh?t tr?ng th�i kh�a follow.',
    'lock_update_failed' => 'Kh�ng thể cập nhật kh�a follow.',
    default => '',
};

$buildQuery = static function (int $targetPage) use ($keyword, $userId, $side, $risk): string {
    $params = ['page' => $targetPage];
    if ($keyword !== '') {
        $params['q'] = $keyword;
    }
    if ($userId > 0) {
        $params['user_id'] = $userId;
    }
    if ($side !== 'all') {
        $params['side'] = $side;
    }
    if ($risk !== 'all') {
        $params['risk'] = $risk;
    }
    return http_build_query($params);
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lA� mối quan hệ</h1>
        <p class="text-sm text-slate-500">GiA�m sA�t follow giữa người dA�ng vA� xử lA� vi phạm khi cần.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= $e($noticeText); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <form method="get" action="<?= URLROOT; ?>/admin/relationships" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="<?= $e($keyword); ?>" placeholder="T�m theo t�n/email ngu?i theo d�i ho?c du?c theo d�i" class="w-96 max-w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <input type="number" name="user_id" min="0" value="<?= $userId > 0 ? $userId : ''; ?>" placeholder="Lọc theo user ID" class="w-44 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="side" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="all" <?= $side === 'all' ? 'selected' : ''; ?>>Vai trA�: Tất cả</option>
                <option value="as_follower" <?= $side === 'as_follower' ? 'selected' : ''; ?>>User lA� người theo dA�i</option>
                <option value="as_following" <?= $side === 'as_following' ? 'selected' : ''; ?>>User l� ngu?i du?c theo d�i</option>
            </select>
            <select name="risk" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="all" <?= $risk === 'all' ? 'selected' : ''; ?>>Rủi ro: Tất cả</option>
                <option value="suspicious" <?= $risk === 'suspicious' ? 'selected' : ''; ?>>Nghi ngờ</option>
                <option value="high_risk" <?= $risk === 'high_risk' ? 'selected' : ''; ?>>Rủi ro cao</option>
            </select>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Lọc</button>
        </form>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <h3 class="mb-3 text-sm font-semibold text-slate-800">Top follow 24h (nghi ngờ spam)</h3>
        <?php if (empty($top24h)): ?>
            <p class="text-sm text-slate-500">Chưa cA� dữ liệu follow 24h.</p>
        <?php else: ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($top24h as $item): ?>
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        #<?= (int) ($item['follower_id'] ?? 0); ?> <?= $e($item['follower_name'] ?? 'N/A'); ?> � <?= (int) ($item['follows_last_24h'] ?? 0); ?>/24h
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="font-semibold text-slate-900">Danh sA�ch follow (<?= $total; ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Người theo dA�i</th>
                        <th class="px-4 py-3 font-semibold">Ngu?i du?c theo d�i</th>
                        <th class="px-4 py-3 font-semibold">Loại</th>
                        <th class="px-4 py-3 font-semibold">Nhịp follow</th>
                        <th class="px-4 py-3 font-semibold">Rủi ro</th>
                        <th class="px-4 py-3 font-semibold">NgA�y tạo</th>
                        <th class="px-4 py-3 font-semibold">Thao tA�c</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Kh�ng cA� dữ liệu mối quan hệ.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $riskLevel = (string) ($row['risk_level'] ?? 'normal');
                        $riskLabel = match ($riskLevel) {
                            'high_risk' => 'Rủi ro cao',
                            'suspicious' => 'Nghi ngờ',
                            default => 'BA�nh thường',
                        };
                        $riskClass = match ($riskLevel) {
                            'high_risk' => 'bg-rose-100 text-rose-700',
                            'suspicious' => 'bg-amber-100 text-amber-700',
                            default => 'bg-emerald-100 text-emerald-700',
                        };
                        ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">#<?= (int) ($row['follower_id'] ?? 0); ?> - <?= $e($row['follower_name'] ?? 'N/A'); ?></div>
                                <div class="text-xs text-slate-500"><?= $e($row['follower_email'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">#<?= (int) ($row['following_id'] ?? 0); ?> - <?= $e($row['following_name'] ?? 'N/A'); ?></div>
                                <div class="text-xs text-slate-500"><?= $e($row['following_email'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">Follow</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <div><?= (int) ($row['follows_last_hour'] ?? 0); ?>/1h</div>
                                <div class="text-xs text-slate-500"><?= (int) ($row['follows_last_24h'] ?? 0); ?>/24h</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $riskClass; ?>"><?= $e($riskLabel); ?></span>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= $e($row['created_at'] ?? ''); ?></td>
                            <td class="px-4 py-3">
                                <?php if ($canModerate): ?>
                                    <?php $followLock = is_array($row['follow_lock'] ?? null) ? $row['follow_lock'] : null; ?>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <form method="post" action="<?= URLROOT; ?>/admin/relationships/remove" onsubmit="return confirm('Gỡ mối quan hệ follow nA�y?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="follower_id" value="<?= (int) ($row['follower_id'] ?? 0); ?>">
                                            <input type="hidden" name="following_id" value="<?= (int) ($row['following_id'] ?? 0); ?>">
                                            <input type="hidden" name="return_q" value="<?= $e($keyword); ?>">
                                            <input type="hidden" name="return_user_id" value="<?= $userId; ?>">
                                            <input type="hidden" name="return_side" value="<?= $e($side); ?>">
                                            <input type="hidden" name="return_risk" value="<?= $e($risk); ?>">
                                            <button type="submit" class="rounded border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Force Unfollow</button>
                                        </form>

                                        <?php if ($followLock !== null): ?>
                                            <span class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                                                �ang kh�a follow
                                            </span>
                                            <form method="post" action="<?= URLROOT; ?>/admin/relationships/lock">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="target_user_id" value="<?= (int) ($row['follower_id'] ?? 0); ?>">
                                                <input type="hidden" name="mode" value="unlock">
                                                <input type="hidden" name="return_q" value="<?= $e($keyword); ?>">
                                                <input type="hidden" name="return_user_id" value="<?= $userId; ?>">
                                                <input type="hidden" name="return_side" value="<?= $e($side); ?>">
                                                <input type="hidden" name="return_risk" value="<?= $e($risk); ?>">
                                                <button type="submit" class="rounded border border-emerald-300 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Mở kh�a</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?= URLROOT; ?>/admin/relationships/lock">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="target_user_id" value="<?= (int) ($row['follower_id'] ?? 0); ?>">
                                                <input type="hidden" name="mode" value="temp">
                                                <input type="hidden" name="lock_days" value="7">
                                                <input type="hidden" name="reason" value="Suspicious follow activity">
                                                <input type="hidden" name="return_q" value="<?= $e($keyword); ?>">
                                                <input type="hidden" name="return_user_id" value="<?= $userId; ?>">
                                                <input type="hidden" name="return_side" value="<?= $e($side); ?>">
                                                <input type="hidden" name="return_risk" value="<?= $e($risk); ?>">
                                                <button type="submit" class="rounded border border-amber-300 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-50">KhA�a 7 ng�y</button>
                                            </form>
                                            <form method="post" action="<?= URLROOT; ?>/admin/relationships/lock">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="target_user_id" value="<?= (int) ($row['follower_id'] ?? 0); ?>">
                                                <input type="hidden" name="mode" value="permanent">
                                                <input type="hidden" name="reason" value="Repeated suspicious follow activity">
                                                <input type="hidden" name="return_q" value="<?= $e($keyword); ?>">
                                                <input type="hidden" name="return_user_id" value="<?= $userId; ?>">
                                                <input type="hidden" name="return_side" value="<?= $e($side); ?>">
                                                <input type="hidden" name="return_risk" value="<?= $e($risk); ?>">
                                                <button type="submit" class="rounded border border-slate-400 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Kh�a vinh vi?n</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Kh�ng cA� quyền xử lA�</span>
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
                <div class="text-slate-500">Trang <?= $page; ?> / <?= $totalPages; ?></div>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?= URLROOT; ?>/admin/relationships?<?= $e($buildQuery($page - 1)); ?>" class="rounded border border-slate-300 px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50">Trước</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= URLROOT; ?>/admin/relationships?<?= $e($buildQuery($page + 1)); ?>" class="rounded border border-slate-300 px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50">Sau</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
