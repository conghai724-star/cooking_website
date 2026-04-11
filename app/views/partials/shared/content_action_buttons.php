<?php

$actionAuthorId = (int) ($actionAuthorId ?? 0);

$actionAuthorProfileUrl = (string) ($actionAuthorProfileUrl ?? ($actionAuthorId > 0 ? (URLROOT . '/users/' . $actionAuthorId) : ''));

$actionIsLoggedIn = (bool) ($actionIsLoggedIn ?? false);

$actionIsOwner = (bool) ($actionIsOwner ?? false);



$actionEnableFollow = (bool) ($actionEnableFollow ?? false);

$actionFollowAction = (string) ($actionFollowAction ?? '');

$actionIsFollowing = (bool) ($actionIsFollowing ?? false);

$actionFollowText = (string) ($actionFollowText ?? 'Theo dõi');

$actionUnfollowText = (string) ($actionUnfollowText ?? 'Đang theo dõi');



$actionEnableSave = (bool) ($actionEnableSave ?? false);

$actionSaveAction = (string) ($actionSaveAction ?? '');

$actionSaveHiddenFields = is_array($actionSaveHiddenFields ?? null) ? $actionSaveHiddenFields : [];

$actionIsSaved = (bool) ($actionIsSaved ?? false);

$actionSaveLabelOn = (string) ($actionSaveLabelOn ?? 'Đã lưu');

$actionSaveLabelOff = (string) ($actionSaveLabelOff ?? 'Lưu');

$actionSaveSuccessToast = (string) ($actionSaveSuccessToast ?? 'Đã cập nhật lưu nội dung.');

$actionSaveErrorToast = (string) ($actionSaveErrorToast ?? 'Không thể lưu nội dung lúc này.');



$actionEnableShare = (bool) ($actionEnableShare ?? true);

$actionShareText = (string) ($actionShareText ?? 'Xem nội dung này');

$actionShareTitle = (string) ($actionShareTitle ?? '');



$actionEnableReport = (bool) ($actionEnableReport ?? false);

$actionReportMode = (string) ($actionReportMode ?? 'details'); // details|modal

$actionReportTriggerId = (string) ($actionReportTriggerId ?? 'btn-report-trigger');

$actionReportModalTarget = (string) ($actionReportModalTarget ?? '#report-modal');

$actionReportAction = (string) ($actionReportAction ?? '');

$actionReportOtherTargetId = (string) ($actionReportOtherTargetId ?? 'content-report-other');

$actionReportSuccessToast = (string) ($actionReportSuccessToast ?? 'Đã gửi báo cáo.');

$actionReportErrorToast = (string) ($actionReportErrorToast ?? 'Không thể gửi báo cáo lúc này.');

$actionReportReasonField = (string) ($actionReportReasonField ?? 'reason');

$actionReportDetailsField = (string) ($actionReportDetailsField ?? 'details');

$actionReportHiddenFields = is_array($actionReportHiddenFields ?? null) ? $actionReportHiddenFields : [];



$requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');

$requestQuery = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY) ?? '');

$basePath = (string) (parse_url((string) URLROOT, PHP_URL_PATH) ?? '');

if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {

    $requestPath = (string) substr($requestPath, strlen($basePath));

    if ($requestPath === '') {

        $requestPath = '/';

    }

}

if ($requestQuery !== '') {

    $requestPath .= '?' . $requestQuery;

}

?>



