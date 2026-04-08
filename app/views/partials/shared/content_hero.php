<?php
$heroImagePath = (string) ($heroImagePath ?? '');
$heroImagePrefix = (string) ($heroImagePrefix ?? (URLROOT . '/uploads/'));
$heroImageIsAbsolute = (bool) ($heroImageIsAbsolute ?? false);
$heroHeightClass = (string) ($heroHeightClass ?? 'h-[300px] lg:h-[420px]');
$heroCategory = (string) ($heroCategory ?? 'Nội dung');
$heroBadge2 = (string) ($heroBadge2 ?? '');
$heroTitle = (string) ($heroTitle ?? '');
$heroAuthor = (string) ($heroAuthor ?? 'Tác giả');
$heroDate = (string) ($heroDate ?? date('Y-m-d'));

$heroImageSrc = '';
if ($heroImagePath !== '') {
    $heroImageSrc = $heroImageIsAbsolute ? $heroImagePath : ($heroImagePrefix . $heroImagePath);
}
?>
<div class="relative mb-8 w-full overflow-hidden rounded-3xl shadow-2xl <?= htmlspecialchars($heroHeightClass, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="absolute inset-0 z-10 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
    <?php if ($heroImageSrc !== ''): ?>
        <img class="h-full w-full object-cover" src="<?= htmlspecialchars($heroImageSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($heroTitle !== '' ? $heroTitle : 'Hero image', ENT_QUOTES, 'UTF-8'); ?>">
    <?php else: ?>
        <div class="h-full w-full bg-gradient-to-br from-amber-200 via-orange-200 to-orange-400"></div>
    <?php endif; ?>

    <div class="absolute bottom-8 left-8 right-8 z-20 text-white">
        <div class="mb-4 flex flex-wrap gap-2">
            <span class="rounded-full bg-primary px-3 py-1 text-xs font-bold uppercase tracking-wide"><?= htmlspecialchars($heroCategory, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if ($heroBadge2 !== ''): ?>
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase tracking-wide backdrop-blur-md"><?= htmlspecialchars($heroBadge2, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </div>
        <h1 class="mb-3 text-3xl font-black md:text-5xl"><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="flex flex-wrap items-center gap-5 text-sm text-white/90">
            <span>Tác giả: <?= htmlspecialchars($heroAuthor, ENT_QUOTES, 'UTF-8'); ?></span>
            <span>Ngày đăng: <?= htmlspecialchars($heroDate, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>
</div>
