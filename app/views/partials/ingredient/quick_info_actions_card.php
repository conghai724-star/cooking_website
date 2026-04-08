<?php
$sidebarCategory = (string) ($sidebarCategory ?? 'ChA�°a phĂ¢n loA�º¡i');
$sidebarViews = (int) ($sidebarViews ?? 0);
$sidebarAuthor = (string) ($sidebarAuthor ?? 'Không rõ');
$sidebarAuthorId = (int) ($sidebarAuthorId ?? 0);
$sidebarIsFollowing = (bool) ($sidebarIsFollowing ?? false);
$sidebarIsSaved = (bool) ($sidebarIsSaved ?? false);
$sidebarIngredientId = (int) ($sidebarIngredientId ?? 0);
$sidebarIngredientName = (string) ($sidebarIngredientName ?? 'Nguyên liệu');

$quickInfoTitle = 'ThÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â´ng tin nhanh';
$quickInfoItems = [
    ['label' => 'Danh mA�»¥c', 'value' => $sidebarCategory],
    ['label' => 'LA�°A�»£t xem', 'value' => (string) $sidebarViews],
    ['label' => 'TĂ¡c giA�º£', 'value' => $sidebarAuthor],
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
$actionSaveLabelOn = 'A�¿½A? luu nguyA?n li?u';
$actionSaveLabelOff = 'LA�°u nguyĂªn liA�»‡u';
$actionSaveSuccessToast = 'A�¿½A? c?p nh?t luu nguyA?n li?u.';
$actionSaveErrorToast = 'KhA�ng thA�»ƒ lA�°u nguyĂªn liA�»‡u lĂºc nĂ y.';

$actionEnableShare = true;
$actionShareText = 'Xem nguyĂªn liA�»‡u nĂ y';
$actionShareTitle = $sidebarIngredientName;

$actionEnableReport = function_exists('user_has_permission') && user_has_permission('user.ingredients.report');
$actionReportMode = 'modal';
$actionReportModalTarget = '#ingredient-report-modal';
$actionReportTriggerId = 'btn-ingredient-report-trigger';
$actionReportAction = URLROOT . '/ingredients/' . $sidebarIngredientId . '/report';
$actionReportOtherTargetId = 'ingredient-report-other';
$actionReportSuccessToast = 'A�¿½A? g?i bA?o cA?o nguyA?n li?u.';
$actionReportErrorToast = 'KhA�ng thA�»ƒ gA�»­i bĂ¡o cĂ¡o nguyĂªn liA�»‡u.';
$actionReportHiddenFields = ['redirect_to' => '/ingredients/' . $sidebarIngredientId];

require APPROOT . '/app/views/partials/shared/quick_info_actions_card.php';
