<?php
$nutrition = is_array($nutrition ?? null) ? $nutrition : [];
?>
<div class="rounded-xl border border-primary/10 bg-white p-6 shadow-sm">
    <div class="mb-6 flex items-center gap-3">
        <span class="material-symbols-outlined text-primary">nutrition</span>
        <h2 class="text-2xl font-bold text-slate-900">Thông tin dinh dưỡng</h2>
    </div>
    <p class="mb-6 text-sm text-slate-500">Khẩu phần tham chiếu: 100g</p>
    <div class="space-y-4">
        <?php foreach ($nutrition as $row): ?>
            <div class="flex items-center justify-between border-b border-primary/5 py-3">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-lg text-primary/60"><?= htmlspecialchars((string) ($row[2] ?? 'nutrition'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="font-medium text-slate-700"><?= htmlspecialchars((string) ($row[0] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <span class="font-bold text-slate-900"><?= htmlspecialchars((string) ($row[1] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
