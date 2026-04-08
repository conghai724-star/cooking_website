<?php
$viUncategorized = json_decode('"Ch\u01b0a ph\u00e2n lo\u1ea1i"');
$viUnknown = json_decode('"Kh\u00f4ng r\u00f5"');
$viIngredient = json_decode('"Nguy\u00ean li\u1ec7u"');
$viUsageTip = json_decode('"M\u1eb9o s\u1eed d\u1ee5ng"');

$nutrition = is_array($nutrition ?? null) ? $nutrition : [];
$sidebarCategory = (string) ($sidebarCategory ?? $viUncategorized);
$sidebarViews = (int) ($sidebarViews ?? 0);
$sidebarAuthor = (string) ($sidebarAuthor ?? $viUnknown);
$sidebarAuthorId = (int) ($sidebarAuthorId ?? 0);
$sidebarIsFollowing = (bool) ($sidebarIsFollowing ?? false);
$sidebarIsSaved = (bool) ($sidebarIsSaved ?? false);
$sidebarIngredientId = (int) ($sidebarIngredientId ?? 0);
$sidebarIngredientName = (string) ($sidebarIngredientName ?? $viIngredient);
$sidebarTip = (string) ($sidebarTip ?? '');
?>
<aside class="detail-side space-y-6" >
    <?php require APPROOT . '/app/views/partials/ingredient/quick_info_actions_card.php'; ?>


    <?php if ($sidebarTip !== ''): ?>
        <div class="rounded-xl border border-primary/20 bg-primary/5 p-6">
            <h3 class="mb-2 text-lg font-bold"><?= htmlspecialchars($viUsageTip, ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="text-sm italic leading-relaxed text-slate-600">"<?= htmlspecialchars($sidebarTip, ENT_QUOTES, 'UTF-8'); ?>"</p>
        </div>
    <?php endif; ?>
</aside>