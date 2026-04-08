<?php
$campaigns = is_array($campaigns ?? null) ? $campaigns : [];
$notice = (string) ($notice ?? '');
$noticeText = match ($notice) {
    'sent' => 'Da gui thong bao he thong.',
    'invalid_payload' => 'Vui l�ng nh?p ti�u d? v� n?i dung.',
    'invalid_scope' => 'Doi tuong gui khong hop le.',
    'no_recipients' => 'Kh�ng t�m th?y ngu?i nh?n h?p l?.',
    default => '',
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lA� thA�ng bA�o</h1>
        <p class="text-sm text-slate-500">Tạo vA� gửi thA�ng bA�o hệ thống tới người dA�ng.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h3 class="mb-4 font-semibold text-slate-800">Tạo thA�ng bA�o hệ thống</h3>
        <form method="post" action="<?= URLROOT; ?>/admin/notifications/send" class="grid grid-cols-1 gap-4">
            <?= csrf_field(); ?>
            <input type="text" name="title" required maxlength="255" placeholder="Ti�u d? th�ng b�o" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <textarea name="message" required rows="4" maxlength="2000" placeholder="Nội dung thA�ng bA�o" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            <input type="text" name="action_url" maxlength="255" placeholder="Link mở khi bấm thA�ng bA�o (vd: /meal-plans)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <select name="scope" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="all">ToA�n bộ user active</option>
                    <option value="role">Theo role</option>
                    <option value="users">Theo email cụ thể</option>
                </select>
                <select name="role" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">(DA�ng khi chọn scope=role)</option>
                    <option value="user">user</option>
                    <option value="support">support</option>
                    <option value="mod">mod</option>
                    <option value="super_admin">super_admin</option>
                </select>
                <input type="text" name="user_list" placeholder="Email cA�ch nhau bởi dấu phẩy" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Gửi thA�ng bA�o</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Lịch sử gửi</h3>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if ($campaigns === []): ?>
                <div class="p-6 text-sm text-slate-500">Chua c� d?t g?i n�o.</div>
            <?php else: ?>
                <?php foreach ($campaigns as $c): ?>
                    <div class="p-6 flex flex-col gap-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($c['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">�� g?i: <?= (int) ($c['sent_count'] ?? 0); ?></span>
                        </div>
                        <p class="text-sm text-slate-700"><?= htmlspecialchars((string) ($c['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-xs text-slate-500">
                            Scope: <?= htmlspecialchars((string) ($c['target_scope'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($c['target_value'])): ?> | Target: <?= htmlspecialchars((string) $c['target_value'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            | By: <?= htmlspecialchars((string) ($c['created_by_name'] ?? $c['created_by_email'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                            | At: <?= htmlspecialchars((string) ($c['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

