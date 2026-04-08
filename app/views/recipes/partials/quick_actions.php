<aside class="detail-side space-y-6" >
    <?php
    $quickInfoTitle = 'ThĂ„â€Ă‚Â´ng tin nhanh';
    $quickInfoItems = [
        ['label' => 'Danh mục', 'value' => (string) ($recipe['category_name'] ?? 'ChA�°a phĂ¢n loA�º¡i')],
        ['label' => 'LA�°A�»£t xem', 'value' => (string) (int) ($recipe['view_count'] ?? 0)],
        ['label' => 'TĂ¡c giA�º£', 'value' => (string) ($recipe['author_name'] ?? 'KhA�ng rĂµ')],
    ];

    $quickInfoShowActions = !empty($recipe['user_id']);
    $quickInfoActionsContainerId = '';

    if ($quickInfoShowActions) {
        $actionAuthorId = (int) $recipe['user_id'];
        $actionIsLoggedIn = $is_logged_in;
        $actionIsOwner = $actionIsLoggedIn && (int) current_user_id() === (int) $recipe['user_id'];
        $actionEnableFollow = function_exists('user_has_permission') && user_has_permission('user.follow.manage');
        $actionFollowAction = URLROOT . '/users/' . (int) $recipe['user_id'] . '/' . ($is_following ? 'unfollow' : 'follow');
        $actionIsFollowing = $is_following;

        $actionEnableSave = function_exists('user_has_permission') && user_has_permission('user.recipes.save');
        $actionSaveAction = URLROOT . '/recipes/save';
        $actionSaveHiddenFields = [
            'recipe_id' => (string) ((int) ($recipe['id'] ?? 0)),
        ];
        $actionIsSaved = $is_saved;
        $actionSaveLabelOn = 'A�¿½A? luu cA?ng th?c';
        $actionSaveLabelOff = 'LA�°u cĂ´ng thA�»©c';
        $actionSaveSuccessToast = 'A�¿½A? c?p nh?t luu cA?ng th?c.';
        $actionSaveErrorToast = 'KhA�ng thA�»ƒ lA�°u cĂ´ng thA�»©c lĂºc nĂ y.';

        $actionEnableShare = true;
        $actionShareText = 'Xem cĂ´ng thA�»©c nA�º¥u A�ƒn nĂ y!';
        $actionShareTitle = (string) ($recipe['title'] ?? 'CA�ng thức nA�º¥u A�ƒn');

        $actionEnableReport = function_exists('user_has_permission') && user_has_permission('user.recipes.report');
        $actionReportMode = 'modal';
        $actionReportModalTarget = '#recipe-report-modal';
        $actionReportTriggerId = 'btn-recipe-report-trigger';
    }

    require APPROOT . '/app/views/partials/shared/quick_info_actions_card.php';
    ?>
</aside>
