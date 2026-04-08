<?php $forbiddenReason = (string) ($forbidden_reason ?? ''); ?>
<section class="w-full">
    <div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6">
        <h1 class="text-2xl font-black text-slate-900">Không thể xem kế hoạch bữa ăn</h1>
        <?php if ($forbiddenReason === 'block'): ?>
            <p class="mt-3 text-sm text-slate-600">
                Hai bên đã chặn lẫn nhau hoặc một bên đã chặn bên kia. Kế hoạch bữa ăn không được hiển thị.
            </p>
        <?php else: ?>
            <p class="mt-3 text-sm text-slate-600">
                Ke hoach bua an cua <?= htmlspecialchars((string) (($owner['name'] ?? $owner['username'] ?? 'người dùng')), ENT_QUOTES, 'UTF-8'); ?>
                dang de o che do
                <?php
                $label = match ((string) ($visibility ?? 'private')) {
                    'public' => 'Công khai',
                    'followers' => 'Người theo dõi',
                    'friends' => 'Bạn bè',
                    'link' => 'Qua link',
                    default => 'Riêng tư',
                };
                ?>
                <strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></strong>.
            </p>
        <?php endif; ?>
        <a class="mt-4 inline-block rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white" href="<?= URLROOT; ?>/">Về trang chủ</a>
    </div>
</section>
