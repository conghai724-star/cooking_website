<?php
$campaigns = is_array($campaigns ?? null) ? $campaigns : [];
$notice = (string) ($notice ?? '');

$noticeMap = [
    'sent' => 'A�¿½A? g?i thA?ng bA?o thA?nh cA?ng.',
    'invalid_payload' => 'Vui lĂ²ng nhA�º­p tiĂªu A�‘A�» vĂ  nA�»™i dung.',
    'send_failed' => 'KhA�ng thA�»ƒ gA�»­i thĂ´ng bĂ¡o. Vui lĂ²ng thA�»­ lA�º¡i.',
    'no_recipients' => 'KhA�ng tĂ¬m thA�º¥y ngA�°A�»i nhA�º­n hA�»£p lA�»‡.',
];
$noticeText = $noticeMap[$notice] ?? '';
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">QuA�º£n lĂ½ thĂ´ng bĂ¡o</h1>
        <p class="text-sm text-slate-500">TA�º¡o vĂ  gA�»­i thĂ´ng bĂ¡o hA�»‡ thA�»‘ng tA�»›i ngA�°A�»i dĂ¹ng.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="mb-4 font-semibold text-slate-800">TA�º¡o thĂ´ng bĂ¡o hA�»‡ thA�»‘ng</h3>
        <form method="post" action="<?= URLROOT; ?>/admin/notifications/send" class="grid grid-cols-1 gap-4">
            <?= csrf_field(); ?>
            <input type="text" name="title" required maxlength="255" placeholder="TiĂªu A�‘A�» thĂ´ng bĂ¡o" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <textarea name="message" required rows="4" maxlength="2000" placeholder="NA�»™i dung thĂ´ng bĂ¡o" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            <input type="text" name="action_url" maxlength="255" placeholder="Link mA�»Ÿ khi bA�º¥m thĂ´ng bĂ¡o (vd: /meal-plans)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <select name="target_scope" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="all">ToĂ n bA�»™ user active</option>
                    <option value="role">Theo vai trÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â²</option>
                    <option value="users">Theo danh sÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡ch email</option>
                </select>
                <select name="target_value" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">(DĂ¹ng khi chA�»n scope=role)</option>
                    <option value="user">user</option>
                    <option value="support">support</option>
                    <option value="mod">mod</option>
                    <option value="super_admin">super_admin</option>
                </select>
                <input type="text" name="user_list" placeholder="Email cĂ¡ch nhau bA�»Ÿi dA�º¥u phA�º©y" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">GA�»­i thĂ´ng bĂ¡o</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-6 py-4">
            <h3 class="font-semibold text-slate-800">LA�»‹ch sA�»­ chiA�º¿n dA�»‹ch gA�»­i</h3>
        </div>

        <?php if ($campaigns === []): ?>
            <div class="p-6 text-sm text-slate-500">ChA�°a cĂ³ A�‘A�»£t gA�»­i nĂ o.</div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($campaigns as $c): ?>
                    <div class="p-6">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($c['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">ĐA� gửi: <?= (int) ($c['sent_count'] ?? 0); ?></span>
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


