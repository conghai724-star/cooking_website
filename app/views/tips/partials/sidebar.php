<?php
$sidebarCategory = (string) ($sidebarCategory ?? 'ChA�°a phĂ¢n loA�º¡i');
$sidebarViews = (int) ($sidebarViews ?? 0);
$sidebarAuthor = (string) ($sidebarAuthor ?? 'KhÄ‚Â´ng rĂ„â€Ă‚Âµ');
$sidebarAuthorId = (int) ($sidebarAuthorId ?? 0);
$sidebarIsFollowing = (bool) ($sidebarIsFollowing ?? false);
$sidebarIsSaved = (bool) ($sidebarIsSaved ?? false);
$sidebarTipId = (int) ($sidebarTipId ?? 0);
$sidebarTipPath = (string) ($sidebarTipPath ?? '/tips');
$sidebarTitle = (string) ($sidebarTitle ?? 'MA�º¹o vA�º·t');

$quickInfoTitle = 'ThĂ„â€Ă‚Â´ng tin nhanh';
$quickInfoItems = [
    ['label' => 'Danh mục', 'value' => $sidebarCategory !== '' ? $sidebarCategory : 'ChA�°a phĂ¢n loA�º¡i'],
    ['label' => 'LA�°A�»£t xem', 'value' => (string) $sidebarViews],
    ['label' => 'TĂ¡c giA�º£', 'value' => $sidebarAuthor],
];

$quickInfoShowActions = true;
$quickInfoActionsContainerId = 'tip-action-box';

$actionAuthorId = $sidebarAuthorId;
$actionIsLoggedIn = is_logged_in();
$actionIsOwner = $actionIsLoggedIn && (int) current_user_id() === $sidebarAuthorId;
$actionEnableFollow = function_exists('user_has_permission') && user_has_permission('user.follow.manage');
$actionFollowAction = URLROOT . '/users/' . $sidebarAuthorId . '/' . ($sidebarIsFollowing ? 'unfollow' : 'follow');
$actionIsFollowing = $sidebarIsFollowing;

$actionEnableSave = function_exists('user_has_permission') && user_has_permission('user.tips.save');
$actionSaveAction = URLROOT . '/tips/save';
$actionSaveHiddenFields = [
    'tip_id' => (string) $sidebarTipId,
    'redirect_to' => $sidebarTipPath,
];
$actionIsSaved = $sidebarIsSaved;
$actionSaveLabelOn = 'ĐA� lưu mẹo';
$actionSaveLabelOff = 'LA�°u mA�º¹o';
$actionSaveSuccessToast = 'ĐA� cập nhật lưu mẹo vặt.';
$actionSaveErrorToast = 'KhA�ng thA�»ƒ lA�°u mA�º¹o vA�º·t lĂºc nĂ y.';

$actionEnableShare = true;
$actionShareText = 'Xem mA�º¹o vA�º·t nĂ y';
$actionShareTitle = $sidebarTitle;

$actionEnableReport = function_exists('user_has_permission') && user_has_permission('user.tips.report');
$actionReportMode = 'modal';
$actionReportModalTarget = '#tip-report-modal';
$actionReportTriggerId = 'btn-tip-report-trigger';
$actionReportAction = URLROOT . '/tips/' . $sidebarTipId . '/report';
$actionReportOtherTargetId = 'tip-report-other';
$actionReportSuccessToast = 'A�¿½A? g?i bA?o cA?o m?o v?t.';
$actionReportErrorToast = 'KhA�ng thA�»ƒ gA�»­i bĂ¡o cĂ¡o mA�º¹o vA�º·t.';
$actionReportHiddenFields = ['redirect_to' => $sidebarTipPath];
?>

<aside class="detail-side space-y-6" >
    <?php require APPROOT . '/app/views/partials/shared/quick_info_actions_card.php'; ?>
</aside>
