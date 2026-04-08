<?php
$sidebarCategory = (string) ($sidebarCategory ?? 'Chưa phân loại');
$sidebarViews = (int) ($sidebarViews ?? 0);
$sidebarAuthor = (string) ($sidebarAuthor ?? 'Không rõ');
$sidebarAuthorId = (int) ($sidebarAuthorId ?? 0);
$sidebarIsFollowing = (bool) ($sidebarIsFollowing ?? false);
$sidebarIsSaved = (bool) ($sidebarIsSaved ?? false);
$sidebarIngredientId = (int) ($sidebarIngredientId ?? 0);
$sidebarIngredientName = (string) ($sidebarIngredientName ?? 'Nguyên liệu');

$quickInfoTitle = 'Thông tin nhanh';
$quickInfoItems = [
    ['label' => 'Danh mục', 'value' => $sidebarCategory],
    ['label' => 'Lượt xem', 'value' => (string) $sidebarViews],
    ['label' => 'Tác giả', 'value' => $sidebarAuthor],
];

$quickInfoShowActions = true;
$quickInfoActionsContainerId = 'ingredient-action-box';

$actionAuthorId = $sidebarAuthorId;
$actionIsLoggedIn = is_logged_in();
$actionIsOwner = $actionIsLoggedIn && (int) current_user_id() === $sidebarAuthorId;
$actionEnableFollow = function_exists('user_has_permission') && user_has_permission('user.follow.manage');
$actionFollowAction = URLROOT . '/users/' . $sidebarAuthorId . '/' . ($sidebarIsFollowing ? 'unfollow' : 'follow');
$actionIsFollowing = $sidebarIsFollowing;

$actionEnableSave = function_exists('user_has_permission') && user_has_permission('user.ingredients.save');
$actionSaveAction = URLROOT . '/ingredients/save';
$actionSaveHiddenFields = [
    'ingredient_id' => (string) $sidebarIngredientId,
    'redirect_to' => '/ingredients/' . $sidebarIngredientId,
];
$actionIsSaved = $sidebarIsSaved;
$actionSaveLabelOn = 'Đã lưu nguyên liệu';
$actionSaveLabelOff = 'Lưu nguyên liệu';
$actionSaveSuccessToast = 'Đã cập nhật lưu nguyên liệu.';
$actionSaveErrorToast = 'Không thể lưu nguyên liệu lúc này.';

$actionEnableShare = true;
$actionShareText = 'Xem nguyên liệu này';
$actionShareTitle = $sidebarIngredientName;

$actionEnableReport = function_exists('user_has_permission') && user_has_permission('user.ingredients.report');
$actionReportMode = 'modal';
$actionReportModalTarget = '#ingredient-report-modal';
$actionReportTriggerId = 'btn-ingredient-report-trigger';
$actionReportAction = URLROOT . '/ingredients/' . $sidebarIngredientId . '/report';
$actionReportOtherTargetId = 'ingredient-report-other';
$actionReportSuccessToast = 'Đã gửi báo cáo nguyên liệu.';
$actionReportErrorToast = 'Không thể gửi báo cáo nguyên liệu.';
$actionReportHiddenFields = ['redirect_to' => '/ingredients/' . $sidebarIngredientId];

require APPROOT . '/app/views/partials/shared/quick_info_actions_card.php';