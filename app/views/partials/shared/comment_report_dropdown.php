<?php
$reportCommentId = (int) ($reportCommentId ?? 0);
$reportContentType = (string) ($reportContentType ?? 'recipe');
$reportSuccessToast = (string) ($reportSuccessToast ?? '�� g?i b�o c�o b�nh lu?n.');
$reportErrorToast = (string) ($reportErrorToast ?? 'Kh�ng th? g?i b�o c�o b�nh lu?n.');
$reportOtherId = 'comment-report-other-' . $reportContentType . '-' . $reportCommentId;
$reportHiddenFields = is_array($reportHiddenFields ?? null) ? $reportHiddenFields : [];
?>
<details class="relative">
    <summary class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
        <span class="material-symbols-outlined text-base">more_horiz</span>
    </summary>
    <div class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
        <p class="mb-2 text-xs font-semibold text-red-600">B�o c�o b�nh lu?n</p>
        <form method="post" action="<?= URLROOT; ?>/comments/<?= $reportCommentId; ?>/report" class="space-y-2" data-ajax-form data-success-toast="<?= htmlspecialchars($reportSuccessToast, ENT_QUOTES, 'UTF-8'); ?>" data-error-toast="<?= htmlspecialchars($reportErrorToast, ENT_QUOTES, 'UTF-8'); ?>" data-success-button-text="�� g?i t? c�o">
            <?= csrf_field(); ?>
            <input type="hidden" name="content_type" value="<?= htmlspecialchars($reportContentType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php foreach ($reportHiddenFields as $fieldName => $fieldValue): ?>
                <input type="hidden" name="<?= htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?= htmlspecialchars((string) $fieldValue, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endforeach; ?>
            <select class="w-full rounded-lg border border-red-200 bg-white px-2 py-1.5 text-xs" name="reason" data-report-reason-select data-report-other-target="#<?= htmlspecialchars($reportOtherId, ENT_QUOTES, 'UTF-8'); ?>" required>
                <option value="N?i dung kh�ng ph� h?p">N?i dung kh�ng ph� h?p</option>
                <option value="Qu?y r?i / c�ng k�ch c� nh�n">Qu?y r?i / c�ng k�ch c� nh�n</option>
                <option value="Spam">Spam</option>
                <option value="Th�ng tin sai l?ch">Th�ng tin sai l?ch</option>
                <option value="Kh�c">Kh�c</option>
            </select>
            <textarea id="<?= htmlspecialchars($reportOtherId, ENT_QUOTES, 'UTF-8'); ?>" class="hidden w-full rounded-lg border border-red-200 bg-white px-2 py-1.5 text-xs" name="reason_other" rows="2" placeholder="M� t? th�m (n?u ch?n Kh�c)"></textarea>
            <div class="flex justify-end">
                <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100" type="submit">G?i t? c�o</button>
            </div>
        </form>
    </div>
</details>
