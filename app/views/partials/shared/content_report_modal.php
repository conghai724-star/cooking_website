<?php
$reportModalId = (string) ($reportModalId ?? 'report-modal');
$reportModalTitle = (string) ($reportModalTitle ?? 'B�o c�o n?i dung');
$reportModalAction = (string) ($reportModalAction ?? '');
$reportModalReasonField = (string) ($reportModalReasonField ?? 'reason');
$reportModalDetailsField = (string) ($reportModalDetailsField ?? 'details');
$reportModalSuccessToast = (string) ($reportModalSuccessToast ?? '�� g?i b�o c�o.');
$reportModalErrorToast = (string) ($reportModalErrorToast ?? 'Kh�ng th? g?i b�o c�o l�c n�y.');
$reportModalHiddenFields = is_array($reportModalHiddenFields ?? null) ? $reportModalHiddenFields : [];
$reportModalOtherId = $reportModalId . '-other';
$reportModalSelector = '#' . $reportModalId;
?>
<div id="<?= htmlspecialchars($reportModalId, ENT_QUOTES, 'UTF-8'); ?>" data-modal-overlay class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h3 class="mb-4 text-xl font-black text-slate-800"><?= htmlspecialchars($reportModalTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
        <form method="post" action="<?= htmlspecialchars($reportModalAction, ENT_QUOTES, 'UTF-8'); ?>" data-ajax-form data-close-target="<?= htmlspecialchars($reportModalSelector, ENT_QUOTES, 'UTF-8'); ?>" data-success-toast="<?= htmlspecialchars($reportModalSuccessToast, ENT_QUOTES, 'UTF-8'); ?>" data-error-toast="<?= htmlspecialchars($reportModalErrorToast, ENT_QUOTES, 'UTF-8'); ?>">
            <?= csrf_field(); ?>
            <?php foreach ($reportModalHiddenFields as $fieldName => $fieldValue): ?>
                <input type="hidden" name="<?= htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?= htmlspecialchars((string) $fieldValue, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endforeach; ?>

            <div class="mb-4">
                <label class="mb-2 block text-sm font-semibold text-slate-600">L� do b�o c�o:</label>
                <select name="<?= htmlspecialchars($reportModalReasonField, ENT_QUOTES, 'UTF-8'); ?>" data-report-reason-select data-report-other-target="#<?= htmlspecialchars($reportModalOtherId, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl border border-slate-200 p-3 focus:border-primary focus:ring-primary" required>
                    <option value="">-- Ch?n l� do --</option>
                    <option value="Spam">Spam</option>
                    <option value="N?i dung kh�ng ph� h?p">N?i dung kh�ng ph� h?p</option>
                    <option value="Th�ng tin sai l?ch">Th�ng tin sai l?ch</option>
                    <option value="Kh�c">Kh�c</option>
                </select>
            </div>

            <div id="<?= htmlspecialchars($reportModalOtherId, ENT_QUOTES, 'UTF-8'); ?>" class="mb-4 hidden">
                <label class="mb-2 block text-sm font-semibold text-slate-600">M� t? chi ti?t:</label>
                <textarea name="<?= htmlspecialchars($reportModalDetailsField, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl border border-slate-200 p-3 focus:border-primary focus:ring-primary" rows="3" placeholder="M� t? chi ti?t..."></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" data-modal-close="<?= htmlspecialchars($reportModalSelector, ENT_QUOTES, 'UTF-8'); ?>" class="flex-1 rounded-xl border border-slate-300 px-4 py-2 font-semibold text-slate-600 hover:bg-slate-50">H?y</button>
                <button type="submit" class="flex-1 rounded-xl bg-red-500 px-4 py-2 font-semibold text-white hover:bg-red-600">G?i b�o c�o</button>
            </div>
        </form>
    </div>
</div>
