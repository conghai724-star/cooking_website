<?php
$sidebarCategory = (string) ($sidebarCategory ?? 'Chua ph�n lo?i');
$sidebarViews = (int) ($sidebarViews ?? 0);
$sidebarAuthor = (string) ($sidebarAuthor ?? 'Kh�ng r�');
$sidebarAuthorId = (int) ($sidebarAuthorId ?? 0);
$sidebarIsFollowing = (bool) ($sidebarIsFollowing ?? false);
$sidebarIsSaved = (bool) ($sidebarIsSaved ?? false);
$sidebarIngredientId = (int) ($sidebarIngredientId ?? 0);
$sidebarIngredientName = (string) ($sidebarIngredientName ?? 'Nguy�n li?u');
?>
<div class="rounded-3xl border border-slate-200 bg-white p-6">
    <h4 class="mb-4 text-lg font-black">Th�ng tin nhanh</h4>
    <?php
    $quickInfoItems = [
        ['label' => 'Danh m?c', 'value' => $sidebarCategory],
        ['label' => 'Lu?t xem', 'value' => (string) $sidebarViews],
        ['label' => 'T�c gi?', 'value' => $sidebarAuthor],
    ];
    require APPROOT . '/app/views/partials/shared/quick_info_rows.php';
    ?>

    <div class="mt-4 flex flex-col gap-2" id="ingredient-action-box">
        <?php
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
        $actionSaveLabelOn = '�� luu nguy�n li?u';
        $actionSaveLabelOff = 'Luu nguy�n li?u';
        $actionSaveSuccessToast = '�� c?p nh?t luu nguy�n li?u.';
        $actionSaveErrorToast = 'Kh�ng th? luu nguy�n li?u l�c n�y.';

        $actionEnableShare = true;
        $actionShareText = 'Xem nguy�n li?u n�y';
        $actionShareTitle = $sidebarIngredientName;

        $actionEnableReport = function_exists('user_has_permission') && user_has_permission('user.ingredients.report');
        $actionReportMode = 'modal';
        $actionReportModalTarget = '#ingredient-report-modal';
        $actionReportTriggerId = 'btn-ingredient-report-trigger';
        $actionReportAction = URLROOT . '/ingredients/' . $sidebarIngredientId . '/report';
        $actionReportOtherTargetId = 'ingredient-report-other';
        $actionReportSuccessToast = '�� g?i b�o c�o nguy�n li?u.';
        $actionReportErrorToast = 'Kh�ng th? g?i b�o c�o nguy�n li?u.';
        $actionReportHiddenFields = ['redirect_to' => '/ingredients/' . $sidebarIngredientId];

        require APPROOT . '/app/views/partials/shared/content_action_buttons.php';
        ?>
    </div>
</div>
