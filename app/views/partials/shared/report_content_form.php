<?php
$reportAction = (string) ($reportAction ?? '');
$reportReasonField = (string) ($reportReasonField ?? 'reason');
$reportDetailsField = (string) ($reportDetailsField ?? 'details');
$reportOtherTargetId = (string) ($reportOtherTargetId ?? 'content-report-other');
$reportSuccessToast = (string) ($reportSuccessToast ?? '�� g?i b�o c�o.');
$reportErrorToast = (string) ($reportErrorToast ?? 'Kh�ng th? g?i b�o c�o l�c n�y.');
$reportHiddenFields = is_array($reportHiddenFields ?? null) ? $reportHiddenFields : [];
?>
<form method="post" action="<?= htmlspecialchars($reportAction, ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 space-y-2 rounded-lg border border-slate-200 p-3" data-ajax-form data-success-toast="<?= htmlspecialchars($reportSuccessToast, ENT_QUOTES, 'UTF-8'); ?>" data-error-toast="<?= htmlspecialchars($reportErrorToast, ENT_QUOTES, 'UTF-8'); ?>">
    <?= csrf_field(); ?>
    <?php foreach ($reportHiddenFields as $fieldName => $fieldValue): ?>
        <input type="hidden" name="<?= htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?= htmlspecialchars((string) $fieldValue, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>

    <select name="<?= htmlspecialchars($reportReasonField, ENT_QUOTES, 'UTF-8'); ?>" data-report-reason-select data-report-other-target="#<?= htmlspecialchars($reportOtherTargetId, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
        <option value="N?i dung kh�ng ph� h?p">N?i dung kh�ng ph� h?p</option>
        <option value="Sai th�ng tin">Sai th�ng tin</option>
        <option value="Spam ho?c qu?ng c�o">Spam ho?c qu?ng c�o</option>
        <option value="Kh�c">Kh�c</option>
    </select>

    <input id="<?= htmlspecialchars($reportOtherTargetId, ENT_QUOTES, 'UTF-8'); ?>" type="text" name="<?= htmlspecialchars($reportDetailsField, ENT_QUOTES, 'UTF-8'); ?>" class="hidden w-full rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="Chi ti?t th�m (n?u ch?n Kh�c)">
    <button type="submit" class="w-full rounded bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-600">G?i b�o c�o</button>
</form>