<div class="mt-4 flex flex-col gap-2">

    <?php if ($actionAuthorId > 0): ?>

        <a class="inline-flex w-full items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90" href="<?= htmlspecialchars($actionAuthorProfileUrl, ENT_QUOTES, 'UTF-8'); ?>">

            Xem hồ sơ tác giả

        </a>

    <?php endif; ?>



    <?php if ($actionEnableFollow && $actionIsLoggedIn && !$actionIsOwner && $actionFollowAction !== ''): ?>

        <form method="post" action="<?= htmlspecialchars($actionFollowAction, ENT_QUOTES, 'UTF-8'); ?>" data-ajax-form data-on-success="toggle-follow" data-follow-text="<?= htmlspecialchars($actionFollowText, ENT_QUOTES, 'UTF-8'); ?>" data-unfollow-text="<?= htmlspecialchars($actionUnfollowText, ENT_QUOTES, 'UTF-8'); ?>" data-success-toast="Đã cập nhật theo dõi." data-error-toast="Không thể cập nhật theo dõi.">

            <?= csrf_field(); ?>

            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($requestPath, ENT_QUOTES, 'UTF-8'); ?>">

            <button data-follow-btn class="inline-flex w-full items-center justify-center rounded-xl border border-primary px-4 py-2 text-sm font-bold text-primary hover:bg-primary/10" type="submit">

                <?= htmlspecialchars($actionIsFollowing ? $actionUnfollowText : $actionFollowText, ENT_QUOTES, 'UTF-8'); ?>

            </button>

        </form>

    <?php endif; ?>



    <?php if ($actionEnableSave && $actionIsLoggedIn && $actionSaveAction !== ''): ?>

        <form method="post" action="<?= htmlspecialchars($actionSaveAction, ENT_QUOTES, 'UTF-8'); ?>" data-ajax-form data-on-success="toggle-label" data-label-on="<?= htmlspecialchars($actionSaveLabelOn, ENT_QUOTES, 'UTF-8'); ?>" data-label-off="<?= htmlspecialchars($actionSaveLabelOff, ENT_QUOTES, 'UTF-8'); ?>" data-active-class="bg-green-50" data-success-toast="<?= htmlspecialchars($actionSaveSuccessToast, ENT_QUOTES, 'UTF-8'); ?>" data-error-toast="<?= htmlspecialchars($actionSaveErrorToast, ENT_QUOTES, 'UTF-8'); ?>">

            <?= csrf_field(); ?>

            <?php foreach ($actionSaveHiddenFields as $fieldName => $fieldValue): ?>

                <input type="hidden" name="<?= htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?= htmlspecialchars((string) $fieldValue, ENT_QUOTES, 'UTF-8'); ?>">

            <?php endforeach; ?>

            <button type="submit" data-toggle-btn class="inline-flex w-full items-center justify-center rounded-xl border border-green-600 px-4 py-2 text-sm font-bold text-green-600 hover:bg-green-50 <?= $actionIsSaved ? 'bg-green-50' : ''; ?>">

                <?= htmlspecialchars($actionIsSaved ? $actionSaveLabelOn : $actionSaveLabelOff, ENT_QUOTES, 'UTF-8'); ?>

            </button>

        </form>

    <?php endif; ?>



    <?php if ($actionEnableShare): ?>

        <button type="button" data-share-btn data-share-text="<?= htmlspecialchars($actionShareText, ENT_QUOTES, 'UTF-8'); ?>" data-share-title="<?= htmlspecialchars($actionShareTitle, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex w-full items-center justify-center rounded-xl border border-blue-500 px-4 py-2 text-sm font-bold text-blue-500 hover:bg-blue-50">Chia sẻ</button>

    <?php endif; ?>



    <?php if ($actionEnableReport && $actionIsLoggedIn && !$actionIsOwner): ?>

        <?php if ($actionReportMode === 'modal'): ?>

            <button id="<?= htmlspecialchars($actionReportTriggerId, ENT_QUOTES, 'UTF-8'); ?>" data-modal-open="<?= htmlspecialchars($actionReportModalTarget, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex w-full items-center justify-center rounded-xl border border-red-500 px-4 py-2 text-sm font-bold text-red-500 hover:bg-red-50" type="button">Báo cáo</button>

        <?php else: ?>

            <details>

                <summary class="inline-flex w-full cursor-pointer list-none items-center justify-center rounded-xl border border-red-500 px-4 py-2 text-sm font-bold text-red-500 hover:bg-red-50">Báo cáo</summary>

                <?php

                $reportAction = $actionReportAction;

                $reportOtherTargetId = $actionReportOtherTargetId;

                $reportSuccessToast = $actionReportSuccessToast;

                $reportErrorToast = $actionReportErrorToast;

                $reportReasonField = $actionReportReasonField;

                $reportDetailsField = $actionReportDetailsField;

                $reportHiddenFields = $actionReportHiddenFields;

                require APPROOT . '/app/views/partials/shared/report_content_form.php';

                ?>

            </details>

        <?php endif; ?>

    <?php endif; ?>

</div>



