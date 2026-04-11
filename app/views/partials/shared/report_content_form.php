<?php
$reportAction = (string) ($reportAction ?? '');
$reportReasonField = (string) ($reportReasonField ?? 'reason');
$reportDetailsField = (string) ($reportDetailsField ?? 'details');
$reportOtherTargetId = (string) ($reportOtherTargetId ?? 'content-report-other');
$reportSuccessToast = (string) ($reportSuccessToast ?? 'Aďż˝ÂżÂ˝A? g?i bA?o cA?o.');
$reportErrorToast = (string) ($reportErrorToast ?? 'KhAďż˝ng thAďż˝Â»Ć’ gAďż˝Â»Â­i bÄ‚Âˇo cÄ‚Âˇo lÄ‚Âşc nÄ‚Â y.');
$reportHiddenFields = is_array($reportHiddenFields ?? null) ? $reportHiddenFields : [];
?>
<form method="post" action="<?= htmlspecialchars($reportAction, ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 space-y-2 rounded-lg border border-slate-200 p-3" data-ajax-form data-success-toast="<?= htmlspecialchars($reportSuccessToast, ENT_QUOTES, 'UTF-8'); ?>" data-error-toast="<?= htmlspecialchars($reportErrorToast, ENT_QUOTES, 'UTF-8'); ?>">
    <?= csrf_field(); ?>
    <?php foreach ($reportHiddenFields as $fieldName => $fieldValue): ?>
        <input type="hidden" name="<?= htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?= htmlspecialchars((string) $fieldValue, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>

    <select name="<?= htmlspecialchars($reportReasonField, ENT_QUOTES, 'UTF-8'); ?>" data-report-reason-select data-report-other-target="#<?= htmlspecialchars($reportOtherTargetId, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
        <option value="NAďż˝Â»â„˘i dung khÄ‚Â´ng phÄ‚Âą hAďż˝Â»ÂŁp">NAďż˝Â»â„˘i dung khÄ‚Â´ng phÄ‚Âą hAďż˝Â»ÂŁp</option>
        <option value="Sai thÄ‚ďż˝??ďż˝â‚¬ďż˝?Ä‚ďż˝??ďż˝Â´ng tin">Sai thÄ‚ďż˝??ďż˝â‚¬ďż˝?Ä‚ďż˝??ďż˝Â´ng tin</option>
        <option value="Spam hoAďż˝ÂşÂ·c quAďż˝ÂşÂŁng cÄ‚Âˇo">Spam hoAďż˝ÂşÂ·c quAďż˝ÂşÂŁng cÄ‚Âˇo</option>
        <option value="KhÄ‚ďż˝??ďż˝â‚¬ďż˝?Ä‚ďż˝??ďż˝Âˇc">KhÄ‚ďż˝??ďż˝â‚¬ďż˝?Ä‚ďż˝??ďż˝Âˇc</option>
    </select>

    <input id="<?= htmlspecialchars($reportOtherTargetId, ENT_QUOTES, 'UTF-8'); ?>" type="text" name="<?= htmlspecialchars($reportDetailsField, ENT_QUOTES, 'UTF-8'); ?>" class="hidden w-full rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="Chi tiAďż˝ÂşÂżt thÄ‚ÂŞm (nAďż˝ÂşÂżu chAďż˝Â»ÂŤn KhÄ‚Âˇc)">
    <button type="submit" class="w-full rounded bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-600">GAďż˝Â»Â­i bÄ‚Âˇo cÄ‚Âˇo</button>
</form>

