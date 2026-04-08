<?php
$breadcrumbItems = is_array($breadcrumbItems ?? null) ? $breadcrumbItems : [];
?>
<div class="mb-6 flex items-center gap-2 text-sm">
    <?php foreach ($breadcrumbItems as $index => $item): ?>
        <?php
        $label = (string) ($item['label'] ?? '');
        $url = (string) ($item['url'] ?? '');
        $isLast = $index === array_key_last($breadcrumbItems);
        ?>
        <?php if ($url !== '' && !$isLast): ?>
            <a class="font-medium text-primary" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="text-slate-400">/</span>
        <?php else: ?>
            <span class="text-slate-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
