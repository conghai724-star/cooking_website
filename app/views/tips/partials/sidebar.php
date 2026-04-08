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
?>

<aside class="space-y-6 lg:col-span-4">
    <div class="rounded-3xl border border-slate-200 bg-white p-6">
        <h4 class="mb-4 text-lg font-black">Thông tin nhanh</h4>
        <?php
        $quickInfoItems = [
            ['label' => 'Danh mục', 'value' => $sidebarCategory !== '' ? $sidebarCategory : 'Chưa phân loại'],
            ['label' => 'Lượt xem', 'value' => (string) $sidebarViews],
            ['label' => 'Tác giả', 'value' => $sidebarAuthor],
        ];
        require APPROOT . '/app/views/partials/shared/quick_info_rows.php';
        ?>

        <div id="tip-action-box">
            <?php
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

            require APPROOT . '/app/views/partials/shared/content_action_buttons.php';
            ?>
        </div>
    </div>
</aside>
