<div class="w-full">
    <div class="mx-auto max-w-7xl px-2 py-4 sm:px-4">
        <?php require APPROOT . '/app/views/recipes/partials/hero.php'; ?>

        <style>.detail-layout{display:flex;gap:2rem;align-items:flex-start}.detail-main{flex:1 1 auto;min-width:0}.detail-side{flex:0 0 300px;max-width:300px}@media (max-width:700px){.detail-layout{display:block}.detail-side{max-width:none;width:auto}}</style>
        <div class="detail-layout">
            <?php require APPROOT . '/app/views/recipes/partials/content_main.php'; ?>
            <?php require APPROOT . '/app/views/recipes/partials/quick_actions.php'; ?>
        </div>

    </div>
</div>

<?php
$reportModalId = 'recipe-report-modal';
$reportModalTitle = 'Báo cáo công thức';
$reportModalAction = URLROOT . '/recipes/report';
$reportModalReasonField = 'reason';
$reportModalDetailsField = 'reason_other';
$reportModalSuccessToast = 'Đã gửi báo cáo công thức.';
$reportModalErrorToast = 'Không thể gửi báo cáo công thức lúc này.';
$reportModalHiddenFields = ['recipe_id' => (string) ((int) ($recipe['id'] ?? 0))];
require APPROOT . '/app/views/partials/shared/content_report_modal.php';
?>








