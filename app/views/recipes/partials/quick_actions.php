<aside class="detail-side space-y-6">
    <?php
    $quickInfoTitle = 'Thông tin nhanh';
    $quickInfoCategory = (string) ($recipe['category_name'] ?? 'Chưa phân loại');
    $quickInfoViews = (int) ($recipe['view_count'] ?? 0);
    $quickInfoAuthor = (string) ($recipe['author_name'] ?? 'Không rõ');

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

    require APPROOT . '/app/views/partials/shared/quick_info/quick_info_actions_card.php';
    ?>

    <?php $recipeId = (int) ($recipe['id'] ?? 0); ?>
    <div class="rounded-3xl border border-slate-200 bg-white p-6">
        <h4 class="mb-4 text-lg font-black">Xuất công thức</h4>
        <form class="space-y-3" method="get">
            <input type="hidden" name="include_ingredients" value="0">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary" type="checkbox" name="include_ingredients" value="1" checked>
                Bao gồm nguyên liệu
            </label>
            <input type="hidden" name="include_calories" value="0">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary" type="checkbox" name="include_calories" value="1" checked>
                Bao gồm calories
            </label>
            <input type="hidden" name="include_images" value="0">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary" type="checkbox" name="include_images" value="1" checked>
                Bao gồm hình ảnh
            </label>

            <div class="grid grid-cols-1 gap-2 pt-1">
                <button class="rounded-xl border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700 hover:border-primary hover:text-primary" type="submit" formaction="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/export/pdf">[PDF] Xuất PDF</button>
                <button class="rounded-xl border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700 hover:border-primary hover:text-primary" type="submit" formaction="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/export/docx">[Word] Xuất DOCX</button>
                <button class="rounded-xl border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700 hover:border-primary hover:text-primary" type="submit" formaction="<?= URLROOT; ?>/recipes/<?= $recipeId; ?>/export/txt">[TXT] Xuat TXT</button>
            </div>
        </form>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6">
        <h4 class="mb-4 text-lg font-black">Tiện ích</h4>
        <div class="grid grid-cols-1 gap-2">
            <button class="rounded-xl border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700 hover:border-primary hover:text-primary" type="button" onclick="window.print()">In công thức</button>
        </div>
    </div>

    <!-- <script>
    function shareCurrentRecipe() {
        const url = window.location.href;
        const title = document.title || 'Cong thuc';
        if (navigator.share) {
            navigator.share({ title, url }).catch(() => {});
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Đã sao chép link công thức.');
            }).catch(() => {
                prompt('Sao chép link công thức:', url);
            });
            return;
        }
        prompt('Sao chép link công thức:', url);
    }
    </script> -->
</aside>
