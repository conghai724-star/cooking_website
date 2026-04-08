<?php
$quickInfoItems = is_array($quickInfoItems ?? null) ? $quickInfoItems : [];
?>
<div class="space-y-3 text-sm">
    <?php foreach ($quickInfoItems as $row): ?>
        <?php
        $label = (string) ($row['label'] ?? '');
        $value = (string) ($row['value'] ?? '');
        ?>
        <div class="flex items-center justify-between">
            <span class="text-slate-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="font-semibold"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    <?php endforeach; ?>
</div>
