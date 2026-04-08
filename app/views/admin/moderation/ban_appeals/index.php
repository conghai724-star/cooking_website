<?php
$rows = is_array($rows ?? null) ? $rows : [];
$status = (string) ($status ?? '');
$keyword = (string) ($keyword ?? '');
$notice = (string) ($notice ?? '');

$noticeText = match ($notice) {
    'reviewed' => 'A�¿½A? c?p nh?t tr?ng thA?i khi?u n?i.',
    'review_failed' => 'KhA�ng thA�»ƒ xA�»­ lĂ½ khiA�º¿u nA�º¡i. Vui lĂ²ng thA�»­ lA�º¡i.',
    default => '',
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Khiếu nại ban/quyA�»n</h1>
        <p class="text-sm text-slate-500">Theo dĂµi vĂ  xA�»­ lĂ½ khiA�º¿u nA�º¡i tA�»« ngA�°A�»i dĂ¹ng.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <form method="get" action="<?= URLROOT; ?>/admin/ban-appeals" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" placeholder="TĂ¬m user, email, nA�»™i dung" class="w-80 max-w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="" <?= $status === '' ? 'selected' : ''; ?>>TA�º¥t cA�º£ trA�º¡ng thĂ¡i</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>pending</option>
                <option value="reviewing" <?= $status === 'reviewing' ? 'selected' : ''; ?>>reviewing</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : ''; ?>>approved</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : ''; ?>>rejected</option>
            </select>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">LA�»c</button>
        </form>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="font-semibold text-slate-900">Danh sách (<?= count($rows); ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">NgA�°A�»i dĂ¹ng</th>
                    <th class="px-4 py-3 font-semibold">MA�»¥c tiĂªu</th>
                    <th class="px-4 py-3 font-semibold">Lý do khiA�º¿u nA�º¡i</th>
                    <th class="px-4 py-3 font-semibold">TrA�º¡ng thĂ¡i</th>
                    <th class="px-4 py-3 font-semibold">Hành động</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">KhA�ng cĂ³ khiA�º¿u nA�º¡i.</td>
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
                                    <select name="decision" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                        <option value="reviewing">reviewing</option>
                                        <option value="approved">approved</option>
                                        <option value="rejected">rejected</option>
                                    </select>
                                    <textarea name="admin_note" rows="2" class="w-full rounded border border-slate-300 px-2 py-1 text-xs" placeholder="Ghi chĂº cho user (khĂ´ng bA�º¯t buA�»™c)"></textarea>
                                    <button type="submit" class="rounded border border-sky-300 px-2 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-50">CA�º­p nhA�º­t</button>
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


