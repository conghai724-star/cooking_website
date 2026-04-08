<aside class="space-y-6 lg:col-span-4">
    <div class="rounded-3xl border border-slate-200 bg-white p-6">
        <h4 class="mb-4 text-lg font-black">ThA�ng tin nhanh</h4>
        <?php
        $quickInfoItems = [
            ['label' => 'Danh mục', 'value' => (string) ($recipe['category_name'] ?? 'Chưa phA�n loại')],
            ['label' => 'Lượt xem', 'value' => (string) (int) ($recipe['view_count'] ?? 0)],
            ['label' => 'TA�c giả', 'value' => (string) ($recipe['author_name'] ?? 'Kh�ng rA�')],
        ];
        require APPROOT . '/app/views/partials/shared/quick_info_rows.php';
        ?>
        <?php if (!empty($recipe['user_id'])): ?>
                    <div class="mt-4 flex flex-col gap-2">
            <?php
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
            $actionSaveLabelOn = '�� luu c�ng th?c';
            $actionSaveLabelOff = 'Lưu cA�ng thức';
            $actionSaveSuccessToast = '�� c?p nh?t luu c�ng th?c.';
            $actionSaveErrorToast = 'Kh�ng thể lưu cA�ng thức lA�c nA�y.';

            $actionEnableShare = true;
            $actionShareText = 'Xem c�ng th?c n?u an n�y!';
            $actionShareTitle = (string) ($recipe['title'] ?? 'C�ng th?c n?u an');

            $actionEnableReport = function_exists('user_has_permission') && user_has_permission('user.recipes.report');
            $actionReportMode = 'modal';
            $actionReportModalTarget = '#recipe-report-modal';
            $actionReportTriggerId = 'btn-recipe-report-trigger';

            require APPROOT . '/app/views/partials/shared/content_action_buttons.php';
            ?>
        </div>
    <?php endif; ?>
    </div>
</aside>



