?<?php
$ingredientHeroImage = (string) ($ingredientHeroImage ?? '');
$ingredientHeroTitle = (string) ($ingredientHeroTitle ?? 'Nguyên liệu');
$ingredientHeroTag = (string) ($ingredientHeroTag ?? 'Nguyên liệu');
$ingredientHeroSummary = (string) ($ingredientHeroSummary ?? '');
?>
<div class="relative mb-8 w-full overflow-hidden rounded-3xl shadow-2xl">
    <div class="absolute inset-0 z-10 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
    <img class="h-[320px] w-full object-cover md:h-[420px]" src="<?= htmlspecialchars($ingredientHeroImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($ingredientHeroTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="absolute bottom-0 left-0 z-20 max-w-3xl p-6 md:p-10">
        <span class="mb-4 inline-block rounded-full bg-primary px-3 py-1 text-xs font-bold uppercase tracking-wider text-white"><?= htmlspecialchars($ingredientHeroTag, ENT_QUOTES, 'UTF-8'); ?></span>
        <h1 class="mb-3 text-4xl font-bold text-white md:text-5xl"><?= htmlspecialchars($ingredientHeroTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-base text-slate-200 md:text-lg"><?= htmlspecialchars($ingredientHeroSummary, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</div>
