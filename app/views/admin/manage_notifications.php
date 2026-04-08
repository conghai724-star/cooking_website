<?php
$campaigns = is_array($campaigns ?? null) ? $campaigns : [];
$notice = (string) ($notice ?? '');

$noticeMap = [
    'sent' => 'Đã gửi thông báo thành công.',
    'invalid_payload' => 'Vui lòng nhập tiêu đề và nội dung.',
    'send_failed' => 'Không thể gửi thông báo. Vui lòng thử lại.',
    'no_recipients' => 'Không tìm thấy người nhận hợp lệ.',
];
$noticeText = $noticeMap[$notice] ?? '';
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lý thông báo</h1>
        <p class="text-sm text-slate-500">Tạo và gửi thông báo hệ thống tới người dùng.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="mb-4 font-semibold text-slate-800">Tạo thông báo hệ thống</h3>
        <form method="post" action="<?= URLROOT; ?>/admin/notifications/send" class="grid grid-cols-1 gap-4">
            <?= csrf_field(); ?>
            <input type="text" name="title" required maxlength="255" placeholder="Tiêu đề thông báo" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <textarea name="message" required rows="4" maxlength="2000" placeholder="Nội dung thông báo" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            <input type="text" name="action_url" maxlength="255" placeholder="Link mở khi bấm thông báo (vd: /meal-plans)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <select name="target_scope" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="all">Toàn bộ user active</option>
                    <option value="role">Theo vai trò</option>
                    <option value="users">Theo danh sách email</option>
                </select>
                <select name="target_value" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">(Dùng khi chọn scope=role)</option>
                    <option value="user">user</option>
                    <option value="support">support</option>
                    <option value="mod">mod</option>
                    <option value="super_admin">super_admin</option>
                </select>
                <input type="text" name="user_list" placeholder="Email cách nhau bởi dấu phẩy" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Gửi thông báo</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-6 py-4">
            <h3 class="font-semibold text-slate-800">Lịch sử chiến dịch gửi</h3>
        </div>

        <?php if ($campaigns === []): ?>
            <div class="p-6 text-sm text-slate-500">Chưa có đợt gửi nào.</div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($campaigns as $c): ?>
                    <div class="p-6">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($c['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Đã gửi: <?= (int) ($c['sent_count'] ?? 0); ?></span>
                        </div>
                        <p class="text-sm text-slate-700"><?= htmlspecialchars((string) ($c['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="mt-2 text-xs text-slate-500">
                            Scope: <?= htmlspecialchars((string) ($c['target_scope'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($c['target_value'])): ?> | Target: <?= htmlspecialchars((string) $c['target_value'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            | By: <?= htmlspecialchars((string) ($c['created_by_name'] ?? $c['created_by_email'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                            | At: <?= htmlspecialchars((string) ($c['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
