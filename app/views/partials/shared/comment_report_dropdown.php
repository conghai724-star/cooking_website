<?php
$reportCommentId = (int) ($reportCommentId ?? 0);
$reportContentType = (string) ($reportContentType ?? 'recipe');
$reportSuccessToast = (string) ($reportSuccessToast ?? 'Đã gửi báo cáo bình luận.');
$reportErrorToast = (string) ($reportErrorToast ?? 'Không thể gửi báo cáo bình luận.');
$reportOtherId = 'comment-report-other-' . $reportContentType . '-' . $reportCommentId;
$reportHiddenFields = is_array($reportHiddenFields ?? null) ? $reportHiddenFields : [];
?>
<details class="relative">
    <summary class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
        <span class="material-symbols-outlined text-base">more_horiz</span>
    </summary>
    <div class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
        <p class="mb-2 text-xs font-semibold text-red-600">Báo cáo bình luận</p>
        <form method="post" action="<?= URLROOT; ?>/comments/<?= $reportCommentId; ?>/report" class="space-y-2" data-ajax-form data-success-toast="<?= htmlspecialchars($reportSuccessToast, ENT_QUOTES, 'UTF-8'); ?>" data-error-toast="<?= htmlspecialchars($reportErrorToast, ENT_QUOTES, 'UTF-8'); ?>" data-success-button-text="Đã gửi tố cáo">
            <?= csrf_field(); ?>
            <input type="hidden" name="content_type" value="<?= htmlspecialchars($reportContentType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php foreach ($reportHiddenFields as $fieldName => $fieldValue): ?>
                <input type="hidden" name="<?= htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?= htmlspecialchars((string) $fieldValue, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endforeach; ?>
            <select class="w-full rounded-lg border border-red-200 bg-white px-2 py-1.5 text-xs" name="reason" data-report-reason-select data-report-other-target="#<?= htmlspecialchars($reportOtherId, ENT_QUOTES, 'UTF-8'); ?>" required>
                <option value="Nội dung không phù hợp">Nội dung không phù hợp</option>
                <option value="Quấy rối / công kích cá nhân">Quấy rối / công kích cá nhân</option>
                <option value="Spam">Spam</option>
                <option value="Thông tin sai lệch">Thông tin sai lệch</option>
                <option value="Khác">Khác</option>
            </select>
            <textarea id="<?= htmlspecialchars($reportOtherId, ENT_QUOTES, 'UTF-8'); ?>" class="hidden w-full rounded-lg border border-red-200 bg-white px-2 py-1.5 text-xs" name="reason_other" rows="2" placeholder="Mô tả thêm (nếu chọn Khác)"></textarea>
            <div class="flex justify-end">
                <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100" type="submit">Gửi tố cáo</button>
            </div>
        </form>
    </div>
</details>
