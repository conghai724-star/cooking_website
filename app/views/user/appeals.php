<?php
$targets = is_array($targets ?? null) ? $targets : [];
$appeals = is_array($appeals ?? null) ? $appeals : [];
$notice = (string) ($notice ?? '');

$noticeText = match ($notice) {
    'appeal_submitted' => 'Đã gửi khiếu nại thành công.',
    'appeal_exists' => 'Bạn đã có khiếu nại đang chờ cho quyết định này.',
    'appeal_target_not_found' => 'Không tìm thấy quyết định đang hiệu lực để khiếu nại.',
    'appeal_invalid' => 'Dữ liệu khiếu nại không hợp lệ.',
    'appeal_failed' => 'Không thể gửi khiếu nại. Vui lòng thử lại.',
    default => '',
};
?>

<section class="w-full">
    <div class="mx-auto w-full max-w-[960px] px-2 py-4 sm:px-4 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Khiếu nại quyền bị khóa</h1>
            <p class="text-sm text-slate-500">Gửi khiếu nại khi bạn bị ban hoặc bị khóa một số quyền.</p>
        </div>

        <?php if ($noticeText !== ''): ?>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-base font-semibold text-slate-900">Gửi khiếu nại mới</h2>
            <?php if ($targets === []): ?>
                <p class="text-sm text-slate-500">Hiện không có quyết định khóa nào còn hiệu lực để khiếu nại.</p>
            <?php else: ?>
                <form method="post" action="<?= URLROOT; ?>/appeals" class="space-y-3">
                    <?= csrf_field(); ?>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Quyết định bị khiếu nại</label>
                        <select name="target_type_target_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                            <option value="">Chọn quyết định</option>
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
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Lý do khiếu nại</label>
                        <textarea name="appeal_reason" rows="4" maxlength="2000" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Nêu lý do bạn cho rằng quyết định chưa phù hợp..."></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Bằng chứng bổ sung (không bắt buộc)</label>
                        <textarea name="evidence_text" rows="3" maxlength="4000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Link, mô tả ngữ cảnh, thông tin bổ sung..."></textarea>
                    </div>
                    <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Gửi khiếu nại</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-base font-semibold text-slate-900">Lịch sử khiếu nại</h2>
            <?php if ($appeals === []): ?>
                <p class="text-sm text-slate-500">Bạn chưa gửi khiếu nại nào.</p>
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
                                    Phản hồi admin:
                                    <?= htmlspecialchars((string) ($row['admin_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            <?php endif; ?>
                            <p class="mt-1 text-xs text-slate-400">Gửi lúc: <?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
