<aside class="detail-side space-y-6" >
    <?php
    $quickInfoTitle = 'Thông tin nhanh';
    $quickInfoItems = [
        ['label' => 'Danh mục', 'value' => (string) ($recipe['category_name'] ?? 'Chưa phân loại')],
        ['label' => 'Lượt xem', 'value' => (string) (int) ($recipe['view_count'] ?? 0)],
        ['label' => 'Tác giả', 'value' => (string) ($recipe['author_name'] ?? 'Không rõ')],
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
        $actionSaveLabelOn = 'Đã lưu công thức';
        $actionSaveLabelOff = 'Lưu công thức';
        $actionSaveSuccessToast = 'Đã cập nhật lưu công thức.';
        $actionSaveErrorToast = 'Không thể lưu công thức lúc này.';

        $actionEnableShare = true;
        $actionShareText = 'Xem công thức nấu ăn này!';
        $actionShareTitle = (string) ($recipe['title'] ?? 'Công thức nấu ăn');

        $actionEnableReport = function_exists('user_has_permission') && user_has_permission('user.recipes.report');
        $actionReportMode = 'modal';
        $actionReportModalTarget = '#recipe-report-modal';
        $actionReportTriggerId = 'btn-recipe-report-trigger';
    }

    require APPROOT . '/app/views/partials/shared/quick_info_actions_card.php';
    ?>
</aside>