<?php
$nutrition = is_array($nutrition ?? null) ? $nutrition : [];
?>
<div class="rounded-xl border border-primary/10 bg-white p-5 shadow-sm">
    <div class="mb-3 flex items-center gap-3">
        <span class="material-symbols-outlined text-primary">nutrition</span>
        <h2 class="text-xl font-bold text-slate-900">Thông tin dinh dưỡng</h2>
    </div>
    <p class="mb-4 text-sm text-slate-500">Khẩu phần tham chiếu: 100g</p>
    <div class="flex flex-wrap gap-3">
        <?php foreach ($nutrition as $row): ?>
            <div class="min-w-[150px] flex-1 rounded-lg border border-primary/10 bg-primary/5 px-3 py-2">
                <div class="flex items-center gap-2 text-slate-600">
                    <span class="material-symbols-outlined text-base text-primary/70"><?= htmlspecialchars((string) ($row[2] ?? 'nutrition'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="text-sm font-medium"><?= htmlspecialchars((string) ($row[0] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <p class="mt-1 text-base font-bold text-slate-900"><?= htmlspecialchars((string) ($row[1] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
