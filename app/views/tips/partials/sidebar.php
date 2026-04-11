<?php
$sidebarCategory = (string) ($sidebarCategory ?? 'Chưa phân loại');
$sidebarViews = (int) ($sidebarViews ?? 0);
$sidebarAuthor = (string) ($sidebarAuthor ?? 'Không rõ');
$sidebarAuthorId = (int) ($sidebarAuthorId ?? 0);
$sidebarIsFollowing = (bool) ($sidebarIsFollowing ?? false);
$sidebarIsSaved = (bool) ($sidebarIsSaved ?? false);
$sidebarTipId = (int) ($sidebarTipId ?? 0);
$sidebarTipPath = (string) ($sidebarTipPath ?? '/tips');
$sidebarTitle = (string) ($sidebarTitle ?? 'Mẹo vặt');

$quickInfoTitle = 'Thông tin nhanh';
$quickInfoCategory = $sidebarCategory !== '' ? $sidebarCategory : 'Chưa phân loại';
$quickInfoViews = $sidebarViews;
$quickInfoAuthor = $sidebarAuthor;

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
$actionSaveLabelOn = 'Đã lưu mẹo';
$actionSaveLabelOff = 'Lưu mẹo';
$actionSaveSuccessToast = 'Đã cập nhật lưu mẹo vặt.';
$actionSaveErrorToast = 'Không thể lưu mẹo vặt lúc này.';

$actionEnableShare = true;
$actionShareText = 'Xem mẹo vặt này';
$actionShareTitle = $sidebarTitle;

$actionEnableReport = function_exists('user_has_permission') && user_has_permission('user.tips.report');
$actionReportMode = 'modal';
$actionReportModalTarget = '#tip-report-modal';
$actionReportTriggerId = 'btn-tip-report-trigger';
$actionReportAction = URLROOT . '/tips/' . $sidebarTipId . '/report';
$actionReportOtherTargetId = 'tip-report-other';
$actionReportSuccessToast = 'Đã gửi báo cáo mẹo vặt.';
$actionReportErrorToast = 'Không thể gửi báo cáo mẹo vặt.';
$actionReportHiddenFields = ['redirect_to' => $sidebarTipPath];
?>

<aside class="detail-side space-y-6" >
    <?php require APPROOT . '/app/views/partials/shared/quick_info/quick_info_actions_card.php'; ?>
</aside>
