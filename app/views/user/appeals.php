<?php
$targets = is_array($targets ?? null) ? $targets : [];
$appeals = is_array($appeals ?? null) ? $appeals : [];
$notice = (string) ($notice ?? '');

$noticeText = match ($notice) {
    'appeal_submitted' => 'A�¿½A? g?i khi?u n?i thA?nh cA?ng.',
    'appeal_exists' => 'BA�º¡n A�‘Ă£ cĂ³ khiA�º¿u nA�º¡i A�‘ang chA�» cho quyA�º¿t A�‘A�»‹nh nĂ y.',
    'appeal_target_not_found' => 'KhA�ng tĂ¬m thA�º¥y quyA�º¿t A�‘A�»‹nh A�‘ang hiA�»‡u lA�»±c A�‘A�»ƒ khiA�º¿u nA�º¡i.',
    'appeal_invalid' => 'DA�»¯ liA�»‡u khiA�º¿u nA�º¡i khĂ´ng hA�»£p lA�»‡.',
    'appeal_failed' => 'KhA�ng thA�»ƒ gA�»­i khiA�º¿u nA�º¡i. Vui lĂ²ng thA�»­ lA�º¡i.',
    default => '',
};
?>

<section class="w-full">
    <div class="mx-auto w-full max-w-[960px] px-2 py-4 sm:px-4 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">KhiA�º¿u nA�º¡i quyA�»n bA�»‹ khA�a</h1>
            <p class="text-sm text-slate-500">GA�»­i khiA�º¿u nA�º¡i khi bA�º¡n bA�»‹ ban hoA�º·c bA�»‹ khA�a mA�»™t sA�»‘ quyA�»n.</p>
        </div>

        <?php if ($noticeText !== ''): ?>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-base font-semibold text-slate-900">GA�»­i khiA�º¿u nA�º¡i mA�»›i</h2>
            <?php if ($targets === []): ?>
                <p class="text-sm text-slate-500">HiA�»‡n khĂ´ng cĂ³ quyA�º¿t A�‘A�»‹nh khA�a nĂ o cĂ²n hiA�»‡u lA�»±c A�‘A�»ƒ khiA�º¿u nA�º¡i.</p>
            <?php else: ?>
                <form method="post" action="<?= URLROOT; ?>/appeals" class="space-y-3">
                    <?= csrf_field(); ?>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">QuyA�º¿t A�‘A�»‹nh bA�»‹ khiA�º¿u nA�º¡i</label>
                        <select name="target_type_target_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                            <option value="">ChA�»n quyA�º¿t A�‘A�»‹nh</option>
                            <?php foreach ($targets as $target): ?>
                                <?php
                                $targetType = (string) ($target['target_type'] ?? '');
                                $targetId = (int) ($target['target_id'] ?? 0);
                                $label = (string) ($target['label'] ?? '');
                                $reason = (string) ($target['reason'] ?? '');
                                ?>
                                <option value="<?= htmlspecialchars($targetType . ':' . $targetId, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($label . ' #' . $targetId . ($reason !== '' ? ' - ' . $reason : ''), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">LĂ½ do khiA�º¿u nA�º¡i</label>
                        <textarea name="appeal_reason" rows="4" maxlength="2000" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="NĂªu lĂ½ do bA�º¡n cho rA�º±ng quyA�º¿t A�‘A�»‹nh chA�°a phĂ¹ hA�»£p..."></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">BA�º±ng chA�»©ng bA�»• sung (khĂ´ng bA�º¯t buA�»™c)</label>
                        <textarea name="evidence_text" rows="3" maxlength="4000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Link, mĂ´ tA�º£ ngA�»¯ cA�º£nh, thĂ´ng tin bA�»• sung..."></textarea>
                    </div>
                    <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">GA�»­i khiA�º¿u nA�º¡i</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-base font-semibold text-slate-900">LA�»‹ch sA�»­ khiA�º¿u nA�º¡i</h2>
            <?php if ($appeals === []): ?>
                <p class="text-sm text-slate-500">BA�º¡n chA�°a gA�»­i khiA�º¿u nA�º¡i nĂ o.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($appeals as $row): ?>
                        <article class="rounded-lg border border-slate-200 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm font-semibold text-slate-900">
                                    <?= htmlspecialchars((string) ($row['target_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    #<?= (int) ($row['target_id'] ?? 0); ?>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                    <?= htmlspecialchars((string) ($row['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-700"><?= htmlspecialchars((string) ($row['appeal_reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php if (trim((string) ($row['admin_note'] ?? '')) !== ''): ?>
                                <p class="mt-2 text-xs text-slate-500">
                                    PhA�º£n hA�»“i admin:
                                    <?= htmlspecialchars((string) ($row['admin_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            <?php endif; ?>
                            <p class="mt-1 text-xs text-slate-400">GA�»­i lĂºc: <?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

