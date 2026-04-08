<?php
$ingredientDescription = (string) ($ingredientDescription ?? 'Đang cập nhật.');
$ingredientUsage = (string) ($ingredientUsage ?? 'Đang cập nhật.');
$ingredientPreparation = (string) ($ingredientPreparation ?? 'Đang cập nhật.');
$ingredientStorage = (string) ($ingredientStorage ?? 'Đang cập nhật.');
?>
<div class="mb-8 rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="mb-4 text-2xl font-bold text-slate-900">Thông tin nguyên liệu</h2>
    <div class="space-y-4 text-sm leading-relaxed text-slate-700">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Mô tả</p><p><?= nl2br(htmlspecialchars($ingredientDescription, ENT_QUOTES, 'UTF-8')); ?></p></div>
        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Công dụng</p><p><?= nl2br(htmlspecialchars($ingredientUsage, ENT_QUOTES, 'UTF-8')); ?></p></div>
        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Cách sơ chế</p><p><?= nl2br(htmlspecialchars($ingredientPreparation, ENT_QUOTES, 'UTF-8')); ?></p></div>
        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Cách bảo quản</p><p><?= nl2br(htmlspecialchars($ingredientStorage, ENT_QUOTES, 'UTF-8')); ?></p></div>
    </div>
</div>
