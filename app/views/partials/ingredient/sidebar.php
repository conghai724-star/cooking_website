<?php
$nutrition = is_array($nutrition ?? null) ? $nutrition : [];
$sidebarCategory = (string) ($sidebarCategory ?? 'Chưa phân loại');
$sidebarViews = (int) ($sidebarViews ?? 0);
$sidebarAuthor = (string) ($sidebarAuthor ?? 'Không rõ');
$sidebarAuthorId = (int) ($sidebarAuthorId ?? 0);
$sidebarIsFollowing = (bool) ($sidebarIsFollowing ?? false);
$sidebarIsSaved = (bool) ($sidebarIsSaved ?? false);
$sidebarIngredientId = (int) ($sidebarIngredientId ?? 0);
$sidebarIngredientName = (string) ($sidebarIngredientName ?? 'Nguyên liệu');
$sidebarTip = (string) ($sidebarTip ?? '');
?>
<aside class="space-y-6 lg:col-span-4">
    <?php require APPROOT . '/app/views/partials/ingredient/nutrition_card.php'; ?>

    <?php require APPROOT . '/app/views/partials/ingredient/quick_info_actions_card.php'; ?>

    <?php if ($sidebarTip !== ''): ?>
        <div class="rounded-xl border border-primary/20 bg-primary/5 p-6">
            <h3 class="mb-2 text-lg font-bold">Mẹo sử dụng</h3>
            <p class="text-sm italic leading-relaxed text-slate-600">"<?= htmlspecialchars($sidebarTip, ENT_QUOTES, 'UTF-8'); ?>"</p>
        </div>
    <?php endif; ?>
</aside>

