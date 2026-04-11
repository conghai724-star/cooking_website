<?php
$quickInfoTitle = (string) ($quickInfoTitle ?? 'Thông tin nhanh');
$quickInfoShowActions = (bool) ($quickInfoShowActions ?? false);
$quickInfoActionsContainerId = (string) ($quickInfoActionsContainerId ?? '');

if (!is_array($quickInfoItems ?? null)) {
    $quickInfoCategory = isset($quickInfoCategory)
        ? (string) $quickInfoCategory
        : (string) ($sidebarCategory ?? 'Chưa phân loại');
    $quickInfoViews = isset($quickInfoViews)
        ? (int) $quickInfoViews
        : (int) ($sidebarViews ?? 0);
    $quickInfoAuthor = isset($quickInfoAuthor)
        ? (string) $quickInfoAuthor
        : (string) ($sidebarAuthor ?? 'Không rõ');

    $quickInfoItems = [
        ['label' => 'Danh mục', 'value' => $quickInfoCategory !== '' ? $quickInfoCategory : 'Chưa phân loại'],
        ['label' => 'Lượt xem', 'value' => (string) $quickInfoViews],
        ['label' => 'Tác giả', 'value' => $quickInfoAuthor],
    ];
}
?>

<div class="rounded-3xl border border-slate-200 bg-white p-6">
    <h4 class="mb-4 text-lg font-black"><?= htmlspecialchars($quickInfoTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
    <?php require APPROOT . '/app/views/partials/shared/quick_info/quick_info_rows.php'; ?>

    <?php if ($quickInfoShowActions): ?>
        <div class="mt-4 flex flex-col gap-2"<?= $quickInfoActionsContainerId !== '' ? ' id="' . htmlspecialchars($quickInfoActionsContainerId, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
            <?php require APPROOT . '/app/views/partials/shared/content_action_buttons.php'; ?>
        </div>
    <?php endif; ?>
</div>
