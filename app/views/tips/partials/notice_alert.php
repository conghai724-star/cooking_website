<?php $noticeText = (string) ($noticeText ?? ''); ?>
<?php if ($noticeText !== ''): ?>
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
        <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>
